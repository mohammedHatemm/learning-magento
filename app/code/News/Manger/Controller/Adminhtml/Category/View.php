<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Framework\Controller\ResultFactory;
use News\Manger\Model\CategoryFactory;
use Magento\Framework\Registry;

class View extends \Magento\Backend\App\Action
{
  const ADMIN_RESOURCE = 'News_Manger::Category_view';
  const PAGE_TITLE = 'View Category';

  protected $resultPageFactory;
  protected $categoryFactory;
  protected $registry;

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    CategoryFactory $categoryFactory,
    Registry $registry
  ) {
    $this->resultPageFactory = $resultPageFactory;
    $this->categoryFactory = $categoryFactory;
    $this->registry = $registry;
    parent::__construct($context);
  }

  public function execute()
  {
    // ✅ يدعم كل من category_id و id من الرابط
    $categoryId = (int)$this->getRequest()->getParam('category_id') ?: (int)$this->getRequest()->getParam('id');

    if (!$categoryId) {
      $this->messageManager->addErrorMessage(__('Category ID is missing.'));
      return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }

    $category = $this->categoryFactory->create()->load($categoryId);
    if (!$category->getId()) {
      $this->messageManager->addErrorMessage(__('Category not found.'));
      return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }

    $this->registry->register('current_category', $category);

    $resultPage = $this->resultPageFactory->create();
    $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
    $resultPage->addBreadcrumb(__(static::PAGE_TITLE), __(static::PAGE_TITLE));
    $resultPage->getConfig()->getTitle()->prepend(__('View Category: %1', $category->getCategoryName()));

    return $resultPage;
  }

  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
  }
}
