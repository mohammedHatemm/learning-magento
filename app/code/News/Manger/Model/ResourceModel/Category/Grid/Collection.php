<?php

namespace News\Manger\Model\ResourceModel\Category\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Search\AggregationInterface;
use News\Manger\Model\ResourceModel\Category\Collection as CategoryCollection;

class Collection extends CategoryCollection implements SearchResultInterface
{
  protected $aggregations;

  /**
   * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
   * @param \Magento\Framework\Event\ManagerInterface $eventManager
   * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
   * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
   */
  public function __construct(
    \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
    \Psr\Log\LoggerInterface $logger,
    \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
    \Magento\Framework\Event\ManagerInterface $eventManager,
    \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
    \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
  ) {
    parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
  }

  /**
   * Initialize resource model
   */
  protected function _construct()
  {
    $this->_init('News\Manger\Model\Category', 'News\Manger\Model\ResourceModel\Category');
    $this->_eventPrefix = 'news_manger_category_grid_collection';
    $this->_eventObject = 'category_grid_collection';
  }

  /**
   * Initialize select with parent information
   *
   * @return $this
   */
  protected function _initSelect()
  {
    parent::_initSelect();

    // إضافة parent name بطريقة مختلفة
    $this->getSelect()->columns([
      'parent_name' => new \Zend_Db_Expr(
        'CASE
                WHEN main_table.parent_ids IS NULL OR main_table.parent_ids = "[]" OR main_table.parent_ids = ""
                THEN "Root Category"
                ELSE "Has Parents"
            END'
      )
    ]);

    return $this;
  }

  /**
   * Add field to filter - handle parent name filtering
   *
   * @param array|string $field
   * @param string|int|array|null $condition
   * @return $this
   */
  public function addFieldToFilter($field, $condition = null)
  {
    if ($field === 'parent_name') {
      if (isset($condition['like'])) {
        $this->getSelect()->where('parent.category_name LIKE ?', $condition['like']);
      } elseif (isset($condition['eq'])) {
        $this->getSelect()->where('parent.category_name = ?', $condition['eq']);
      }
      return $this;
    }

    return parent::addFieldToFilter($field, $condition);
  }

  /**
   * Add order for grid
   *
   * @param string $field
   * @param string $direction
   * @return $this
   */
  public function setOrder($field, $direction = self::SORT_ORDER_DESC)
  {
    if ($field === 'parent_name') {
      $this->getSelect()->order('parent.category_name ' . $direction);
    } else {
      parent::setOrder($field, $direction);
    }
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
   * Get search criteria
   *
   * @return \Magento\Framework\Api\Search\SearchCriteriaInterface|null
   */
  public function getSearchCriteria()
  {
    return null;
  }

  /**
   * Set search criteria
   *
   * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
   * @return $this
   */
  public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
  {
    return $this;
  }

  /**
   * Get total count
   *
   * @return int
   */
  public function getTotalCount()
  {
    return $this->getSize();
  }

  /**
   * Set total count
   *
   * @param int $totalCount
   * @return $this
   */
  public function setTotalCount($totalCount)
  {
    return $this;
  }

  /**
   * Set items list
   *
   * @param \Magento\Framework\Api\Search\DocumentInterface[] $items
   * @return $this
   */
  public function setItems(array $items = null)
  {
    return $this;
  }

  /**
   * Add custom filter for category status
   *
   * @param int|array $status
   * @return $this
   */
  public function addStatusFilter($status)
  {
    $this->addFieldToFilter('category_status', $status);
    return $this;
  }

  /**
   * Add filter for root categories
   *
   * @param bool $isRoot
   * @return $this
   */
  public function addRootFilter($isRoot = true)
  {
    $condition = $isRoot ? ['null' => true] : ['notnull' => true];
    $this->addFieldToFilter('parent_id', $condition);
    return $this;
  }

  /**
   * Add level filter
   *
   * @param int $level
   * @return $this
   */
  public function addLevelFilter($level)
  {
    // Note: This requires a custom SQL as level is calculated
    $this->getSelect()->where('main_table.level = ?', $level);
    return $this;
  }
}
