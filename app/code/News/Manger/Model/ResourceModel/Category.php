<?php

namespace News\Manger\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Category extends AbstractDb
{
  protected function _construct()
  {
    $this->_init('news_category', 'category_id');
  }
}
