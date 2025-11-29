<?php

namespace News\Manger\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use News\Manger\Api\Data\NewsInterface;

class News extends AbstractExtensibleObject implements NewsInterface
{
  /**
   * Get news ID
   * @return int|null
   */
  public function getNewsId()
  {
    return $this->_get(self::NEWS_ID);
  }

  /**
   * Set news ID
   * @param int $newsId
   * @return $this
   */
  public function setNewsId($id)
  {
    return $this->setData(self::NEWS_ID, $id);
  }

  /**
   * Get news title
   * @return string|null
   */
  public function getNewsTitle()
  {
    return $this->_get(self::TITLE);
  }

  /**
   * Set news title
   * @param string $title
   * @return $this
   */
  public function setNewsTitle($title)
  {
    return $this->setData(self::TITLE, $title);
  }

  /**
   * Get news content
   * @return string|null
   */
  public function getNewsContent()
  {
    return $this->_get(self::CONTENT);
  }

  /**
   * Set news content
   * @param string $content
   * @return $this
   */
  public function setNewsContent($content)
  {
    return $this->setData(self::CONTENT, $content);
  }

  /**
   * Get news status
   * @return int|null
   */
  public function getNewsStatus()
  {
    return $this->_get(self::STATUS);
  }

  /**
   * Set news status
   * @param int $status
   * @return $this
   */
  public function setNewsStatus($status)
  {
    return $this->setData(self::STATUS, $status);
  }

  /**
   * Get created at
   * @return string|null
   */
  public function getCreatedAt()
  {
    return $this->_get(self::CREATED_AT);
  }

  /**
   * Set created at
   * @param string $createdAt
   * @return $this
   */
  public function setCreatedAt($createdAt)
  {
    return $this->setData(self::CREATED_AT, $createdAt);
  }

  /**
   * Get updated at
   * @return string|null
   */
  public function getUpdatedAt()
  {
    return $this->_get(self::UPDATED_AT);
  }

  /**
   * Set updated at
   * @param string $updatedAt
   * @return $this
   */
  public function setUpdatedAt($updatedAt)
  {
    return $this->setData(self::UPDATED_AT, $updatedAt);
  }

  /**
   * Get category IDs
   * @return int[]|null
   */
  public function getCategoryIds()
  {
    $categoryIds = $this->_get(self::CATEGORY_IDS);
    return is_array($categoryIds) ? $categoryIds : [];
  }

  /**
   * Set category IDs
   * @param int[] $categoryIds
   * @return $this
   */
  public function setCategoryIds(array $categoryIds)
  {
    return $this->setData(self::CATEGORY_IDS, $categoryIds);
  }
}
