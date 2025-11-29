<?php

namespace News\Manger\Ui\DataProvider\News;

use Magento\Ui\DataProvider\AbstractDataProvider;
use News\Manger\Model\ResourceModel\News\Grid\Collection;
use News\Manger\Model\CategoryFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class NewsGridDataProvider extends AbstractDataProvider
{
  protected $collection;
  private $logger;
  protected array $loadedData = [];
  protected $categoryFactory;
  protected $resourceConnection;

  public function __construct(
    $name,
    $primaryFieldName,
    $requestFieldName,
    Collection $collection,
    LoggerInterface $logger,
    CategoryFactory $categoryFactory,
    ResourceConnection $resourceConnection,
    array $meta = [],
    array $data = []
  ) {
    $this->collection = $collection;
    $this->logger = $logger;
    $this->categoryFactory = $categoryFactory;
    $this->resourceConnection = $resourceConnection;
    parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
  }

  public function getData()
  {
    if (!empty($this->loadedData)) {
      return [
        'totalRecords' => $this->collection->getSize(),
        'items' => array_values($this->loadedData),
      ];
    }

    foreach ($this->collection->getItems() as $item) {
      $data = $item->getData();

      // جلب الفئات المرتبطة بالخبر
      $categoryIds = $this->getCategoryIdsForNews($item->getId());
      $this->logger->info('Category IDs for news ' . $item->getId() . ': ' . json_encode($categoryIds));
      $categoryPaths = [];
      foreach ($categoryIds as $categoryId) {
        $category = $this->categoryFactory->create()->load($categoryId);
        if ($category->getId()) {
          $categoryPaths[] = $category->getPath();
        }
      }

      // تعيين المسارات الكاملة أو قيمة افتراضية
      $data['category_paths'] = !empty($categoryPaths) ? implode('<br>', $categoryPaths) : __('No Categories');
      $this->logger->info('Category Paths for news ' . $item->getId() . ': ' . json_encode($categoryPaths));
      $this->loadedData[$item->getId()] = $data;
    }

    return [
      'totalRecords' => $this->collection->getSize(),
      'items' => array_values($this->loadedData),
    ];
  }

  private function getCategoryIdsForNews($newsId)
  {
    try {
      $connection = $this->resourceConnection->getConnection();
      $select = $connection->select()
        ->from($this->resourceConnection->getTableName('news_news_category'), ['category_id'])
        ->where('news_id = ?', $newsId);
      return $connection->fetchCol($select);
    } catch (\Exception $e) {
      $this->logger->error('Error getting category IDs for news: ' . $e->getMessage());
      return [];
    }
  }
}
