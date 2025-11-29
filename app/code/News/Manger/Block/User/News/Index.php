<?php
/* File: app/code/News/Manger/Block/User/News/Index.php */

namespace News\Manger\Block\User\News;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use News\Manger\Model\ResourceModel\News\CollectionFactory;
use News\Manger\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class Index extends Template
{
  protected $newsCollectionFactory;
  protected $categoryCollectionFactory;
  protected $resourceConnection;
  protected $logger;
  protected $request;
  protected $itemsPerPage = 5;

  public function __construct(
    Context $context,
    CollectionFactory $newsCollectionFactory,
    CategoryCollectionFactory $categoryCollectionFactory,
    ResourceConnection $resourceConnection,
    LoggerInterface $logger,
    RequestInterface $request,
    array $data = []
  ) {
    parent::__construct($context, $data);
    $this->newsCollectionFactory = $newsCollectionFactory;
    $this->categoryCollectionFactory = $categoryCollectionFactory;
    $this->resourceConnection = $resourceConnection;
    $this->logger = $logger;
    $this->request = $request;
  }

  public function getNewsCollection()
  {
    $currentPage = (int)$this->request->getParam('p', 1);
    $collection = $this->newsCollectionFactory->create();
    $collection->addFieldToFilter('news_status', 1);
    $collection->setOrder('created_at', 'DESC');

    // Apply pagination
    $collection->setPageSize($this->itemsPerPage);
    $collection->setCurPage($currentPage);

    return $collection;
  }

  public function getPagerHtml()
  {
    $collection = $this->getNewsCollection();
    $totalItems = $collection->getSize();
    $currentPage = (int)$this->request->getParam('p', 1);
    $lastPage = ceil($totalItems / $this->itemsPerPage);

    if ($lastPage <= 1) {
      return '';
    }

    $html = '<div class="pagination">';

    // Previous page link
    if ($currentPage > 1) {
      $html .= sprintf(
        '<a href="%s" class="page-prev">%s</a>',
        $this->getPagerUrl($currentPage - 1),
        __('Previous')
      );
    }

    // Page numbers
    $html .= '<div class="pages">';
    for ($i = 1; $i <= $lastPage; $i++) {
      if ($i == $currentPage) {
        $html .= sprintf('<strong class="current">%s</strong>', $i);
      } else {
        $html .= sprintf(
          '<a href="%s" class="page">%s</a>',
          $this->getPagerUrl($i),
          $i
        );
      }
    }
    $html .= '</div>';

    // Next page link
    if ($currentPage < $lastPage) {
      $html .= sprintf(
        '<a href="%s" class="page-next">%s</a>',
        $this->getPagerUrl($currentPage + 1),
        __('Next')
      );
    }

    $html .= '</div>';
    return $html;
  }

  protected function getPagerUrl($page)
  {
    return $this->getUrl('*/*/*', [
      '_current' => true,
      '_use_rewrite' => true,
      '_query' => ['p' => $page]
    ]);
  }

  /**
   *
   * @param int $newsId
   * @return array
   */
  public function getCategoriesForNews($newsId)
  {
    try {
      $connection = $this->resourceConnection->getConnection();
      $select = $connection->select()
        ->from(['nc' => $this->resourceConnection->getTableName('news_news_category')], [])
        ->joinLeft(
          ['c' => $this->resourceConnection->getTableName('news_category')],
          'nc.category_id = c.category_id',
          ['name' => 'category_name']
        )
        ->where('nc.news_id = ?', $newsId);

      return $connection->fetchCol($select);
    } catch (\Exception $e) {
      $this->logger->error('Error in getCategoriesForNews: ' . $e->getMessage());
      return [];
    }
  }

  public function getExcerpt(string $content, int $length = 150): string
  {
    return mb_substr(strip_tags($content), 0, $length) . '...';
  }

  /**
   *
   * @return array
   */
  public function getHierarchicalCategories(): array
  {
    try {
      $collection = $this->categoryCollectionFactory->create();
      $collection->addFieldToFilter('status', 1);
      $collection->setOrder('level', 'ASC');
      $collection->setOrder('sort_order', 'ASC');
      $collection->setOrder('category_name', 'ASC');

      $categories = [];
      foreach ($collection as $category) {
        $categories[] = [
          'id' => $category->getCategoryId(),
          'name' => $category->getCategoryName(),
          'parent_id' => $category->getParentId(),
          'level' => $category->getLevel(),
          'description' => $category->getDescription(),
          'sort_order' => $category->getSortOrder(),
          'url' => $this->getUrl('newsuser/category/view', ['id' => $category->getCategoryId()])
        ];
      }

      return $this->buildHierarchy($categories);
    } catch (\Exception $e) {
      $this->logger->error('Error getting hierarchical categories: ' . $e->getMessage());
      return [];
    }
  }

  /**
   *
   * @param array $categories
   * @param int $parentId
   * @return array
   */
  private function buildHierarchy(array $categories, int $parentId = 0): array
  {
    $hierarchy = [];

    foreach ($categories as $category) {
      if ($category['parent_id'] == $parentId) {
        $children = $this->buildHierarchy($categories, $category['id']);
        if (!empty($children)) {
          $category['children'] = $children;
        }
        $hierarchy[] = $category;
      }
    }

    return $hierarchy;
  }

  /**
   * جلب جميع المسارات للفئات (Full Paths)
   * @return array
   */
  // public function getCategoryPaths(): array
  // {
  //     try {
  //         $collection = $this->categoryCollectionFactory->create();
  //         $collection->addFieldToFilter('status', 1);

  //         $paths = [];
  //         foreach ($collection as $category) {
  //             // استخدام الدالة الموجودة في الموديل
  //             $breadcrumbPaths = $category->getBreadcrumbPaths();

  //             foreach ($breadcrumbPaths as $breadcrumb) {
  //                 $pathNames = array_column($breadcrumb, 'name');
  //                 $pathString = implode(' > ', $pathNames);

  //                 // تجنب التكرار
  //                 $pathKey = md5($pathString);
  //                 if (!isset($paths[$pathKey])) {
  //                     $paths[$pathKey] = [
  //                         'category_id' => $category->getCategoryId(),
  //                         'path_string' => $pathString,
  //                         'path_array' => $breadcrumb,
  //                         'level' => count($breadcrumb),
  //                         'url' => $this->getUrl('newsuser/category/view', ['id' => $category->getCategoryId()])
  //                     ];
  //                 }
  //             }
  //         }

  //         // ترتيب المسارات حسب المستوى ثم حسب الاسم
  //         usort($paths, function ($a, $b) {
  //             if ($a['level'] == $b['level']) {
  //                 return strcmp($a['path_string'], $b['path_string']);
  //             }
  //             return $a['level'] - $b['level'];
  //         });

  //         return array_values($paths);
  //     } catch (\Exception $e) {
  //         $this->logger->error('Error getting category paths: ' . $e->getMessage());
  //         return [];
  //     }
  // }




  /**
   * جلب عدد الأخبار لكل فئة
   * @param int $categoryId
   * @return int
   */
  public function getNewsCountForCategory(int $categoryId): int
  {
    try {
      $connection = $this->resourceConnection->getConnection();
      $select = $connection->select()
        ->from(['nc' => $this->resourceConnection->getTableName('news_news_category')], ['COUNT(*)'])
        ->joinLeft(
          ['n' => $this->resourceConnection->getTableName('news_news')],
          'nc.news_id = n.id',
          []
        )
        ->where('nc.category_id = ?', $categoryId)
        ->where('n.news_status = ?', 1);

      return (int)$connection->fetchOne($select);
    } catch (\Exception $e) {
      $this->logger->error('Error getting news count for category: ' . $e->getMessage());
      return 0;
    }
  }

  /**
   * تحديد ما إذا كانت الفئة تحتوي على فئات فرعية
   * @param int $categoryId
   * @return bool
   */
  // public function hasSubCategories(int $categoryId): bool
  // {
  //     try {
  //         $collection = $this->categoryCollectionFactory->create();
  //         $collection->addFieldToFilter('parent_id', $categoryId);
  //         $collection->addFieldToFilter('status', 1);

  //         return $collection->getSize() > 0;
  //     } catch (\Exception $e) {
  //         $this->logger->error('Error checking sub categories: ' . $e->getMessage());
  //         return false;
  //     }
  // }
}
