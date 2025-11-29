<?php

namespace News\Manger\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use News\Manger\Api\Data\CategoryInterface;

/**
 * Category data model
 */
class Category extends AbstractExtensibleObject implements CategoryInterface
{
  /**
   * @inheritDoc
   */
  public function getCategoryId()
  {
    return $this->_get(self::CATEGORY_ID);
  }

  /**
   * @inheritDoc
   */
  public function setCategoryId($id)
  {
    return $this->setData(self::CATEGORY_ID, $id);
  }

  /**
   * @inheritDoc
   */
  public function getCategoryName()
  {
    return $this->_get(self::NAME);
  }

  /**
   * @inheritDoc
   */
  public function setCategoryName($name)
  {
    return $this->setData(self::NAME, $name);
  }

  /**
   * @inheritDoc
   */
  public function getCategoryDescription()
  {
    return $this->_get(self::DESCRIPTION);
  }

  /**
   * @inheritDoc
   */
  public function setCategoryDescription($description)
  {
    return $this->setData(self::DESCRIPTION, $description);
  }

  /**
   * @inheritDoc
   */
  public function getCategoryStatus()
  {
    return $this->_get(self::STATUS);
  }

  /**
   * @inheritDoc
   */
  public function setCategoryStatus($status)
  {
    return $this->setData(self::STATUS, $status);
  }

  /**
   * @inheritDoc
   */
  public function getCreatedAt()
  {
    return $this->_get(self::CREATED_AT);
  }

  /**
   * @inheritDoc
   */
  public function setCreatedAt($createdAt)
  {
    return $this->setData(self::CREATED_AT, $createdAt);
  }

  /**
   * @inheritDoc
   */
  public function getUpdatedAt()
  {
    return $this->_get(self::UPDATED_AT);
  }

  /**
   * @inheritDoc
   */
  public function setUpdatedAt($updatedAt)
  {
    return $this->setData(self::UPDATED_AT, $updatedAt);
  }

  /**
   * @inheritDoc
   */
  public function getParentIds()
  {
    $parentIds = $this->_get(self::PARENT_IDS);
    return is_array($parentIds) ? $parentIds : [];
  }

  /**
   * @inheritDoc
   */
  public function setParentIds(array $parentIds)
  {
    return $this->setData(self::PARENT_IDS, $parentIds);
  }

  /**
   * @inheritDoc
   */
  public function getChildIds()
  {
    $childIds = $this->_get(self::CHILD_IDS);
    return is_array($childIds) ? $childIds : [];
  }

  /**
   * @inheritDoc
   */
  public function setChildIds(array $childIds)
  {
    return $this->setData(self::CHILD_IDS, $childIds);
  }

  /**
   * @inheritDoc
   */
  public function getNewsIds()
  {
    $newsIds = $this->_get(self::NEWS_IDS);
    return is_array($newsIds) ? $newsIds : [];
  }

  /**
   * @inheritDoc
   */
  public function setNewsIds(array $newsIds)
  {
    return $this->setData(self::NEWS_IDS, $newsIds);
  }
}
