<?php

namespace News\Manger\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use News\Manger\Model\CategoryFactory;
use News\Manger\Api\Data\CategoryInterface;

class Category extends AbstractModel implements IdentityInterface, CategoryInterface
{
  const CACHE_TAG = 'news_manger_category';
  const CATEGORY_ID = 'category_id';
  const CONFIG_MAX_DEPTH = 'news_manager/category/max_depth';
  const CONFIG_ENABLE_CACHING = 'news_manager/category/enable_caching';
  const DEFAULT_MAX_DEPTH = 5;

  protected $_cacheTag = self::CACHE_TAG;
  protected $_eventPrefix = 'news_manger_category';
  protected $_eventObject = 'category';
  protected $_idFieldName = self::CATEGORY_ID;
  protected $_scopeConfig;
  protected static $_categoryTreeCache = [];

  protected $_categoryFactory;

  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    ScopeConfigInterface $scopeConfig,
    CategoryFactory $categoryFactory,
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = []
  ) {
    $this->_scopeConfig = $scopeConfig;
    $this->_categoryFactory = $categoryFactory;
    parent::__construct($context, $registry, $resource, $resourceCollection, $data);
  }

  protected function _construct()
  {
    $this->_init('News\Manger\Model\ResourceModel\Category');
  }

  public function getIdentities()
  {
    return [self::CACHE_TAG . '_' . $this->getId()];
  }

  public function getDefaultValues()
  {
    $values = [];
    $values['category_status'] = 1;
    $values['created_at'] = date('Y-m-d H:i:s');
    $values['updated_at'] = date('Y-m-d H:i:s');
    return $values;
  }

  public function getMaxAllowedDepth()
  {
    return (int) $this->_scopeConfig->getValue(
      self::CONFIG_MAX_DEPTH,
      ScopeInterface::SCOPE_STORE
    ) ?: self::DEFAULT_MAX_DEPTH;
  }

  public function isCachingEnabled()
  {
    return $this->_scopeConfig->isSetFlag(
      self::CONFIG_ENABLE_CACHING,
      ScopeInterface::SCOPE_STORE
    );
  }

  // === CategoryInterface Implementation ===

  /**
   * @inheritDoc
   */
  public function getCategoryId()
  {
    return $this->getData(self::CATEGORY_ID);
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
    return $this->getData('category_name');
  }

  /**
   * @inheritDoc
   */
  public function setCategoryName($name)
  {
    return $this->setData('category_name', $name);
  }

  /**
   * @inheritDoc
   */
  public function getCategoryDescription()
  {
    return $this->getData('category_description');
  }

  /**
   * @inheritDoc
   */
  public function setCategoryDescription($description)
  {
    return $this->setData('category_description', $description);
  }

  /**
   * @inheritDoc
   */
  public function getCategoryStatus()
  {
    return $this->getData('category_status');
  }

  /**
   * @inheritDoc
   */
  public function setCategoryStatus($status)
  {
    return $this->setData('category_status', $status);
  }

  /**
   * @inheritDoc
   */
  public function getCreatedAt()
  {
    return $this->getData('created_at');
  }

  /**
   * @inheritDoc
   */
  public function setCreatedAt($createdAt)
  {
    return $this->setData('created_at', $createdAt);
  }

  /**
   * @inheritDoc
   */
  public function getUpdatedAt()
  {
    return $this->getData('updated_at');
  }

  /**
   * @inheritDoc
   */
  public function setUpdatedAt($updatedAt)
  {
    return $this->setData('updated_at', $updatedAt);
  }

  /**
   * @inheritDoc
   */
  public function getParentIds()
  {
    $parentIds = $this->getData('parent_ids');
    if (is_string($parentIds) && !empty($parentIds)) {
      $decoded = json_decode($parentIds, true);
      return is_array($decoded) ? $decoded : [];
    }
    return is_array($parentIds) ? $parentIds : [];
  }

  /**
   * @inheritDoc
   */
  public function setParentIds($parentIds)
  {
    if (is_array($parentIds)) {
      // Store as JSON in database
      return $this->setData('parent_ids', json_encode($parentIds));
    }
    return $this->setData('parent_ids', $parentIds);
  }

  /**
   * @inheritDoc
   */
  public function getChildIds()
  {
    // This will be dynamically calculated from the database
    // based on which categories have this category as parent
    $collection = $this->getCollection();
    $collection->addFieldToFilter('parent_ids', ['like' => '%"' . $this->getId() . '"%']);

    $childIds = [];
    foreach ($collection as $child) {
      $childIds[] = $child->getId();
    }
    return $childIds;
  }

  /**
   * @inheritDoc
   */
  public function setChildIds($childIds)
  {
    // Child IDs are dynamically calculated, so this is just for interface compliance
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getNewsIds()
  {
    $newsIds = $this->getData('news_ids');
    if (is_string($newsIds) && !empty($newsIds)) {
      $decoded = json_decode($newsIds, true);
      return is_array($decoded) ? $decoded : [];
    }
    return is_array($newsIds) ? $newsIds : [];
  }

  /**
   * @inheritDoc
   */
  public function setNewsIds($newsIds)
  {
    if (is_array($newsIds)) {
      return $this->setData('news_ids', json_encode($newsIds));
    }
    return $this->setData('news_ids', $newsIds);
  }

  // === Additional Methods from Original Model ===

  public function isActive()
  {
    return (bool)$this->getCategoryStatus();
  }

  public function isRoot()
  {
    return empty($this->getParentIds());
  }

  public function getParentCategories()
  {
    $parents = [];
    foreach ($this->getParentIds() as $parentId) {
      $parentCategory = $this->_categoryFactory->create();
      $this->_getResource()->load($parentCategory, $parentId);
      if ($parentCategory->getId()) {
        $parents[] = $parentCategory;
      }
    }
    return $parents;
  }

  public function getChildrenCategories($activeOnly = false)
  {
    $collection = $this->getCollection();
    $collection->addFieldToFilter('parent_ids', ['like' => '%"' . $this->getId() . '"%']);
    if ($activeOnly) {
      $collection->addFieldToFilter('category_status', 1);
    }
    $collection->setOrder('category_name', 'ASC');
    return $collection;
  }

  public function getRootCategories($activeOnly = true)
  {
    $collection = $this->getCollection();
    $collection->addFieldToFilter('parent_ids', ['null' => true]);
    if ($activeOnly) {
      $collection->addFieldToFilter('category_status', 1);
    }
    $collection->setOrder('category_name', 'ASC');
    return $collection;
  }

  public function getLevel()
  {
    $parentIds = $this->getParentIds();
    if (empty($parentIds)) {
      return 0;
    }
    $maxLevel = 0;
    foreach ($parentIds as $parentId) {
      $parentCategory = $this->_categoryFactory->create();
      $this->_getResource()->load($parentCategory, $parentId);
      if ($parentCategory->getId()) {
        $level = $parentCategory->getLevel() + 1;
        $maxLevel = max($maxLevel, $level);
      }
    }
    return $maxLevel;
  }

  public function getPath($separator = ' > ')
  {
    $paths = [];
    foreach ($this->getFormattedPaths($separator) as $path) {
      $paths[] = $path;
    }

    return implode("\n", $paths);
  }

  /**
   * Get paths formatted for HTML display
   * @param string $separator
   * @return string
   */
  public function getPathHtml($separator = ' > ')
  {
    $paths = [];
    foreach ($this->getFormattedPaths($separator) as $path) {
      $paths[] = htmlentities($path);
    }
    // عرض كل مسار في سطر منفصل مع HTML
    return implode("<br>", $paths);
  }

  public function getBreadcrumbPaths()
  {
    $paths = [];
    $parentIds = $this->getParentIds();
    $maxDepth = $this->getMaxAllowedDepth();

    foreach ($parentIds as $parentId) {
      $breadcrumbs = [];
      $current = $this->_categoryFactory->create()->load($parentId);
      $depth = 0;

      while ($current && $current->getId() && $depth < $maxDepth) {
        array_unshift($breadcrumbs, [
          'id' => $current->getId(),
          'name' => $current->getCategoryName(),
          'level' => $current->getLevel()
        ]);
        $parentIds = $current->getParentIds();
        $current = !empty($parentIds) ? $this->_categoryFactory->create()->load($parentIds[0]) : null;
        $depth++;
      }

      array_push($breadcrumbs, [
        'id' => $this->getId(),
        'name' => $this->getCategoryName(),
        'level' => $this->getLevel()
      ]);
      $paths[] = $breadcrumbs;
    }

    if (empty($paths)) {
      $paths[] = [[
        'id' => $this->getId(),
        'name' => $this->getCategoryName(),
        'level' => $this->getLevel()
      ]];
    }

    return $paths;
  }

  public function getAllParents()
  {
    $parents = [];
    foreach ($this->getParentIds() as $parentId) {
      $parentCategory = $this->_categoryFactory->create();
      $this->_getResource()->load($parentCategory, $parentId);
      if ($parentCategory->getId()) {
        $parents[] = $parentCategory;
      }
    }
    return $parents;
  }

  public function isAncestorOf($categoryId)
  {
    $category = $this->_categoryFactory->create();
    $this->_getResource()->load($category, $categoryId);

    if (!$category->getId()) {
      return false;
    }

    $parentIds = $category->getParentIds();
    return in_array($this->getId(), $parentIds);
  }

  public function isDescendantOf($categoryId)
  {
    return in_array($categoryId, $this->getParentIds());
  }

  public function getRootCategory()
  {
    $current = $this;
    $maxDepth = $this->getMaxAllowedDepth();
    $depth = 0;

    while (!empty($current->getParentIds()) && $depth < $maxDepth) {
      $parentId = $current->getParentIds()[0];
      $parent = $this->_categoryFactory->create();
      $this->_getResource()->load($parent, $parentId);
      if ($parent->getId()) {
        $current = $parent;
        $depth++;
      } else {
        break;
      }
    }
    return $current;
  }

  // public function validateHierarchy()
  // {
  //   $parentIds = $this->getParentIds();
  //   if (empty($parentIds)) {
  //     return true;
  //   }

  //   if (in_array($this->getId(), $parentIds)) {
  //     return false;
  //   }

  //   foreach ($parentIds as $parentId) {
  //     $parentCategory = $this->_categoryFactory->create();
  //     $this->_getResource()->load($parentCategory, $parentId);
  //     if (!$parentCategory->getId()) {
  //       return false;
  //     }

  //     if ($this->isAncestorOf($parentId)) {
  //       return false;
  //     }

  //     $parentLevel = $parentCategory->getLevel();
  //     $maxDepth = $this->getMaxAllowedDepth();
  //     if ($parentLevel >= ($maxDepth - 1)) {
  //       return false;
  //     }
  //   }

  //   return true;
  // }

  public function getFormattedName($prefix = '├── ')
  {
    $level = $this->getLevel();
    $indent = str_repeat('│   ', $level);
    return $level > 0 ? $indent . $prefix . $this->getCategoryName() : $this->getCategoryName();
  }

  public function getCategoryStats()
  {
    return [
      'id' => $this->getId(),
      'name' => $this->getCategoryName(),
      'level' => $this->getLevel(),
      'is_root' => $this->isRoot(),
      'is_active' => $this->isActive(),
      'children_count' => $this->getChildrenCount(),
      'has_children' => $this->hasChildren(),
      'parent_ids' => $this->getParentIds(),
      'breadcrumb_path' => $this->getBreadcrumbPaths(),
      'created_at' => $this->getCreatedAt(),
      'updated_at' => $this->getUpdatedAt()
    ];
  }

  public function getFormattedPaths($separator = ' > ')
  {
    $paths = [];
    foreach ($this->getBreadcrumbPaths() as $breadcrumb) {
      $path = [];
      foreach ($breadcrumb as $item) {
        $path[] = $item['name'];
      }
      $paths[] = implode($separator, $path);
    }
    return $paths;
  }

  /**
   * Get paths formatted for HTML display in grid
   * @return string
   */
  public function getFormattedPathsForGridHtml()
  {
    $paths = [];
    $breadcrumbPaths = $this->getBreadcrumbPaths();

    foreach ($breadcrumbPaths as $breadcrumb) {
      $pathNames = [];
      foreach ($breadcrumb as $item) {
        $pathNames[] = htmlspecialchars($item['name']);
      }
      $paths[] = implode(' &gt; ', $pathNames);
    }

    // إرجاع كل مسار في سطر منفصل مع HTML line breaks
    return implode('<br/>', $paths);
  }

  public function getAllPaths()
  {
    return $this->getBreadcrumbPaths();
  }

  public function hasChildren()
  {
    return $this->getChildrenCount() > 0;
  }

  public function getChildrenCount()
  {
    $collection = $this->getChildrenCategories();
    return $collection->getSize();
  }

  public function getCategoryTreeForJs($addCollectionData = false)
  {
    $tree = [];
    $rootCategories = $this->getRootCategories(true);
    foreach ($rootCategories as $root) {
      $tree[] = $this->buildTreeNode($root, $addCollectionData);
    }
    return $tree;
  }

  private function buildTreeNode($category, $addCollectionData = false)
  {
    $node = [
      'id' => $category->getId(),
      'text' => $category->getCategoryName(),
      'level' => $category->getLevel(),
      'is_active' => $category->isActive(),
      'children' => []
    ];
    if ($addCollectionData) {
      $node['full_data'] = $category->getData();
    }
    $children = $category->getChildrenCategories(true);
    foreach ($children as $child) {
      $node['children'][] = $this->buildTreeNode($child, $addCollectionData);
    }
    return $node;
  }

  public function getChildrenTree()
  {
    $tree = [];
    $children = $this->getChildrenCategories(true);
    foreach ($children as $child) {
      $tree[] = $this->buildTreeNode($child, false);
    }
    return $tree;
  }

  public static function clearTreeCache()
  {
    self::$_categoryTreeCache = [];
  }

  public function beforeSave()
  {
    // Validate hierarchy before saving
    // if (!$this->validateHierarchy()) {
    //   throw new \Magento\Framework\Exception\LocalizedException(
    //     __('Invalid category hierarchy. Please check parent category selection.')
    //   );
    // }

    // Ensure JSON encoding for array fields
    foreach (['parent_ids', 'child_ids', 'news_ids'] as $field) {
      $value = $this->getData($field);
      if (is_array($value)) {
        $this->setData($field, json_encode($value));
      }
    }

    // Set timestamps
    if (!$this->getId()) {
      $this->setCreatedAt(date('Y-m-d H:i:s'));
    }
    $this->setUpdatedAt(date('Y-m-d H:i:s'));

    // Clear cache
    self::clearTreeCache();

    return parent::beforeSave();
  }

  public function afterDelete()
  {
    self::clearTreeCache();
    return parent::afterDelete();
  }

  public function validateBeforeSave()
  {
    parent::validateBeforeSave();

    $parentIds = $this->getParentIds();
    if (empty($parentIds)) {
      return true;
    }

    // تحويل parent_ids إلى مصفوفة إذا كان نصياً
    if (is_string($parentIds)) {
      $parentIds = json_decode($parentIds, true);
    }

    // التحقق فقط من أن الفئة لا تكون أباً لنفسها
    if (in_array($this->getId(), $parentIds)) {
      throw new \Magento\Framework\Exception\LocalizedException(
        __('A category cannot be parent to itself.')
      );
    }

    return true;
  }
}
