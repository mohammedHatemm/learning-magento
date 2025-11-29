<?php

namespace News\Manger\Controller\User;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use News\Manger\Model\NewsFactory;
use Magento\Customer\Model\Session;

class View extends Action
{
  protected $resultPageFactory;
  protected $coreRegistry;
  protected $newsFactory;
  protected $customerSession;

  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    Registry $coreRegistry,
    NewsFactory $newsFactory,
    Session $customerSession
  ) {
    $this->resultPageFactory = $resultPageFactory;
    $this->coreRegistry = $coreRegistry;
    $this->newsFactory = $newsFactory;
    $this->customerSession = $customerSession;
    parent::__construct($context);
  }

  public function execute()
  {
    if (!$this->customerSession->isLoggedIn()) {
      $this->messageManager->addErrorMessage(__('You must be logged in to view this page.'));
      return $this->resultRedirectFactory->create()->setPath('customer/account/login');
    }
    $newsId = (int)$this->getRequest()->getParam('id');
    if (!$newsId) {
      return $this->_forward('noroute');
    }

    $news = $this->newsFactory->create()->load($newsId);


    if (!$news->getId() || !$news->getNewsStatus()) {
      return $this->_forward('noroute');
    }

    $this->coreRegistry->register('current_news', $news);

    $resultPage = $this->resultPageFactory->create();


    $resultPage->getConfig()->getTitle()->set($news->getNewsTitle());
    return $resultPage;
  }
}
