<?php

namespace News\Manger\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

use News\Manger\Api\CategoryRepositoryInterface;
use News\Manger\Api\Data\CategoryInterface;
use News\Manger\Api\Data\CategorySearchResultsInterfaceFactory;
use News\Manger\Model\ResourceModel\Category as CategoryResource;
use News\Manger\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

use News\Manger\Model\CategoryFactory;
use News\Manger\Api\Data\CategoryInterfaceFactory as DataCategoryFactory;

/**
 * Category repository class
 */
class CategoryRepository implements CategoryRepositoryInterface
{
  /**
   * @var CategoryResource
   */
  protected $resource;

  /**
   * @var CategoryFactory
   */
  protected $categoryFactory;

  /**
   * @var DataCategoryFactory
   */
  protected $dataCategoryFactory;

  /**
   * @var CategoryCollectionFactory
   */
  protected $collectionFactory;

  /**
   * @var CategorySearchResultsInterfaceFactory
   */
  protected $searchResultsFactory;

  /**
   * @var CollectionProcessorInterface
   */
  protected $collectionProcessor;

  /**
   * @var \Magento\Framework\Api\SearchCriteriaBuilder
   */
  protected $searchCriteriaBuilder;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Magento\Framework\App\ResourceConnection
   */
  protected $resourceConnection;

  /**
   * @param CategoryResource $resource
   * @param CategoryFactory $categoryFactory
   * @param DataCategoryFactory $dataCategoryFactory
   * @param CategoryCollectionFactory $collectionFactory
   * @param CategorySearchResultsInterfaceFactory $searchResultsFactory
   * @param CollectionProcessorInterface $collectionProcessor
   * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
   * @param \Magento\Framework\App\ResourceConnection $resourceConnection
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(
    CategoryResource $resource,
    CategoryFactory $categoryFactory,
    DataCategoryFactory $dataCategoryFactory,
    CategoryCollectionFactory $collectionFactory,
    CategorySearchResultsInterfaceFactory $searchResultsFactory,
    CollectionProcessorInterface $collectionProcessor,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Framework\App\ResourceConnection $resourceConnection,
    \Psr\Log\LoggerInterface $logger = null
  ) {
    $this->resource = $resource;
    $this->categoryFactory = $categoryFactory;
    $this->dataCategoryFactory = $dataCategoryFactory;
    $this->collectionFactory = $collectionFactory;
    $this->searchResultsFactory = $searchResultsFactory;
    $this->collectionProcessor = $collectionProcessor;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->resourceConnection = $resourceConnection;
    $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()
      ->get(\Psr\Log\LoggerInterface::class);
  }

  /**
   * @inheritDoc
   */
  public function save(CategoryInterface $category, $saveOptions = false): CategoryInterface
  {
    $connection = $this->resourceConnection->getConnection();

    try {
      // بداية Transaction
      $connection->beginTransaction();

      // تحويل Data Object إلى Model Object
      $model = null;

      // إذا كان يوجد ID نحاول تحميل العنصر وتحديثه
      if ($category->getCategoryId() && is_numeric($category->getCategoryId())) {
        try {
          $model = $this->categoryFactory->create();
          $this->resource->load($model, $category->getCategoryId());

          if (!$model->getId()) {
            throw new NoSuchEntityException(__('Category with ID "%1" does not exist.', $category->getCategoryId()));
          }
        } catch (NoSuchEntityException $e) {
          throw $e;
        }
      } else {
        // عنصر جديد
        $model = $this->categoryFactory->create();
      }

      // نسخ البيانات من الـ Data Object إلى الـ Model
      $model->setCategoryName($category->getCategoryName());
      $model->setCategoryDescription($category->getCategoryDescription());
      $model->setCategoryStatus($category->getCategoryStatus());
      $model->setParentIds($category->getParentIds() ?: []);
      $model->setChildIds($category->getChildIds() ?: []);
      $model->setNewsIds($category->getNewsIds() ?: []);

      // حفظ الموديل في قاعدة البيانات
      $this->resource->save($model);

      // الآن نتعامل مع جدول news_news_category
      $categoryId = $model->getId();
      $newsIds = $category->getNewsIds() ?: [];

      // حفظ العلاقات في الجدول المنفصل
      $this->updateNewsCategoryRelations($categoryId, $newsIds);

      // تحديث الـ Data Object بالقيم الجديدة مثل ID و Timestamps
      $category->setCategoryId($model->getId());
      $category->setCreatedAt($model->getCreatedAt());
      $category->setUpdatedAt($model->getUpdatedAt());

      // إتمام Transaction
      $connection->commit();

      return $category;
    } catch (\Exception $e) {
      // إلغاء Transaction في حالة خطأ
      $connection->rollBack();
      throw new CouldNotSaveException(__('Could not save the category: %1', $e->getMessage()), $e);
    }
  }

  /**
   * Update relations between category and news in pivot table
   *
   * @param int $categoryId
   * @param array $newsIds
   * @return void
   */
  protected function updateNewsCategoryRelations($categoryId, $newsIds = [])
  {
    $connection = $this->resourceConnection->getConnection();
    $tableName = $this->resourceConnection->getTableName('news_news_category');

    try {
      // أولاً: مسح العلاقات القديمة للـ category ده
      $connection->delete($tableName, ['category_id = ?' => $categoryId]);

      // ثانياً: إضافة العلاقات الجديدة (إذا كان فيه news_ids)
      if (!empty($newsIds) && is_array($newsIds)) {
        $insertData = [];
        foreach ($newsIds as $newsId) {
          if (is_numeric($newsId) && $newsId > 0) {
            $insertData[] = [
              'news_id' => (int)$newsId,
              'category_id' => (int)$categoryId
            ];
          }
        }

        if (!empty($insertData)) {
          $connection->insertMultiple($tableName, $insertData);
        }
      }
    } catch (\Exception $e) {
      $this->logger->error('Error updating news-category relations: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Get news IDs for a category from pivot table
   *
   * @param int $categoryId
   * @return array
   */
  protected function getNewsIdsFromPivotTable($categoryId)
  {
    $connection = $this->resourceConnection->getConnection();
    $tableName = $this->resourceConnection->getTableName('news_news_category');

    $select = $connection->select()
      ->from($tableName, ['news_id'])
      ->where('category_id = ?', $categoryId);

    $result = $connection->fetchCol($select);
    return array_map('intval', $result);
  }

  /**
   * @inheritDoc
   */
  public function delete(CategoryInterface $category): bool
  {
    $connection = $this->resourceConnection->getConnection();

    try {
      $connection->beginTransaction();

      // We need to get the model to delete it
      $model = $this->categoryFactory->create();
      $this->resource->load($model, $category->getCategoryId());

      if (!$model->getId()) {
        throw new NoSuchEntityException(__('Category with id "%1" does not exist.', $category->getCategoryId()));
      }

      // حذف العلاقات من الجدول المنفصل أولاً
      $this->updateNewsCategoryRelations($category->getCategoryId(), []);

      // حذف الـ Category نفسه
      $this->resource->delete($model);

      $connection->commit();
    } catch (\Exception $e) {
      $connection->rollBack();
      throw new CouldNotDeleteException(
        __('Could not delete the category: %1', $e->getMessage())
      );
    }
    return true;
  }

  /**
   * @inheritDoc
   */
  public function deleteById($categoryId): bool
  {
    $category = $this->getById($categoryId);
    return $this->delete($category);
  }

  /**
   * @inheritDoc
   */
  // public function getList(?SearchCriteriaInterface $searchCriteria = null): SearchResultsInterface
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
  //     $categoryData = $this->dataCategoryFactory->create();
  //     $categoryData->setCategoryId($model->getId());
  //     $categoryData->setCategoryName($model->getCategoryName());
  //     $categoryData->setCategoryDescription($model->getCategoryDescription());
  //     $categoryData->setCategoryStatus($model->getCategoryStatus());
  //     $categoryData->setCreatedAt($model->getCreatedAt());
  //     $categoryData->setUpdatedAt($model->getUpdatedAt());
  //     $categoryData->setParentIds($model->getParentIds());
  //     $categoryData->setChildIds($model->getChildIds());

  //     // جلب الـ news IDs من الجدول المنفصل
  //     $newsIds = $this->getNewsIdsFromPivotTable($model->getId());
  //     $categoryData->setNewsIds($newsIds);

  //     $items[] = $categoryData;
  //   }

  //   $searchResults->setItems($items);
  //   $searchResults->setTotalCount($collection->getSize());

  //   return $searchResults;
  // }
  /**
   * @inheritDoc
   */
  public function getList(?SearchCriteriaInterface $searchCriteria = null): SearchResultsInterface
  {
    $collection = $this->collectionFactory->create();

    if ($searchCriteria === null) {
      $searchCriteria = $this->searchCriteriaBuilder->create();
    }

    $this->collectionProcessor->process($searchCriteria, $collection);

    $searchResults = $this->searchResultsFactory->create();
    $searchResults->setSearchCriteria($searchCriteria);

    $items = [];
    foreach ($collection->getItems() as $model) {
      // إنشاء Data Object وتمرير البيانات عند الإنشاء
      $categoryData = $this->dataCategoryFactory->create(['data' => $model->getData()]);

      // جلب علاقات الأخبار المرتبطة
      $newsIds = $this->getNewsIdsFromPivotTable($model->getId());
      $categoryData->setNewsIds($newsIds);

      $items[] = $categoryData;
    }

    $searchResults->setItems($items);
    $searchResults->setTotalCount($collection->getSize());

    return $searchResults;
  }


  /**
   * @inheritDoc
   */
  public function getChildren($categoryId)
  {
    $category = $this->getById($categoryId);
    $model = $this->categoryFactory->create();
    $this->resource->load($model, $categoryId);

    $children = $model->getChildrenCategories();

    $searchResults = $this->searchResultsFactory->create();

    // Convert models to data objects
    $items = [];
    foreach ($children->getItems() as $childModel) {
      $categoryData = $this->dataCategoryFactory->create();
      $categoryData->setCategoryId($childModel->getId());
      $categoryData->setCategoryName($childModel->getCategoryName());
      $categoryData->setCategoryDescription($childModel->getCategoryDescription());
      $categoryData->setCategoryStatus($childModel->getCategoryStatus());
      $categoryData->setCreatedAt($childModel->getCreatedAt());
      $categoryData->setUpdatedAt($childModel->getUpdatedAt());
      $categoryData->setParentIds($childModel->getParentIds());
      $categoryData->setChildIds($childModel->getChildIds());

      // جلب الـ news IDs من الجدول المنفصل
      $newsIds = $this->getNewsIdsFromPivotTable($childModel->getId());
      $categoryData->setNewsIds($newsIds);

      $items[] = $categoryData;
    }

    $searchResults->setItems($items);
    $searchResults->setTotalCount($children->getSize());

    return $searchResults;
  }

  /**
   * @inheritDoc
   */
  public function getParents($categoryId)
  {
    $category = $this->getById($categoryId);
    $model = $this->categoryFactory->create();
    $this->resource->load($model, $categoryId);

    $parents = $model->getParentCategories();

    $searchResults = $this->searchResultsFactory->create();

    // Convert models to data objects
    $items = [];
    foreach ($parents as $parentModel) {
      $categoryData = $this->dataCategoryFactory->create();
      $categoryData->setCategoryId($parentModel->getId());
      $categoryData->setCategoryName($parentModel->getCategoryName());
      $categoryData->setCategoryDescription($parentModel->getCategoryDescription());
      $categoryData->setCategoryStatus($parentModel->getCategoryStatus());
      $categoryData->setCreatedAt($parentModel->getCreatedAt());
      $categoryData->setUpdatedAt($parentModel->getUpdatedAt());
      $categoryData->setParentIds($parentModel->getParentIds());
      $categoryData->setChildIds($parentModel->getChildIds());

      // جلب الـ news IDs من الجدول المنفصل
      $newsIds = $this->getNewsIdsFromPivotTable($parentModel->getId());
      $categoryData->setNewsIds($newsIds);

      $items[] = $categoryData;
    }

    $searchResults->setItems($items);
    $searchResults->setTotalCount(count($parents));

    return $searchResults;
  }

  /**
   * @inheritDoc
   */
  public function getNews($categoryId)
  {
    // This would typically use a different repository for news items
    // For now, we'll return an empty result
    return $this->searchResultsFactory->create();
  }

  /**
   * @inheritDoc
   */
  public function addParent($categoryId, $parentId)
  {
    $category = $this->getById($categoryId);
    $parent = $this->getById($parentId);

    $parentIds = $category->getParentIds();
    if (!in_array($parentId, $parentIds)) {
      $parentIds[] = $parentId;
      $category->setParentIds($parentIds);
      $this->save($category);
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function removeParent($categoryId, $parentId)
  {
    $category = $this->getById($categoryId);
    $parentIds = $category->getParentIds();

    if (($key = array_search($parentId, $parentIds)) !== false) {
      unset($parentIds[$key]);
      $category->setParentIds(array_values($parentIds));
      $this->save($category);
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function update($categoryId, CategoryInterface $category): CategoryInterface
  {
    try {
      $existingCategory = $this->getById($categoryId);

      // Update fields
      $existingCategory->setCategoryName($category->getCategoryName());
      $existingCategory->setCategoryDescription($category->getCategoryDescription());
      $existingCategory->setCategoryStatus($category->getCategoryStatus());
      $existingCategory->setParentIds($category->getParentIds());
      $existingCategory->setChildIds($category->getChildIds());
      $existingCategory->setNewsIds($category->getNewsIds());

      return $this->save($existingCategory);
    } catch (\Exception $e) {
      throw new CouldNotSaveException(__($e->getMessage()));
    }
  }

  /**
   * @inheritDoc
   */
  public function validate(CategoryInterface $category)
  {
    // Basic validation - can be extended as needed
    if (empty($category->getCategoryName())) {
      throw new \Magento\Framework\Exception\ValidatorException(__('Category name is required'));
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function exists($categoryId)
  {
    try {
      $this->getById($categoryId);
      return true;
    } catch (NoSuchEntityException $e) {
      return false;
    }
  }

  /**
   * @inheritDoc
   */
  public function getById($categoryId): CategoryInterface
  {
    $category = $this->categoryFactory->create();
    $this->resource->load($category, $categoryId);

    if (!$category->getId()) {
      throw new NoSuchEntityException(
        __('Category with id "%1" does not exist.', $categoryId)
      );
    }

    return $category;
  }
}
