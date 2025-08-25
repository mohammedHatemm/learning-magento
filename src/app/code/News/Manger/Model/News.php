<?php

namespace News\Manger\Model;


use Magento\Framework\Model\AbstractModel;
use News\Manger\Model\ResourceModel\News as NewsResourceModel;


class News extends AbstractModel
{
  const NEWS_ID = 'news_id';
  const NEWS_TITLE = 'news_title';
  const NEWS_CONTENT = 'news_content';
  const NEWS_STATUS = 'news_status';
  const NEWS_IMAGE = 'news_image';
  const CREATED_AT = 'created_at';
  const UPDATED_AT = 'updated_at';


  protected $_eventPrefix = 'news_news';
  protected $_eventObject = 'news_news';
  protected $_idFieldName = 'news_id';

  protected function _construct()
  {
    $this->_init(NewsResourceModel::class);
  }


  public function getNewsId()
  {
    return $this->getData(self::NEWS_ID);
  }

  public function getNewsTitle()
  {
    return $this->getData(self::NEWS_TITLE);
  }

  public function getNewsContent()
  {
    return $this->getData(self::NEWS_CONTENT);
  }

  public function getNewsStatus()
  {
    return $this->getData(self::NEWS_STATUS);
  }

  public function getNewsImage()
  {
    return $this->getData(self::NEWS_IMAGE);
  }

  public function getCreatedAt()
  {
    return $this->getData(self::CREATED_AT);
  }

  public function getUpdatedAt()
  {
    return $this->getData(self::UPDATED_AT);
  }

  public function setNewsId($newsId)
  {
    return $this->setData(self::NEWS_ID, $newsId);
  }

  public function setNewsTitle($newsTitle)
  {
    return $this->setData(self::NEWS_TITLE, $newsTitle);
  }

  public function setNewsContent($newsContent)
  {
    return $this->setData(self::NEWS_CONTENT, $newsContent);
  }

  public function setNewsStatus($newsStatus)
  {
    return $this->setData(self::NEWS_STATUS, $newsStatus);
  }

  public function setNewsImage($newsImage)
  {
    return $this->setData(self::NEWS_IMAGE, $newsImage);
  }

  public function setCreatedAt($createdAt)
  {
    return $this->setData(self::CREATED_AT, $createdAt);
  }

  public function setUpdatedAt($updatedAt)
  {
    return $this->setData(self::UPDATED_AT, $updatedAt);
  }
}
