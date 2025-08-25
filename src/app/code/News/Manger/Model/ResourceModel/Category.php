<?php

namespace News\Manger\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Category extends AbstractDb
{
  protected function _construct()
  {
    $this->_init('news_category', 'category_id');
  }

  /**
   * Load category with parent information
   *
   * @param AbstractModel $object
   * @return $this
   */
  protected function _afterLoad(AbstractModel $object)
  {
    if ($object->getId()) {
      // Load parent ID from hierarchy table
      $connection = $this->getConnection();
      $select = $connection->select()
        ->from($this->getTable('news_category_hierarchy'), 'parent_id')
        ->where('category_id = ?', $object->getId());

      $parentId = $connection->fetchOne($select);
      $object->setData('parent_category_id', $parentId);
    }

    return parent::_afterLoad($object);
  }

  /**
   * Get categories with hierarchy information
   *
   * @return array
   */
  public function getCategoriesWithHierarchy()
  {
    $connection = $this->getConnection();

    $select = $connection->select()
      ->from(['c' => $this->getMainTable()], ['category_id', 'category_name', 'category_status'])
      ->joinLeft(
        ['h' => $this->getTable('news_category_hierarchy')],
        'c.category_id = h.category_id',
        ['parent_id']
      )
      ->where('c.category_status = ?', 1)
      ->order('c.category_name ASC');

    return $connection->fetchAll($select);
  }

  /**
   * Get category tree structure
   *
   * @param int|null $parentId
   * @return array
   */
  public function getCategoryTree($parentId = null)
  {
    $connection = $this->getConnection();

    $select = $connection->select()
      ->from(['c' => $this->getMainTable()], ['category_id', 'category_name', 'category_status', 'sort_order'])
      ->joinLeft(
        ['h' => $this->getTable('news_category_hierarchy')],
        'c.category_id = h.category_id',
        ['parent_id']
      )
      ->where('c.category_status = ?', 1);

    if ($parentId === null) {
      $select->where('h.parent_id IS NULL');
    } else {
      $select->where('h.parent_id = ?', $parentId);
    }

    $select->order(['c.sort_order ASC', 'c.category_name ASC']);

    $categories = $connection->fetchAll($select);

    // Add children to each category
    foreach ($categories as &$category) {
      $category['children'] = $this->getCategoryTree($category['category_id']);
    }

    return $categories;
  }

  /**
   * Delete category and update hierarchy
   *
   * @param AbstractModel $object
   * @return $this
   */
  protected function _beforeDelete(AbstractModel $object)
  {
    // Check if category has children
    $connection = $this->getConnection();
    $select = $connection->select()
      ->from($this->getTable('news_category_hierarchy'), 'COUNT(*)')
      ->where('parent_id = ?', $object->getId());

    $childrenCount = $connection->fetchOne($select);

    if ($childrenCount > 0) {
      throw new \Magento\Framework\Exception\LocalizedException(
        __('Cannot delete category that has child categories. Please delete or reassign child categories first.')
      );
    }

    return parent::_beforeDelete($object);
  }

  /**
   * Clean up hierarchy table after category deletion
   *
   * @param AbstractModel $object
   * @return $this
   */
  protected function _afterDelete(AbstractModel $object)
  {
    // Remove from hierarchy table
    $connection = $this->getConnection();
    $connection->delete(
      $this->getTable('news_category_hierarchy'),
      ['category_id = ?' => $object->getId()]
    );

    return parent::_afterDelete($object);
  }
}
