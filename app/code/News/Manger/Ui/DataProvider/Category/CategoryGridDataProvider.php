<?php

namespace News\Manger\Ui\DataProvider\Category;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;
use News\Manger\Model\ResourceModel\Category\Grid\CollectionFactory as GridCollectionFactory;
use News\Manger\Model\CategoryFactory;
use Magento\Framework\Phrase;

class CategoryGridDataProvider extends AbstractDataProvider
{
  protected $loadedData;
  private $logger;
  private $categoryFactory;
  private $gridCollectionFactory;

  public function __construct(
    $name,
    $primaryFieldName,
    $requestFieldName,
    GridCollectionFactory $gridCollectionFactory,
    LoggerInterface $logger,
    CategoryFactory $categoryFactory,
    array $meta = [],
    array $data = []
  ) {
    $this->gridCollectionFactory = $gridCollectionFactory;
    $this->collection = $gridCollectionFactory->create();
    $this->logger = $logger;
    $this->categoryFactory = $categoryFactory;
    parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
  }

  public function getData()
  {
    if (isset($this->loadedData)) {
      return $this->loadedData;
    }

    try {
      $this->logger->debug('Collection Class: ' . get_class($this->collection));
      $this->logger->debug('Collection SQL: ' . $this->collection->getSelect()->__toString());

      $items = $this->collection->getItems();
      $this->logger->debug('Collection Size: ' . $this->collection->getSize());

      $formattedItems = [];

      foreach ($items as $item) {
        $data = $item->getData();
        $categoryModel = $this->categoryFactory->create();
        $categoryModel->setData($data); // Use collection data directly

        // Status formatting
        $data['category_status'] = $data['category_status'] == 1 ? __('Enabled') : __('Disabled');

        // Level information
        $data['level'] = $categoryModel->getLevel();

        // Path information
        $formattedPaths = $categoryModel->getFormattedPaths(' > ');
        if (!empty($formattedPaths)) {
          // ✅ إصلاح الخطأ هنا - استخدام $data بدلاً من $item و $categoryModel بدلاً من $category
          $data['full_path_array'] = $categoryModel->getFormattedPathsForGridHtml();
          $data['primary_path'] = $formattedPaths[0];
          $data['all_paths'] = json_encode($formattedPaths);
        } else {
          $categoryName = $categoryModel->getCategoryName();
          $data['full_path_array'] = $categoryName;
          $data['primary_path'] = $categoryName;
          $data['all_paths'] = json_encode([$categoryName]);
        }

        // Root and children information
        $data['is_root'] = $categoryModel->isRoot() ? __('Yes') : __('No');
        $data['has_children'] = $categoryModel->hasChildren() ? __('Yes') : __('No');
        $data['children_count'] = $categoryModel->getChildrenCount();

        // Breadcrumb information
        $breadcrumbPaths = $categoryModel->getBreadcrumbPaths();
        $data['breadcrumb_info'] = $this->formatBreadcrumbInfo($breadcrumbPaths);

        // Parent information
        $parentIds = $categoryModel->getParentIds();
        if (!empty($parentIds)) {
          $parentNames = [];
          foreach ($parentIds as $parentId) {
            $parent = $this->categoryFactory->create()->load($parentId);
            if ($parent->getId()) {
              $parentNames[] = $parent->getCategoryName();
            }
          }
          $data['direct_parents'] = implode(', ', $parentNames);
        } else {
          $data['direct_parents'] = __('Root Category');
        }

        // Formatted name with indentation
        $data['formatted_name'] = $categoryModel->getFormattedName();

        // Full path for display
        $data['full_path'] = $categoryModel->getPath();

        $formattedItems[] = $data;
      }

      $this->loadedData = [
        'totalRecords' => $this->collection->getSize(),
        'items' => $formattedItems
      ];

      $this->logger->debug('Final LoadedData: ' . json_encode($this->loadedData));
    } catch (\Exception $e) {
      $this->logger->error('CategoryGridDataProvider Error: ' . $e->getMessage());
      $this->logger->error('Stack Trace: ' . $e->getTraceAsString());
      $this->loadedData = [
        'totalRecords' => 0,
        'items' => []
      ];
    }

    return $this->loadedData;
  }

  private function formatBreadcrumbInfo($breadcrumbPaths)
  {
    $formattedInfo = [];
    foreach ($breadcrumbPaths as $pathIndex => $breadcrumb) {
      $pathInfo = [];
      foreach ($breadcrumb as $item) {
        $pathInfo[] = sprintf('%s (L%d)', $item['name'], $item['level']);
      }
      $formattedInfo[] = implode(' > ', $pathInfo);
    }
    return implode('<br><strong>Path ' . (count($formattedInfo) + 1) . ':</strong> ', $formattedInfo);
  }

  public function getMeta()
  {
    $meta = parent::getMeta();
    $meta['news_category_columns']['children']['level']['arguments']['data']['config']['visible'] = true;
    $meta['news_category_columns']['children']['full_path_array']['arguments']['data']['config']['visible'] = true;
    $meta['news_category_columns']['children']['direct_parents']['arguments']['data']['config']['visible'] = true;
    $meta['news_category_columns']['children']['children_count']['arguments']['data']['config']['visible'] = true;
    return $meta;
  }

  public function getCategoryTreeForJs()
  {
    $categoryModel = $this->categoryFactory->create();
    return $categoryModel->getCategoryTreeForJs(true);
  }

  public function getCategoryStats()
  {
    $stats = [];
    $items = $this->collection->getItems();
    foreach ($items as $item) {
      $categoryModel = $this->categoryFactory->create()->setData($item->getData());
      $categoryStats = $categoryModel->getCategoryStats();
      $categoryStats['formatted_paths'] = $categoryModel->getFormattedPaths();
      $stats[] = $categoryStats;
    }
    return $stats;
  }

  public function getRootCategoriesData()
  {
    $categoryModel = $this->categoryFactory->create();
    $rootCategories = $categoryModel->getRootCategories(true);
    $rootData = [];
    foreach ($rootCategories as $root) {
      $rootData[] = [
        'id' => $root->getId(),
        'name' => $root->getCategoryName(),
        'tree' => $root->getChildrenTree()
      ];
    }
    return $rootData;
  }

  public function getCategoriesWithPaths()
  {
    $items = $this->collection->getItems();
    $categoriesWithPaths = [];
    foreach ($items as $item) {
      $categoryModel = $this->categoryFactory->create()->setData($item->getData());
      $categoriesWithPaths[] = [
        'id' => $categoryModel->getId(),
        'name' => $categoryModel->getCategoryName(),
        'level' => $categoryModel->getLevel(),
        'is_root' => $categoryModel->isRoot(),
        'parent_ids' => $categoryModel->getParentIds(),
        'all_paths' => $categoryModel->getAllPaths(),
        'formatted_paths' => $categoryModel->getFormattedPaths(),
        'breadcrumb_paths' => $categoryModel->getBreadcrumbPaths()
      ];
    }
    return $categoriesWithPaths;
  }
}
