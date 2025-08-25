<?php

namespace News\Manger\Model\ResourceModel\Category\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use News\Manger\Model\Category as CategoryModel;
use News\Manger\Model\ResourceModel\Category as CategoryResourceModel;

class Collection extends AbstractCollection implements SearchResultInterface
{
  /**
   * @var \Magento\Framework\Api\SearchCriteriaInterface
   */
  protected $searchCriteria;

  /**
   * @var \Magento\Framework\Api\Search\AggregationInterface
   */
  protected $aggregations;

  protected function _construct()
  {
    $this->_init(
      CategoryModel::class,
      CategoryResourceModel::class
    );
    $this->_map['fields']['category_id'] = 'main_table.category_id';
  }

  /**
   * Initialize collection
   *
   * @return void
   */
  protected function _initSelect()
  {
    parent::_initSelect();

    // Join hierarchy table to get parent information
    $this->getSelect()->joinLeft(
      ['hierarchy' => $this->getTable('news_category_hierarchy')],
      'main_table.category_id = hierarchy.category_id',
      ['parent_id']
    );

    // Join parent category name
    $this->getSelect()->joinLeft(
      ['parent_cat' => $this->getTable('news_category')],
      'hierarchy.parent_id = parent_cat.category_id',
      ['parent_name' => 'parent_cat.category_name']
    );
  }

  /**
   * Get search criteria.
   *
   * @return \Magento\Framework\Api\SearchCriteriaInterface|null
   */
  public function getSearchCriteria()
  {
    return $this->searchCriteria;
  }

  /**
   * Set search criteria.
   *
   * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
   * @return $this
   */
  public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
  {
    $this->searchCriteria = $searchCriteria;
    return $this;
  }

  /**
   * Get total count.
   *
   * @return int
   */
  public function getTotalCount()
  {
    return $this->getSize();
  }

  /**
   * Set total count.
   *
   * @param int $totalCount
   * @return $this
   */
  public function setTotalCount($totalCount)
  {
    return $this;
  }

  /**
   * Set items list.
   *
   * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
   * @return $this
   */
  public function setItems(array $items = null)
  {
    return $this;
  }

  /**
   * Get aggregations
   *
   * @return AggregationInterface
   */
  public function getAggregations()
  {
    return $this->aggregations;
  }

  /**
   * Set aggregations
   *
   * @param AggregationInterface $aggregations
   * @return $this
   */
  public function setAggregations($aggregations)
  {
    $this->aggregations = $aggregations;
    return $this;
  }

  /**
   * Add hierarchy data to collection
   *
   * @return $this
   */
  public function addHierarchyData()
  {
    // Already added in _initSelect
    return $this;
  }

  /**
   * Filter by parent category
   *
   * @param int|null $parentId
   * @return $this
   */
  public function addParentFilter($parentId = null)
  {
    if ($parentId === null) {
      $this->getSelect()->where('hierarchy.parent_id IS NULL');
    } else {
      $this->getSelect()->where('hierarchy.parent_id = ?', $parentId);
    }

    return $this;
  }

  /**
   * Filter only root categories (no parent)
   *
   * @return $this
   */
  public function addRootCategoriesFilter()
  {
    return $this->addParentFilter(null);
  }

  /**
   * Add active status filter
   *
   * @return $this
   */
  public function addActiveFilter()
  {
    $this->addFieldToFilter('main_table.category_status', 1);
    return $this;
  }

  /**
   * Add field to filter
   *
   * @param string|array $field
   * @param null|string|array $condition
   * @return $this
   */
  public function addFieldToFilter($field, $condition = null)
  {
    if ($field === 'parent_name') {
      $this->getSelect()->where('parent_cat.category_name LIKE ?', '%' . $condition['like'] . '%');
      return $this;
    }

    // Handle main table fields
    if (is_string($field) && strpos($field, '.') === false) {
      $field = 'main_table.' . $field;
    }

    return parent::addFieldToFilter($field, $condition);
  }

  /**
   * Get options array for dropdown
   *
   * @param bool $withEmpty
   * @param int|null $excludeId
   * @return array
   */
  public function getOptionsArray($withEmpty = true, $excludeId = null)
  {
    $this->addActiveFilter();
    $this->setOrder('category_name', 'ASC');

    if ($excludeId) {
      $this->addFieldToFilter('main_table.category_id', ['neq' => $excludeId]);
    }

    $options = [];

    if ($withEmpty) {
      $options[''] = __('-- Select Parent Category --');
    }

    foreach ($this as $category) {
      $options[$category->getId()] = $category->getCategoryName();
    }

    return $options;
  }
}
