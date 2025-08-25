<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use News\Manger\Model\CategoryFactory;

class Edit extends Action
{
  /**
   * @var PageFactory
   */
  protected $resultPageFactory;

  /**
   * @var Registry
   */
  protected $_coreRegistry;

  /**
   * @var CategoryFactory
   */
  protected $_categoryFactory;

  /**
   * @param Context $context
   * @param PageFactory $resultPageFactory
   * @param Registry $registry
   * @param CategoryFactory $categoryFactory
   */
  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    Registry $registry,
    CategoryFactory $categoryFactory
  ) {
    $this->resultPageFactory = $resultPageFactory;
    $this->_coreRegistry = $registry;
    $this->_categoryFactory = $categoryFactory;
    parent::__construct($context);
  }

  /**
   * @inheritDoc
   */
  public function execute()
  {
    $id = $this->getRequest()->getParam('id');
    $model = $this->_categoryFactory->create();

    if ($id) {
      $model->load($id);
      if (!$model->getId()) {
        $this->messageManager->addErrorMessage(__('This category no longer exists.'));
        return $this->_redirect('*/*/');
      }
    }

    $this->_coreRegistry->register('news_category', $model);

    /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
    $resultPage = $this->resultPageFactory->create();

    $resultPage->setActiveMenu('News_Manger::news_header');
    $resultPage->addBreadcrumb(
      $id ? __('Edit Category') : __('New Category'),
      $id ? __('Edit Category') : __('New Category')
    );

    $resultPage->getConfig()->getTitle()->prepend(__('Categories'));
    $resultPage->getConfig()->getTitle()->prepend(
      $model->getId() ? $model->getCategoryName() : __('New Category')
    );

    return $resultPage;
  }

  /**
   * Check if admin has permissions to visit related pages
   *
   * @return bool
   */
  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed('News_Manger::admin');
  }
}
