<?php

namespace News\Manger\Model;

use Magento\Framework\Model\AbstractModel;
use News\Manger\Model\ResourceModel\Category as CategoryResourceModel;

class Category extends AbstractModel
{
  const CATEGORY_ID = "category_id";
  const CATEGORY_NAME = "category_name";
  const CATEGORY_DESCRIPTION = "category_description";
  const CATEGORY_STATUS = "category_status";
  const SORT_ORDER = "sort_order";
  const CREATED_AT = "created_at";
  const UPDATED_AT = "updated_at";

  protected $_eventPrefix = 'news_category';
  protected $_eventObject = 'news_category';
  protected $_idFieldName = 'category_id';

  /**
   * @var \News\Manger\Model\ResourceModel\Category\CollectionFactory
   */
  protected $_categoryCollectionFactory;

  /**
   * @param \Magento\Framework\Model\Context $context
   * @param \Magento\Framework\Registry $registry
   * @param \News\Manger\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
   * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
   * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
   * @param array $data
   */
  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \News\Manger\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = []
  ) {
    $this->_categoryCollectionFactory = $categoryCollectionFactory;
    parent::__construct($context, $registry, $resource, $resourceCollection, $data);
  }

  protected function _construct()
  {
    $this->_init(CategoryResourceModel::class);
  }

  public function getCategoryId()
  {
    return $this->getData(self::CATEGORY_ID);
  }

  public function getCategoryName()
  {
    return $this->getData(self::CATEGORY_NAME);
  }

  public function getCategoryDescription()
  {
    return $this->getData(self::CATEGORY_DESCRIPTION);
  }

  public function getCategoryStatus()
  {
    return $this->getData(self::CATEGORY_STATUS);
  }

  public function getSortOrder()
  {
    return $this->getData(self::SORT_ORDER);
  }

  public function getCreatedAt()
  {
    return $this->getData(self::CREATED_AT);
  }

  public function getUpdatedAt()
  {
    return $this->getData(self::UPDATED_AT);
  }

  /**
   * Get parent categories for dropdown options
   *
   * @param bool $excludeCurrentCategory
   * @return array
   */
  public function getParentCategories($excludeCurrentCategory = true)
  {
    $collection = $this->_categoryCollectionFactory->create();
    $collection->addFieldToFilter('category_status', 1); // Only active categories
    $collection->setOrder('category_name', 'ASC');

    // Exclude current category if editing
    if ($excludeCurrentCategory && $this->getId()) {
      $collection->addFieldToFilter('category_id', ['neq' => $this->getId()]);
    }

    $options = ['' => __('-- Select Parent Category --')]; // Default option

    foreach ($collection as $category) {
      $options[$category->getId()] = $category->getCategoryName();
    }

    return $options;
  }

  /**
   * Get parent category ID from hierarchy table
   *
   * @return int|null
   */
  public function getParentId()
  {
    if (!$this->getId()) {
      return null;
    }

    $connection = $this->getResource()->getConnection();
    $select = $connection->select()
      ->from($this->getResource()->getTable('news_category_hierarchy'), 'parent_id')
      ->where('category_id = ?', $this->getId());

    return $connection->fetchOne($select);
  }

  /**
   * Get child categories
   *
   * @return array
   */
  public function getChildCategories()
  {
    if (!$this->getId()) {
      return [];
    }

    $connection = $this->getResource()->getConnection();
    $select = $connection->select()
      ->from(['h' => $this->getResource()->getTable('news_category_hierarchy')], ['category_id'])
      ->join(
        ['c' => $this->getResource()->getTable('news_category')],
        'h.category_id = c.category_id',
        ['category_name', 'category_status']
      )
      ->where('h.parent_id = ?', $this->getId())
      ->where('c.category_status = ?', 1);

    return $connection->fetchAll($select);
  }

  /**
   * Check if category has children
   *
   * @return bool
   */
  public function hasChildren()
  {
    $children = $this->getChildCategories();
    return count($children) > 0;
  }

  /**
   * Save category with hierarchy
   *
   * @param int|null $parentId
   * @return $this
   */
  public function saveWithHierarchy($parentId = null)
  {
    $this->save();

    // Handle hierarchy
    $connection = $this->getResource()->getConnection();
    $hierarchyTable = $this->getResource()->getTable('news_category_hierarchy');

    // Delete existing hierarchy record
    $connection->delete($hierarchyTable, ['category_id = ?' => $this->getId()]);

    // Insert new hierarchy record
    $connection->insert($hierarchyTable, [
      'category_id' => $this->getId(),
      'parent_id' => $parentId,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ]);

    return $this;
  }
}
