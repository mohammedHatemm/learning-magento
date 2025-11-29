<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use News\Manger\Model\Category;

class Delete extends Action
{
  protected $_model;

  const ADMIN_RESOURCE = 'News_Manger::category_delete';

  public function __construct(
    Action\Context $context,
    Category $model
  ) {
    parent::__construct($context);
    $this->_model = $model;
  }

  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
  }

  public function execute()
  {
    $id = $this->getRequest()->getParam('id'); // ← تم التصحيح هنا
    $resultRedirect = $this->resultRedirectFactory->create();

    if ($id) {
      try {
        $model = $this->_model->load($id);
        if (!$model->getId()) {
          throw new \Magento\Framework\Exception\LocalizedException(__('Category not found.'));
        }

        $model->delete();
        $this->messageManager->addSuccessMessage(__('Category deleted successfully.'));
        return $resultRedirect->setPath('*/*/');
      } catch (\Exception $e) {
        $this->messageManager->addErrorMessage($e->getMessage());
        return $resultRedirect->setPath('*/*/edit', ['id' => $id]); // ← مسار الرجوع عند الخطأ
      }
    }

    $this->messageManager->addErrorMessage(__('Category does not exist.'));
    return $resultRedirect->setPath('*/*/');
  }
}
