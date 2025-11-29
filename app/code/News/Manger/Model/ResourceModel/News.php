<?php

namespace News\Manger\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class News extends AbstractDb
{
  protected function _construct()
  {
    $this->_init('news_news', 'news_id');
  }

  public function getCategoryIds($newsId)
  {
    $connection = $this->getConnection();
    $select = $connection->select()
      ->from($this->getTable('news_news_category'), 'category_id')
      ->where('news_id = ?', $newsId);

    return $connection->fetchCol($select);
  }
}
