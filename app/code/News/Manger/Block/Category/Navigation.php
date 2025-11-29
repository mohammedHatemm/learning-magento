<?php

namespace News\Manger\Block\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use News\Manger\Model\CategoryRepository;
use News\Manger\Model\CategoryFactory;
use News\Manger\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

class Navigation extends Template
{
  /**
   * @var CategoryRepository
   */
  protected $categoryRepository;

  /**
   * @var CategoryFactory
   */
  protected $categoryFactory;

  /**
   * @var CategoryCollectionFactory
   */
  protected $categoryCollectionFactory;

  /**
   * @var SearchCriteriaBuilder
   */
  protected $searchCriteriaBuilder;

  /**
   * @var FilterBuilder
   */
  protected $filterBuilder;

  /**
   * @var FilterGroupBuilder
   */
  protected $filterGroupBuilder;

  /**
   * @param Context $context
   * @param CategoryRepository $categoryRepository
   * @param CategoryFactory $categoryFactory
   * @param CategoryCollectionFactory $categoryCollectionFactory
   * @param SearchCriteriaBuilder $searchCriteriaBuilder
   * @param FilterBuilder $filterBuilder
   * @param FilterGroupBuilder $filterGroupBuilder
   * @param array $data
   */
  public function __construct(
    Context $context,
    CategoryRepository $categoryRepository,
    CategoryFactory $categoryFactory,
    CategoryCollectionFactory $categoryCollectionFactory,
    SearchCriteriaBuilder $searchCriteriaBuilder,
    FilterBuilder $filterBuilder,
    FilterGroupBuilder $filterGroupBuilder,
    array $data = []
  ) {
    $this->categoryRepository = $categoryRepository;
    $this->categoryFactory = $categoryFactory;
    $this->categoryCollectionFactory = $categoryCollectionFactory;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->filterBuilder = $filterBuilder;
    $this->filterGroupBuilder = $filterGroupBuilder;
    parent::__construct($context, $data);
  }

  /**
   * Get root categories using direct collection method
   *
   * @return \News\Manger\Model\Category[]
   */
  public function getRootCategories()
  {
    try {
      $collection = $this->categoryCollectionFactory->create();

      $collection->addFieldToFilter('category_status', 1);

      $connection = $collection->getConnection();
      $parentIdsField = $connection->quoteIdentifier('parent_ids');
      $collection->getSelect()->where(
        "(
                    {$parentIdsField} IS NULL OR
                    {$parentIdsField} = '' OR
                    {$parentIdsField} = '[]' OR
                    {$parentIdsField} = 'null' OR
                    {$parentIdsField} = '0' OR
                    JSON_LENGTH({$parentIdsField}) = 0
                )"
      );

      $collection->setOrder('category_name', 'ASC');

      return $collection->getItems();
    } catch (\Exception $e) {
      $this->_logger->error('Error loading root categories: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get child categories using JSON_CONTAINS for accurate matching
   *
   * @param int $parentId
   * @return \News\Manger\Model\Category[]
   */
  public function getChildCategories($parentId)
  {
    try {
      $collection = $this->categoryCollectionFactory->create();

      $collection->addFieldToFilter('category_status', 1);

      $collection->getSelect()->where(
        'JSON_CONTAINS(parent_ids, ?)',
        json_encode((int)$parentId)
      );

      $collection->setOrder('category_name', 'ASC');

      return $collection->getItems();
    } catch (\Exception $e) {
      $this->_logger->error('Error loading child categories for parent ID ' . $parentId . ': ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get category URL
   *
   * @param int $categoryId
   * @return string
   */
  public function getCategoryUrl($categoryId)
  {
    return $this->getUrl('newsuser/category/view', ['id' => $categoryId]);
  }

  /**
   *
   * @param int $categoryId
   * @return bool
   */
  public function hasChildren($categoryId)
  {
    $children = $this->getChildCategories($categoryId);
    return !empty($children);
  }

  /**
   *
   * @return array
   */
  public function getCategoryTree()
  {
    $tree = [];
    $rootCategories = $this->getRootCategories();

    foreach ($rootCategories as $rootCategory) {
      $tree[] = $this->buildCategoryNode($rootCategory);
    }

    return $tree;
  }

  /**
   *
   * @param \News\Manger\Model\Category $category
   * @return array
   */
  private function buildCategoryNode($category)
  {
    $children = $this->getChildCategories($category->getId());
    $childNodes = [];

    foreach ($children as $child) {
      $childNodes[] = $this->buildCategoryNode($child);
    }

    return [
      'id' => $category->getId(),
      'name' => $category->getCategoryName(),
      'url' => $this->getCategoryUrl($category->getId()),
      'children' => $childNodes,
      'has_children' => !empty($childNodes)
    ];
  }
}
