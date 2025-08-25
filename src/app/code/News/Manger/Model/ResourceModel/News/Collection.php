<?php

namespace News\Manger\Model\ResourceModel\News;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use News\Manger\Model\News as NewsModel;
use News\Manger\Model\ResourceModel\News as NewsResourceModel;


class Collection extends AbstractCollection
{
  protected function _construct()
  {
    $this->_init(
      NewsModel::class,
      NewsResourceModel::class
    );
  }
}
