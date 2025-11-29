<?php

namespace News\Manger\Block\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use News\Manger\Model\CategoryRepository;
use News\Manger\Model\ResourceModel\News\CollectionFactory as NewsCollectionFactory;

class View extends Template
{
  /**
   * @var CategoryRepository
   */
  protected $categoryRepository;

  /**
   * @var NewsCollectionFactory
   */
  protected $newsCollectionFactory;

  /**
   * @var \News\Manger\Model\Category
   */
  protected $currentCategory;

  /**
   * @param Context $context
   * @param CategoryRepository $categoryRepository
   * @param NewsCollectionFactory $newsCollectionFactory
   * @param array $data
   */
  public function __construct(
    Context $context,
    CategoryRepository $categoryRepository,
    NewsCollectionFactory $newsCollectionFactory,
    array $data = []
  ) {
    parent::__construct($context, $data);
    $this->categoryRepository = $categoryRepository;
    $this->newsCollectionFactory = $newsCollectionFactory;
  }

  /**
   * Get category by ID
   *
   * @return \News\Manger\Model\Category|null
   */
  public function getCategory()
  {
    if ($this->currentCategory === null) {
      $categoryId = $this->getRequest()->getParam('id');

      if (!$categoryId) {
        return null;
      }

      try {
        $this->currentCategory = $this->categoryRepository->getById($categoryId);
      } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $this->currentCategory = false;
      } catch (\Exception $e) {
        $this->_logger->error('Error loading category: ' . $e->getMessage());
        $this->currentCategory = false;
      }
    }

    return $this->currentCategory ?: null;
  }

  /**
   * Get news collection for current category
   *
   * @return \News\Manger\Model\ResourceModel\News\Collection|null
   */
  public function getCategoryNews()
  {
    $category = $this->getCategory();

    if (!$category) {
      return null;
    }

    try {
      $collection = $this->newsCollectionFactory->create();

      // Add category filter
      $this->addCategoryFilterToCollection($collection, $category->getId());

      // Only active news
      $collection->addFieldToFilter('news_status', 1);

      // Order by creation date (newest first)
      $collection->setOrder('created_at', 'DESC');

      return $collection;
    } catch (\Exception $e) {
      $this->_logger->error('Error loading category news: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Add category filter to news collection
   *
   * @param \News\Manger\Model\ResourceModel\News\Collection $collection
   * @param int $categoryId
   * @return void
   */
  protected function addCategoryFilterToCollection($collection, $categoryId)
  {
    // Method 1: If you have a pivot table (news_news_category)
    $collection->getSelect()->join(
      ['nnc' => $collection->getTable('news_news_category')],
      'main_table.news_id = nnc.news_id',
      []
    )->where('nnc.category_id = ?', $categoryId);

    // Method 2: If categories are stored as JSON in news table
    // Uncomment this and comment the above if you use JSON storage
    /*
        $collection->addFieldToFilter('category_ids', ['like' => '%"' . $categoryId . '"%']);
        */
  }



  /**
   * Get page title
   *
   * @return string
   */
  public function getPageTitle()
  {
    $category = $this->getCategory();

    if ($category) {
      return $category->getCategoryName() . ' - News';
    }

    return 'Category News';
  }

  /**
   * Get breadcrumbs data
   *
   * @return array
   */
  public function getBreadcrumbsData()
  {
    $category = $this->getCategory();
    $breadcrumbs = [
      [
        'label' => __('Home'),
        'url' => $this->getUrl('')
      ],
      [
        'label' => __('News'),
        'url' => $this->getUrl('newsuser')
      ]
    ];

    if ($category) {
      // Add parent categories to breadcrumbs
      $parentCategories = $category->getParentCategories();
      foreach ($parentCategories as $parent) {
        $breadcrumbs[] = [
          'label' => $parent->getCategoryName(),
          'url' => $this->getUrl('newsuser/category/view', ['id' => $parent->getId()])
        ];
      }

      // Add current category
      $breadcrumbs[] = [
        'label' => $category->getCategoryName(),
        'url' => ''
      ];
    }

    return $breadcrumbs;
  }

  /**
   * Get related categories (siblings or children)
   *
   * @return array
   */
  public function getRelatedCategories()
  {
    $category = $this->getCategory();

    if (!$category) {
      return [];
    }

    $related = [];

    // Get child categories
    $children = $category->getChildrenCategories(true);
    foreach ($children as $child) {
      $related[] = $child;
    }

    return $related;
  }
}
