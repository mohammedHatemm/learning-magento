<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;

class Index extends Action
{
  const ADMIN_RESOURCE = 'News_Manger::Category';
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
    $resultPage->setActiveMenu('News_Manger::Category')->addBreadcrumb(__('Category'), __('Category'))->addBreadcrumb(__('Manger Category'), __('Manger Category'));
    $resultPage->getConfig()->getTitle()->prepend(__('Category'));
    return $resultPage;
  }
}
