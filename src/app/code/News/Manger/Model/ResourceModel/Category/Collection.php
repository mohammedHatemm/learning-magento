<?php

namespace News\Manger\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use News\Manger\Model\Category as CategoryModel;
use News\Manger\Model\ResourceModel\Category as CategoryResourceModel;

class Collection extends AbstractCollection
{
  protected $_idFieldName = 'category_id';

  protected function _construct()
  {
    $this->_init(
      CategoryModel::class,
      CategoryResourceModel::class
    );
  }

  /**
   * Add hierarchy information to collection
   *
   * @return $this
   */
  public function addHierarchyData()
  {
    $this->getSelect()->joinLeft(
      ['hierarchy' => $this->getTable('news_category_hierarchy')],
      'main_table.category_id = hierarchy.category_id',
      ['parent_id']
    );

    return $this;
  }

  /**
   * Filter by parent category
   *
   * @param int|null $parentId
   * @return $this
   */
  public function addParentFilter($parentId = null)
  {
    if (!$this->getFlag('hierarchy_joined')) {
      $this->addHierarchyData();
      $this->setFlag('hierarchy_joined', true);
    }

    if ($parentId === null) {
      $this->getSelect()->where('hierarchy.parent_id IS NULL');
    } else {
      $this->getSelect()->where('hierarchy.parent_id = ?', $parentId);
    }

    return $this;
  }

  /**
   * Filter only root categories (no parent)
   *
   * @return $this
   */
  public function addRootCategoriesFilter()
  {
    return $this->addParentFilter(null);
  }

  /**
   * Add active status filter
   *
   * @return $this
   */
  public function addActiveFilter()
  {
    $this->addFieldToFilter('category_status', 1);
    return $this;
  }

  /**
   * Get categories as tree structure
   *
   * @return array
   */
  public function getTreeStructure()
  {
    $this->addHierarchyData();
    $this->addActiveFilter();
    $this->setOrder('sort_order', 'ASC');
    $this->setOrder('category_name', 'ASC');

    $allCategories = [];
    $tree = [];

    // First, collect all categories
    foreach ($this as $category) {
      $categoryData = [
        'category_id' => $category->getId(),
        'category_name' => $category->getCategoryName(),
        'parent_id' => $category->getData('parent_id'),
        'sort_order' => $category->getSortOrder(),
        'children' => []
      ];
      $allCategories[$category->getId()] = $categoryData;
    }

    // Then, build the tree
    foreach ($allCategories as $category) {
      if ($category['parent_id'] === null) {
        $tree[] = &$allCategories[$category['category_id']];
      } else {
        if (isset($allCategories[$category['parent_id']])) {
          $allCategories[$category['parent_id']]['children'][] = &$allCategories[$category['category_id']];
        }
      }
    }

    return $tree;
  }

  /**
   * Get options array for dropdown
   *
   * @param bool $withEmpty
   * @param int|null $excludeId
   * @return array
   */
  public function getOptionsArray($withEmpty = true, $excludeId = null)
  {
    $this->addActiveFilter();
    $this->setOrder('category_name', 'ASC');

    if ($excludeId) {
      $this->addFieldToFilter('category_id', ['neq' => $excludeId]);
    }

    $options = [];

    if ($withEmpty) {
      $options[''] = __('-- Select Parent Category --');
    }

    foreach ($this as $category) {
      $options[$category->getId()] = $category->getCategoryName();
    }

    return $options;
  }

  /**
   * Get hierarchical options array for dropdown (with indentation)
   *
   * @param bool $withEmpty
   * @param int|null $excludeId
   * @return array
   */
  public function getHierarchicalOptionsArray($withEmpty = true, $excludeId = null)
  {
    $tree = $this->getTreeStructure();

    $options = [];

    if ($withEmpty) {
      $options[''] = __('-- Select Parent Category --');
    }

    $this->_buildOptionsFromTree($tree, $options, 0, $excludeId);

    return $options;
  }

  /**
   * Recursively build options from tree structure
   *
   * @param array $tree
   * @param array &$options
   * @param int $level
   * @param int|null $excludeId
   */
  private function _buildOptionsFromTree($tree, &$options, $level = 0, $excludeId = null)
  {
    $indent = str_repeat('--', $level);

    foreach ($tree as $category) {
      if ($excludeId && $category['category_id'] == $excludeId) {
        continue;
      }

      $options[$category['category_id']] = ($level > 0 ? $indent . ' ' : '') . $category['category_name'];

      if (!empty($category['children'])) {
        $this->_buildOptionsFromTree($category['children'], $options, $level + 1, $excludeId);
      }
    }
  }
}
