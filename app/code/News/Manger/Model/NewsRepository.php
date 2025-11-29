<?php

namespace News\Manger\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\DataObjectHelper;
use News\Manger\Api\NewsRepositoryInterface;
use News\Manger\Api\Data\NewsInterface;
use News\Manger\Api\Data\NewsInterfaceFactory;
use News\Manger\Api\Data\NewsSearchResultsInterfaceFactory;
use News\Manger\Api\Data\CategorySearchResultsInterfaceFactory;
use News\Manger\Model\ResourceModel\News as NewsResource;
use News\Manger\Model\ResourceModel\News\CollectionFactory as NewsCollectionFactory;
use News\Manger\Model\NewsFactory;
use Psr\Log\LoggerInterface;

class NewsRepository implements NewsRepositoryInterface
{
  /**
   * @var NewsFactory
   */
  protected $newsFactory;

  /**
   * @var NewsResource
   */
  protected $resource;

  /**
   * @var NewsCollectionFactory
   */
  protected $collectionFactory;

  /**
   * @var NewsInterfaceFactory
   */
  protected $dataNewsFactory;

  /**
   * @var NewsSearchResultsInterfaceFactory
   */
  protected $searchResultsFactory;

  /**
   * @var CategorySearchResultsInterfaceFactory
   */
  protected $categorySearchResultsFactory;

  /**
   * @var CollectionProcessorInterface
   */
  protected $collectionProcessor;

  /**
   * @var SearchCriteriaBuilder
   */
  protected $searchCriteriaBuilder;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var DataObjectHelper
   */
  protected $dataObjectHelper;

  public function __construct(
    NewsFactory $newsFactory,
    NewsResource $resource,
    NewsCollectionFactory $collectionFactory,
    NewsInterfaceFactory $dataNewsFactory,
    NewsSearchResultsInterfaceFactory $searchResultsFactory,
    CategorySearchResultsInterfaceFactory $categorySearchResultsFactory,
    CollectionProcessorInterface $collectionProcessor,
    SearchCriteriaBuilder $searchCriteriaBuilder,
    DataObjectHelper $dataObjectHelper,
    LoggerInterface $logger
  ) {
    $this->newsFactory = $newsFactory;
    $this->resource = $resource;
    $this->collectionFactory = $collectionFactory;
    $this->dataNewsFactory = $dataNewsFactory;
    $this->searchResultsFactory = $searchResultsFactory;
    $this->categorySearchResultsFactory = $categorySearchResultsFactory;
    $this->collectionProcessor = $collectionProcessor;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->dataObjectHelper = $dataObjectHelper;
    $this->logger = $logger;
  }

  /**
   * @inheritDoc
   */
  public function save(NewsInterface $news)
  {
    try {
      // Validate data including category IDs
      $this->validate($news);

      $model = null;

      // If there's an ID, load existing news
      if ($news->getNewsId() && is_numeric($news->getNewsId())) {
        try {
          $model = $this->newsFactory->create();
          $this->resource->load($model, $news->getNewsId());
          if (!$model->getId()) {
            throw new NoSuchEntityException(__('News with id "%1" does not exist.', $news->getNewsId()));
          }
        } catch (\Exception $e) {
          $this->logger->error('Error loading news: ' . $e->getMessage());
          throw new NoSuchEntityException(__('News with id "%1" does not exist.', $news->getNewsId()));
        }
      } else {
        // Create new model
        $model = $this->newsFactory->create();
      }

      // Set data
      $model->setData([
        'news_title' => $news->getNewsTitle(),
        'news_content' => $news->getNewsContent(),
        'news_status' => $news->getNewsStatus() ?? 1,
        'category_ids' => $news->getCategoryIds() ?: []
      ]);

      // Set ID if updating
      if ($news->getNewsId()) {
        $model->setId($news->getNewsId());
      }

      $this->resource->save($model);
      $newsId = $model->getId();

      // Handle category relations with validation
      $categoryIds = $news->getCategoryIds() ?: [];
      if (!empty($categoryIds)) {
        // Validate and get only existing category IDs
        $validCategoryIds = $this->validateCategoryIds($categoryIds);
        $this->updateNewsCategoryRelations($newsId, $validCategoryIds);

        // Log if some categories were invalid
        if (count($validCategoryIds) !== count($categoryIds)) {
          $invalidIds = array_diff($categoryIds, $validCategoryIds);
          $this->logger->warning(
            'Some category IDs were invalid and skipped for news ID ' . $newsId . ': ' .
              implode(', ', $invalidIds)
          );
        }
      } else {
        // Clear all category relations if no categories provided
        $this->updateNewsCategoryRelations($newsId, []);
      }

      // Update the data object with saved values
      $news->setNewsId($model->getId());
      $news->setCreatedAt($model->getCreatedAt());
      $news->setUpdatedAt($model->getUpdatedAt());

      return $news;
    } catch (LocalizedException $e) {
      // Re-throw validation errors as-is
      throw $e;
    } catch (\Exception $e) {
      $this->logger->error('Error saving news: ' . $e->getMessage());
      throw new CouldNotSaveException(__('Could not save news: %1', $e->getMessage()));
    }
  }

  /**
   * Validate that category IDs exist in database
   *
   * @param array $categoryIds
   * @return array Valid category IDs that exist in database
   */
  protected function validateCategoryIds(array $categoryIds): array
  {
    if (empty($categoryIds)) {
      return [];
    }

    try {
      $connection = $this->resource->getConnection();
      $categoryTable = $this->resource->getTable('news_category');

      $select = $connection->select()
        ->from($categoryTable, 'category_id')
        ->where('category_id IN (?)', $categoryIds);

      $validIds = $connection->fetchCol($select);

      // Convert to integers to match input format
      return array_map('intval', $validIds);
    } catch (\Exception $e) {
      $this->logger->error('Error validating category IDs: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Update the category relations for a news item
   *
   * @param int $newsId
   * @param array $categoryIds
   * @throws CouldNotSaveException
   */
  protected function updateNewsCategoryRelations($newsId, array $categoryIds = [])
  {
    $connection = $this->resource->getConnection();
    $tableName = $this->resource->getTable('news_news_category');

    try {
      // Begin transaction
      $connection->beginTransaction();

      // Delete existing relations
      $connection->delete($tableName, ['news_id = ?' => $newsId]);

      if (!empty($categoryIds)) {
        // Only proceed if we have valid category IDs
        $validCategoryIds = $this->validateCategoryIds($categoryIds);

        if (!empty($validCategoryIds)) {
          // Insert new relations
          $data = [];
          foreach ($validCategoryIds as $categoryId) {
            $data[] = [
              'news_id' => (int)$newsId,
              'category_id' => (int)$categoryId
            ];
          }

          if (!empty($data)) {
            $connection->insertMultiple($tableName, $data);
          }
        }
      }

      // Commit transaction
      $connection->commit();
    } catch (\Exception $e) {
      // Rollback transaction on error
      $connection->rollBack();
      $this->logger->error('Error updating news category relations: ' . $e->getMessage());
      throw new CouldNotSaveException(__('Could not update news category relations: %1', $e->getMessage()));
    }
  }

  /**
   * Get category IDs from pivot table
   *
   * @param int $newsId
   * @return array
   */
  protected function getCategoryIdsFromPivotTable($newsId)
  {
    try {
      $connection = $this->resource->getConnection();
      $tableName = $this->resource->getTable('news_news_category');
      $select = $connection->select()
        ->from($tableName, 'category_id')
        ->where('news_id = ?', $newsId);

      $categoryIds = $connection->fetchCol($select);

      // Convert to integers
      return array_map('intval', $categoryIds);
    } catch (\Exception $e) {
      $this->logger->error('Error getting category IDs from pivot table: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * @inheritDoc
   */
  public function getById($newsId)
  {
    if (!$newsId || !is_numeric($newsId)) {
      throw new NoSuchEntityException(__('Invalid news ID provided.'));
    }

    $newsModel = $this->newsFactory->create();
    $this->resource->load($newsModel, $newsId);

    if (!$newsModel->getId()) {
      throw new NoSuchEntityException(__('News with id "%1" does not exist.', $newsId));
    }

    // Convert model to data object
    $newsData = $this->dataNewsFactory->create();
    $newsData->setNewsId($newsModel->getId());
    $newsData->setNewsTitle($newsModel->getNewsTitle());
    $newsData->setNewsContent($newsModel->getNewsContent());
    $newsData->setNewsStatus($newsModel->getNewsStatus());
    $newsData->setCreatedAt($newsModel->getCreatedAt());
    $newsData->setUpdatedAt($newsModel->getUpdatedAt());

    // Get category IDs from pivot table
    $categoryIds = $this->getCategoryIdsFromPivotTable($newsModel->getId());
    $newsData->setCategoryIds($categoryIds);

    return $newsData;
  }

  /**
   * @inheritDoc
   */
  // public function getList(SearchCriteriaInterface $searchCriteria = null)
  // {
  //   $collection = $this->collectionFactory->create();

  //   if ($searchCriteria === null) {
  //     $searchCriteria = $this->searchCriteriaBuilder->create();
  //   }

  //   $this->collectionProcessor->process($searchCriteria, $collection);

  //   $searchResults = $this->searchResultsFactory->create();
  //   $searchResults->setSearchCriteria($searchCriteria);

  //   // Convert models to data objects
  //   $items = [];
  //   foreach ($collection->getItems() as $model) {
  //     $newsData = $this->dataNewsFactory->create();
  //     $newsData->setNewsId($model->getId());
  //     $newsData->setNewsTitle($model->getNewsTitle());
  //     $newsData->setNewsContent($model->getNewsContent());
  //     $newsData->setNewsStatus($model->getNewsStatus());
  //     $newsData->setCreatedAt($model->getCreatedAt());
  //     $newsData->setUpdatedAt($model->getUpdatedAt());

  //     // Get category IDs from pivot table
  //     $categoryIds = $this->getCategoryIdsFromPivotTable($model->getId());
  //     $newsData->setCategoryIds($categoryIds);

  //     $items[] = $newsData;
  //   }

  //   $searchResults->setItems($items);
  //   $searchResults->setTotalCount($collection->getSize());

  //   return $searchResults;
  // }
  /**
   * @inheritDoc
   */
  public function getList(SearchCriteriaInterface $searchCriteria = null)
  {
    // إنشاء مجموعة الأخبار
    $collection = $this->collectionFactory->create();

    // إذا لم يُمرر SearchCriteria، أنشئ افتراضي
    if ($searchCriteria === null) {
      $searchCriteria = $this->searchCriteriaBuilder->create();
    }

    // تطبيق عمليات الفلترة والفرز والpagination على المجموعة
    $this->collectionProcessor->process($searchCriteria, $collection);

    // إنشاء كائن نتائج البحث (SearchResults)
    $searchResults = $this->searchResultsFactory->create();
    $searchResults->setSearchCriteria($searchCriteria);

    $items = [];

    // تحويل كل عنصر من المجموعة إلى Data Object مناسب
    foreach ($collection->getItems() as $model) {
      // تمرير بيانات النموذج عند الإنشاء لData Object (لتجنب مشاكل setData)
      $newsData = $this->dataNewsFactory->create(['data' => $model->getData()]);

      // جلب جميع معرّفات الفئات المرتبطة بكل خبر
      $categoryIds = $this->getCategoryIdsFromPivotTable($model->getId());
      $newsData->setCategoryIds($categoryIds);

      $items[] = $newsData;
    }

    $searchResults->setItems($items);
    $searchResults->setTotalCount($collection->getSize());

    return $searchResults;
  }

  /**
   * @inheritDoc
   */
  public function delete(NewsInterface $news)
  {
    try {
      $newsModel = $this->newsFactory->create();
      $this->resource->load($newsModel, $news->getNewsId());

      if (!$newsModel->getId()) {
        throw new NoSuchEntityException(__('News with id "%1" does not exist.', $news->getNewsId()));
      }

      // Delete category relations first (handled by foreign key CASCADE)
      $this->resource->delete($newsModel);
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Error deleting news: ' . $e->getMessage());
      throw new CouldNotDeleteException(__('Could not delete news: %1', $e->getMessage()));
    }
  }

  /**
   * @inheritDoc
   */
  public function deleteById($newsId)
  {
    return $this->delete($this->getById($newsId));
  }

  /**
   * @inheritDoc
   */
  public function getCategories($newsId)
  {
    // Implementation for getting categories for a news item
    $searchResults = $this->categorySearchResultsFactory->create();
    $searchResults->setItems([]);
    $searchResults->setTotalCount(0);
    return $searchResults;
  }

  /**
   * @inheritDoc
   */
  public function addCategory($newsId, $categoryId)
  {
    try {
      // Validate category ID exists
      $validCategoryIds = $this->validateCategoryIds([$categoryId]);
      if (empty($validCategoryIds)) {
        $this->logger->error('Invalid category ID: ' . $categoryId);
        return false;
      }

      // Get current news
      $news = $this->getById($newsId);
      $currentCategories = $news->getCategoryIds();

      // Add category if not already present
      if (!in_array($categoryId, $currentCategories)) {
        $currentCategories[] = $categoryId;
        $news->setCategoryIds($currentCategories);
        $this->save($news);
      }

      return true;
    } catch (\Exception $e) {
      $this->logger->error('Error adding category to news: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * @inheritDoc
   */
  public function removeCategory($newsId, $categoryId)
  {
    try {
      // Get current news
      $news = $this->getById($newsId);
      $currentCategories = $news->getCategoryIds();

      // Remove category
      $currentCategories = array_filter($currentCategories, function ($id) use ($categoryId) {
        return $id != $categoryId;
      });

      $news->setCategoryIds(array_values($currentCategories));
      $this->save($news);

      return true;
    } catch (\Exception $e) {
      $this->logger->error('Error removing category from news: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * @inheritDoc
   */
  public function setCategories($newsId, array $categoryIds)
  {
    try {
      // Validate category IDs
      $validCategoryIds = $this->validateCategoryIds($categoryIds);

      if (count($validCategoryIds) !== count($categoryIds)) {
        $invalidIds = array_diff($categoryIds, $validCategoryIds);
        $this->logger->warning(
          'Some category IDs are invalid and will be skipped: ' . implode(', ', $invalidIds)
        );
      }

      $news = $this->getById($newsId);
      $news->setCategoryIds($validCategoryIds);
      $this->save($news);
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Error setting categories for news: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * @inheritDoc
   */
  public function validate(NewsInterface $news)
  {
    $errors = [];

    // Validate required fields
    if (empty(trim($news->getNewsTitle()))) {
      $errors[] = __('News title is required.');
    }

    if (empty(trim($news->getNewsContent()))) {
      $errors[] = __('News content is required.');
    }

    // Validate news status
    $status = $news->getNewsStatus();
    if ($status !== null && !in_array($status, [0, 1])) {
      $errors[] = __('News status must be 0 (disabled) or 1 (enabled).');
    }

    // Validate category IDs if provided
    $categoryIds = $news->getCategoryIds();
    if (!empty($categoryIds)) {
      // Check if all provided IDs are numeric
      foreach ($categoryIds as $categoryId) {
        if (!is_numeric($categoryId) || $categoryId <= 0) {
          $errors[] = __('All category IDs must be positive integers.');
          break;
        }
      }

      // Check if category IDs exist in database
      if (empty($errors)) {
        $validCategoryIds = $this->validateCategoryIds($categoryIds);
        if (count($validCategoryIds) !== count($categoryIds)) {
          $invalidIds = array_diff($categoryIds, $validCategoryIds);
          $errors[] = __('Invalid category IDs: %1', implode(', ', $invalidIds));
        }
      }
    }

    // Throw exception if there are validation errors
    if (!empty($errors)) {
      throw new LocalizedException(__(implode(' ', $errors)));
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function exists($newsId): bool
  {
    try {
      $this->getById($newsId);
      return true;
    } catch (NoSuchEntityException $e) {
      return false;
    }
  }

  /**
   * Check if a category exists
   *
   * @param int $categoryId
   * @return bool
   */
  public function categoryExists($categoryId): bool
  {
    try {
      $validIds = $this->validateCategoryIds([$categoryId]);
      return !empty($validIds);
    } catch (\Exception $e) {
      $this->logger->error('Error checking category existence: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Get news by category ID
   *
   * @param int $categoryId
   * @param SearchCriteriaInterface|null $searchCriteria
   * @return \News\Manger\Api\Data\NewsSearchResultsInterface
   */
  public function getNewsByCategory($categoryId, SearchCriteriaInterface $searchCriteria = null)
  {
    $collection = $this->collectionFactory->create();

    // Join with category relation table
    $collection->getSelect()->joinInner(
      ['nnc' => $this->resource->getTable('news_news_category')],
      'main_table.news_id = nnc.news_id',
      []
    )->where('nnc.category_id = ?', $categoryId);

    if ($searchCriteria === null) {
      $searchCriteria = $this->searchCriteriaBuilder->create();
    }

    $this->collectionProcessor->process($searchCriteria, $collection);

    $searchResults = $this->searchResultsFactory->create();
    $searchResults->setSearchCriteria($searchCriteria);

    // Convert models to data objects
    $items = [];
    foreach ($collection->getItems() as $model) {
      $newsData = $this->dataNewsFactory->create();
      $newsData->setNewsId($model->getId());
      $newsData->setNewsTitle($model->getNewsTitle());
      $newsData->setNewsContent($model->getNewsContent());
      $newsData->setNewsStatus($model->getNewsStatus());
      $newsData->setCreatedAt($model->getCreatedAt());
      $newsData->setUpdatedAt($model->getUpdatedAt());

      // Get all category IDs for this news item
      $categoryIds = $this->getCategoryIdsFromPivotTable($model->getId());
      $newsData->setCategoryIds($categoryIds);

      $items[] = $newsData;
    }

    $searchResults->setItems($items);
    $searchResults->setTotalCount($collection->getSize());

    return $searchResults;
  }
}
