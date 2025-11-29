<?php

namespace News\Manger\Controller\Adminhtml\News;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use News\Manger\Model\NewsFactory;

class Edit extends Action
{
  const ADMIN_RESOURCE = 'News_Manger::edit';

  protected $resultPageFactory;
  protected $coreRegistry;
  protected $newsFactory;

  public function __construct(
    Action\Context $context,
    PageFactory $resultPageFactory,
    Registry $registry,
    NewsFactory $newsFactory
  ) {
    $this->resultPageFactory = $resultPageFactory;
    $this->coreRegistry = $registry;
    $this->newsFactory = $newsFactory;
    parent::__construct($context);
  }

  public function execute()
  {
    $id = $this->getRequest()->getParam('news_id');
    $model = $this->newsFactory->create();

    if ($id) {
      $model->load($id);
      if (!$model->getId()) {
        $this->messageManager->addErrorMessage(__('This news item no longer exists.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
      }
    }

    $this->coreRegistry->register('news_news', $model);

    $resultPage = $this->resultPageFactory->create();
    $resultPage->setActiveMenu('News_Manger::news');
    $resultPage->addBreadcrumb(__('Edit News'), __('Edit News'));
    $resultPage->getConfig()->getTitle()->prepend(__('News'));
    $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New News'));

    return $resultPage;
  }

  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
  }
}
