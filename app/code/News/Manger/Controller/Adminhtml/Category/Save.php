<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use News\Manger\Model\CategoryFactory;
use Psr\Log\LoggerInterface;

class Save extends \Magento\Backend\App\Action
{
  const ADMIN_RESOURCE = 'News_Manger::category_save';

  protected $dataPersistor;
  protected $categoryFactory;
  protected $logger;

  public function __construct(
    Context $context,
    DataPersistorInterface $dataPersistor,
    CategoryFactory $categoryFactory,
    LoggerInterface $logger
  ) {
    $this->dataPersistor = $dataPersistor;
    $this->categoryFactory = $categoryFactory;
    $this->logger = $logger;
    parent::__construct($context);
  }

  public function execute()
  {
    $resultRedirect = $this->resultRedirectFactory->create();
    $data = $this->getRequest()->getPostValue();

    if ($data) {
      $id = $this->getRequest()->getParam('category_id');
      $model = $this->categoryFactory->create();

      if ($id) {
        $model->load($id);
        if (!$model->getId()) {
          $this->messageManager->addErrorMessage(__('This category no longer exists.'));
          return $resultRedirect->setPath('*/*/');
        }
      }

      $validationErrors = $this->validateData($data);
      if (!empty($validationErrors)) {
        foreach ($validationErrors as $error) {
          $this->messageManager->addErrorMessage($error);
        }
        return $this->redirectWithData($resultRedirect, $data, $id);
      }

      $data = $this->prepareData($data, $model);

      // Circular reference check using parent_ids array (for first parent only)
      if (!empty($data['parent_ids']) && $id) {
        $parentIds = json_decode($data['parent_ids'], true);
        if (is_array($parentIds)) {
          foreach ($parentIds as $parentId) {
            if ($this->hasCircularReference((int)$parentId, (int)$id)) {
              $this->messageManager->addErrorMessage(
                __('Cannot set parent category: This would create a circular reference.')
              );
              return $this->redirectWithData($resultRedirect, $data, $id);
            }
          }
        }
      }

      $model->setData($data);

      try {
        $model->save();
        $this->messageManager->addSuccessMessage(__('The category has been saved.'));
        $this->dataPersistor->clear('news_category');

        if ($this->getRequest()->getParam('back')) {
          return $resultRedirect->setPath('*/*/edit', [
            'category_id' => $model->getId(),
            '_current' => true
          ]);
        }

        return $resultRedirect->setPath('*/*/');
      } catch (LocalizedException $e) {
        $this->messageManager->addErrorMessage($e->getMessage());
        $this->logger->error('LocalizedException while saving category: ' . $e->getMessage());
      } catch (\Exception $e) {
        $this->messageManager->addExceptionMessage(
          $e,
          __('Something went wrong while saving the category.')
        );
        $this->logger->error('Exception while saving category: ' . $e->getMessage());
      }

      $this->dataPersistor->set('news_category', $data);
      return $this->redirectWithData($resultRedirect, $data, $id);
    }

    return $resultRedirect->setPath('*/*/');
  }

  private function validateData($data)
  {
    $errors = [];

    if (empty($data['category_name']) || trim($data['category_name']) === '') {
      $errors[] = __('Please provide the category name.');
    }

    if (empty($data['category_description']) || trim($data['category_description']) === '') {
      $errors[] = __('Please provide the category description.');
    }

    if (!isset($data['category_status']) || $data['category_status'] === '') {
      $errors[] = __('Please select the category status.');
    }

    return $errors;
  }

  private function prepareData($data, $model)
  {
    // Handle timestamps
    if (!$model->getId()) {
      $data['created_at'] = date('Y-m-d H:i:s');
    }
    $data['updated_at'] = date('Y-m-d H:i:s');

    // Remove unnecessary fields
    unset($data['form_key'], $data['created_at_display'], $data['updated_at_display']);

    // Trim strings
    $data['category_name'] = trim($data['category_name']);
    $data['category_description'] = trim($data['category_description']);

    // Handle parent_ids checkbox
    if (isset($data['parent_ids']) && is_array($data['parent_ids'])) {
      // remove current category id from parent_ids if editing
      if ($model->getId()) {
        $data['parent_ids'] = array_filter($data['parent_ids'], function ($pid) use ($model) {
          return $pid != $model->getId();
        });
      }
      $data['parent_ids'] = json_encode(array_map('intval', $data['parent_ids']));
    } else {
      $data['parent_ids'] = json_encode([]);
    }

    return $data;
  }

  private function hasCircularReference($parentId, $categoryId)
  {
    if ($parentId == $categoryId) {
      return true;
    }

    try {
      $parentModel = $this->categoryFactory->create()->load($parentId);
      if ($parentModel->getId()) {
        $grandParents = $parentModel->getParentIds();
        if (in_array($categoryId, $grandParents)) {
          return true;
        }

        foreach ($grandParents as $gp) {
          if ($this->hasCircularReference($gp, $categoryId)) {
            return true;
          }
        }
      }
    } catch (\Exception $e) {
      $this->logger->error('Error checking circular reference: ' . $e->getMessage());
    }

    return false;
  }

  private function redirectWithData($resultRedirect, $data, $id)
  {
    $this->dataPersistor->set('news_category', $data);
    return $id
      ? $resultRedirect->setPath('*/*/edit', ['category_id' => $id])
      : $resultRedirect->setPath('*/*/new');
  }
}
