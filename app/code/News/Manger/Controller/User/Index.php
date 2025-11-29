<?php
/*
File: app/code/News/Manger/Controller/User/Index.php
*/

namespace News\Manger\Controller\User;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;

class Index extends Action
{
  protected $resultPageFactory;
  protected $customerSession;

  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    Session $customerSession
  ) {
    parent::__construct($context);
    $this->resultPageFactory = $resultPageFactory;
    $this->customerSession = $customerSession;
  }

  public function execute()
  {
    // Check if customer is logged in
    if (!$this->customerSession->isLoggedIn()) {
      $this->messageManager->addErrorMessage(__('You must be logged in to view this page.'));
      return $this->resultRedirectFactory->create()->setPath('customer/account/login');
    }

    // Create and return page result
    $resultPage = $this->resultPageFactory->create();
    $resultPage->getConfig()->getTitle()->set(__('News List'));

    return $resultPage;
  }
}
