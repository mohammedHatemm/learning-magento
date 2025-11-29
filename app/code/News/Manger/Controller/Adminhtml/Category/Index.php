<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
  const ADMIN_RESOURCE = 'News_Manger::category';

  protected $resultPageFactory;

  public function __construct(
    Context $context,
    PageFactory $resultPageFactory
  ) {
    parent::__construct($context);
    $this->resultPageFactory = $resultPageFactory;
  }

  public function execute()
  {
    $resultPage = $this->resultPageFactory->create();
    $resultPage->setActiveMenu('News_Manger::category')
      ->addBreadcrumb(__('Category'), __('Category'))
      ->addBreadcrumb(__('Manage Category'), __('Manage Category'));
    $resultPage->getConfig()->getTitle()->prepend(__('Categories'));

    return $resultPage;
  }
}
