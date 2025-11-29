<?php

namespace News\Manger\Controller\Adminhtml\News;

use Magento\Backend\App\Action;
use News\Manger\Model\News;

class Delete extends Action
{
  protected $_model;

  const ADMIN_RESOURCE = 'News_Manger::news_delete';

  public function __construct(
    Action\Context $context,
    News $model
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
    $id = $this->getRequest()->getParam('news_id');
    $resultRedirect = $this->resultRedirectFactory->create();

    if ($id) {
      try {
        $model = $this->_model->load($id);
        if (!$model->getId()) {
          throw new \Magento\Framework\Exception\LocalizedException(__('News item not found.'));
        }

        $model->delete();
        $this->messageManager->addSuccessMessage(__('News item deleted successfully.'));
        return $resultRedirect->setPath('*/*/');
      } catch (\Exception $e) {
        $this->messageManager->addErrorMessage($e->getMessage());
        return $resultRedirect->setPath('*/*/edit', ['news_id' => $id]);
      }
    }

    $this->messageManager->addErrorMessage(__('News item does not exist.'));
    return $resultRedirect->setPath('*/*/');
  }
}
