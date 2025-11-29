<?php

namespace News\Manger\Model\ResourceModel\News\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
  protected function _initSelect()
  {
    parent::_initSelect();

    // $this->getSelect()->joinLeft(
    //   ['nnc' => 'news_news_category'],
    //   'main_table.news_id = nnc.news_id',
    //   []
    // )->joinLeft(
    //   ['category' => 'news_category'],
    //   'nnc.category_id = category.category_id',
    //   ['category_name' => 'category.category_name']
    // )->joinLeft(
    //   ['parent' => 'news_category'],
    //   'category.parent_id = parent.category_id',
    //   ['parent_name' => 'parent.category_name']
    // );

    return $this;
  }
}
