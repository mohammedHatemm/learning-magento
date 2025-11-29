<?php

namespace News\Manger\Model\ResourceModel\Category;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
  protected $_idFieldName = 'category_id';
  protected $_eventPrefix = 'news_manger_category_collection';
  protected $_eventObject = 'category_collection';

  /**
   * Define the resource model & the model.
   *
   * @return void
   */
  protected function _construct()
  {
    $this->_init('News\Manger\Model\Category', 'News\Manger\Model\ResourceModel\Category');
  }

  /**
   * Get options array for select fields
   *
   * @return array
   */
  public function toOptionArray()
  {
    $options = [];
    $options[] = ['value' => '', 'label' => __('No Parent (Root Category)')];

    foreach ($this->getItems() as $item) {
      $options[] = [
        'value' => $item->getId(),
        'label' => $item->getCategoryName() ?: __('Category #%1', $item->getId())
      ];
    }

    return $options;
  }

  /**
   * Get options hash for select fields
   *
   * @return array
   */
  public function toOptionHash()
  {
    $options = [];
    $options[''] = __('No Parent (Root Category)');

    foreach ($this->getItems() as $item) {
      $options[$item->getId()] = $item->getCategoryName() ?: __('Category #%1', $item->getId());
    }

    return $options;
  }

  /**
   * Add active filter
   *
   * @return $this
   */
  public function addActiveFilter()
  {
    $this->addFieldToFilter('category_status', 1);
    return $this;
  }

  /**
   * Add name filter
   *
   * @param string $name
   * @return $this
   */
  public function addNameFilter($name)
  {
    $this->addFieldToFilter('category_name', ['like' => '%' . $name . '%']);
    return $this;
  }
}
