<?php

namespace News\Manger\Controller\Adminhtml\News;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
  const ADMIN_RESOURCE = 'News_Manger::News';
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
    $resultPage->setActiveMenu('News_Manger::News')->addBreadcrumb(__('News'), __('News'))->addBreadcrumb(__('Manger News'), __('Manger News'));
    $resultPage->getConfig()->getTitle()->prepend(__('News'));
    return $resultPage;
  }
}
