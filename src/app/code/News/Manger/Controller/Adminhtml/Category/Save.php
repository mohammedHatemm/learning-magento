<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use News\Manger\Model\CategoryFactory;

class Save extends Action
{
  /**
   * @var DataPersistorInterface
   */
  protected $dataPersistor;

  /**
   * @var CategoryFactory
   */
  protected $categoryFactory;

  /**
   * @param Context $context
   * @param DataPersistorInterface $dataPersistor
   * @param CategoryFactory $categoryFactory
   */
  public function __construct(
    Context $context,
    DataPersistorInterface $dataPersistor,
    CategoryFactory $categoryFactory
  ) {
    $this->dataPersistor = $dataPersistor;
    $this->categoryFactory = $categoryFactory;
    parent::__construct($context);
  }

  /**
   * Save action
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute()
  {
    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
    $resultRedirect = $this->resultRedirectFactory->create();
    $data = $this->getRequest()->getPostValue();

    if ($data) {
      $id = $this->getRequest()->getParam('category_id');
      $model = $this->categoryFactory->create()->load($id);

      if (!$model->getId() && $id) {
        $this->messageManager->addErrorMessage(__('This category no longer exists.'));
        return $resultRedirect->setPath('*/*/');
      }

      // Handle empty status
      if (!isset($data['category_status'])) {
        $data['category_status'] = 0;
      }

      // Handle empty sort order
      if (!isset($data['sort_order']) || $data['sort_order'] === '') {
        $data['sort_order'] = 0;
      }

      // Get parent category ID
      $parentCategoryId = isset($data['parent_category_id']) && $data['parent_category_id'] !== ''
        ? (int)$data['parent_category_id']
        : null;

      // Validate parent category (prevent circular reference)
      if ($parentCategoryId && $model->getId()) {
        if (!$this->validateParentCategory($model->getId(), $parentCategoryId)) {
          $this->messageManager->addErrorMessage(__('Cannot set parent category. This would create a circular reference.'));
          $this->dataPersistor->set('news_category', $data);
          return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('category_id')]);
        }
      }

      // Remove parent_category_id from data as it's handled separately
      unset($data['parent_category_id']);

      $model->setData($data);

      try {
        // Save category first
        $model->save();

        // Handle hierarchy
        $this->saveHierarchy($model->getId(), $parentCategoryId);

        $this->messageManager->addSuccessMessage(__('You saved the category.'));
        $this->dataPersistor->clear('news_category');

        if ($this->getRequest()->getParam('back')) {
          return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }
        return $resultRedirect->setPath('*/*/');
      } catch (LocalizedException $e) {
        $this->messageManager->addErrorMessage($e->getMessage());
      } catch (\Exception $e) {
        $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the category.'));
        $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
      }

      $this->dataPersistor->set('news_category', $data);
      return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('category_id')]);
    }
    return $resultRedirect->setPath('*/*/');
  }

  /**
   * Save category hierarchy
   *
   * @param int $categoryId
   * @param int|null $parentId
   * @return void
   */
  protected function saveHierarchy($categoryId, $parentId = null)
  {
    $connection = $this->categoryFactory->create()->getResource()->getConnection();
    $hierarchyTable = $this->categoryFactory->create()->getResource()->getTable('news_category_hierarchy');

    // Delete existing hierarchy record
    $connection->delete($hierarchyTable, ['category_id = ?' => $categoryId]);

    // Insert new hierarchy record
    $hierarchyData = [
      'category_id' => $categoryId,
      'parent_id' => $parentId,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    $connection->insert($hierarchyTable, $hierarchyData);
  }

  /**
   * Validate parent category to prevent circular reference
   *
   * @param int $categoryId
   * @param int $parentId
   * @return bool
   */
  protected function validateParentCategory($categoryId, $parentId)
  {
    if ($categoryId == $parentId) {
      return false; // Category cannot be parent of itself
    }

    $connection = $this->categoryFactory->create()->getResource()->getConnection();
    $hierarchyTable = $this->categoryFactory->create()->getResource()->getTable('news_category_hierarchy');

    // Check if the parent is a descendant of the current category
    $descendants = $this->getCategoryDescendants($categoryId);

    return !in_array($parentId, $descendants);
  }

  /**
   * Get all descendant categories
   *
   * @param int $categoryId
   * @return array
   */
  protected function getCategoryDescendants($categoryId)
  {
    $connection = $this->categoryFactory->create()->getResource()->getConnection();
    $hierarchyTable = $this->categoryFactory->create()->getResource()->getTable('news_category_hierarchy');

    $descendants = [];
    $currentLevel = [$categoryId];

    while (!empty($currentLevel)) {
      $select = $connection->select()
        ->from($hierarchyTable, 'category_id')
        ->where('parent_id IN (?)', $currentLevel);

      $nextLevel = $connection->fetchCol($select);

      if (empty($nextLevel)) {
        break;
      }

      $descendants = array_merge($descendants, $nextLevel);
      $currentLevel = $nextLevel;
    }

    return $descendants;
  }

  /**
   * Check if admin has permissions to save
   *
   * @return bool
   */
  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed('News_Manger::category_save');
  }
}
