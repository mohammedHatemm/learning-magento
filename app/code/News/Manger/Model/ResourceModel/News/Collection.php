<?php

namespace News\Manger\Model\ResourceModel\News;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
  /**
   * @var string
   */
  protected $_idFieldName = 'news_id';

  /**
   * Define resource model
   *
   * @return void
   */
  protected function _construct()
  {
    $this->_init(
      'News\Manger\Model\News',
      'News\Manger\Model\ResourceModel\News'
    );
  }

  /**
   * Add category filter
   *
   * @param int|array $categoryId
   * @return $this
   */
  public function addCategoryFilter($categoryId)
  {
    if (is_array($categoryId)) {
      $this->getSelect()->join(
        ['nnc' => $this->getTable('news_news_category')],
        'main_table.news_id = nnc.news_id',
        []
      )->where('nnc.category_id IN (?)', $categoryId);
    } else {
      $this->getSelect()->join(
        ['nnc' => $this->getTable('news_news_category')],
        'main_table.news_id = nnc.news_id',
        []
      )->where('nnc.category_id = ?', $categoryId);
    }

    return $this;
  }

  /**
   * Add status filter
   *
   * @param int $status
   * @return $this
   */
  public function addStatusFilter($status = 1)
  {
    $this->addFieldToFilter('news_status', $status);
    return $this;
  }

  /**
   * Add date range filter
   *
   * @param string $from
   * @param string $to
   * @return $this
   */
  public function addDateRangeFilter($from = null, $to = null)
  {
    if ($from) {
      $this->addFieldToFilter('created_at', ['gteq' => $from]);
    }
    if ($to) {
      $this->addFieldToFilter('created_at', ['lteq' => $to]);
    }
    return $this;
  }

  /**
   * Get SQL for get record count
   *
   * @return \Magento\Framework\DB\Select
   */
  public function getSelectCountSql()
  {
    $this->_renderFilters();
    $select = clone $this->getSelect();
    $select->reset(\Magento\Framework\DB\Select::ORDER);
    $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
    $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
    $select->reset(\Magento\Framework\DB\Select::COLUMNS);
    $select->columns('COUNT(DISTINCT main_table.news_id)');
    return $select;
  }

  /**
   * Load data with category information
   *
   * @param bool $printQuery
   * @param bool $logQuery
   * @return $this
   */
  public function load($printQuery = false, $logQuery = false)
  {
    if ($this->isLoaded()) {
      return $this;
    }

    parent::load($printQuery, $logQuery);

    // Load category IDs for each news item
    if ($this->getSize()) {
      $this->loadCategoryIds();
    }

    return $this;
  }

  /**
   * Load category IDs for all news items in collection
   *
   * @return $this
   */
  protected function loadCategoryIds()
  {
    $newsIds = $this->getColumnValues('news_id');
    if (empty($newsIds)) {
      return $this;
    }

    $connection = $this->getConnection();
    $select = $connection->select()
      ->from($this->getTable('news_news_category'), ['news_id', 'category_id'])
      ->where('news_id IN (?)', $newsIds);

    $categoryData = $connection->fetchAll($select);
    $categoryMap = [];

    foreach ($categoryData as $row) {
      $newsId = $row['news_id'];
      if (!isset($categoryMap[$newsId])) {
        $categoryMap[$newsId] = [];
      }
      $categoryMap[$newsId][] = (int)$row['category_id'];
    }

    foreach ($this->_items as $item) {
      $newsId = $item->getId();
      $categoryIds = isset($categoryMap[$newsId]) ? $categoryMap[$newsId] : [];
      $item->setData('category_ids', $categoryIds);
    }

    return $this;
  }
}
