<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use News\Manger\Model\CategoryFactory;

class Edit extends Action
{
  const ADMIN_RESOURCE = 'News_Manger::category_save';

  /**
   * @var PageFactory
   */
  protected $_resultPageFactory;

  /**
   * @var Registry
   */
  protected $_coreRegistry;

  /**
   * @var CategoryFactory
   */
  protected $categoryFactory;

  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    Registry $registry,
    CategoryFactory $categoryFactory
  ) {
    parent::__construct($context);
    $this->_resultPageFactory = $resultPageFactory;
    $this->_coreRegistry = $registry;
    $this->categoryFactory = $categoryFactory;
  }

  /**
   * Init admin category edit page
   *
   * @return \Magento\Framework\View\Result\Page
   */
  protected function _initAction()
  {
    $resultPage = $this->_resultPageFactory->create();
    if (!$resultPage) {
      throw new \Exception('Result Page is FALSE - layout handle missing?');
    }

    $resultPage->setActiveMenu('News_Manger::category')
      ->addBreadcrumb(__('Category'), __('Category'))
      ->addBreadcrumb(__('Manage Categories'), __('Manage Categories'));

    return $resultPage;
  }

  /**
   * Edit action
   */
  public function execute()
  {
    $id = $this->getRequest()->getParam('category_id');
    $model = $this->categoryFactory->create();

    if ($id) {
      $model->load($id);
      if (!$model->getId()) {
        $this->messageManager->addErrorMessage(__('This category no longer exists.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
      }
    }

    $data = $this->_getSession()->getFormData(true);
    if (!empty($data)) {
      $model->setData($data);
    }

    // Register model to use it in the form block
    $this->_coreRegistry->register('news_category', $model);

    $resultPage = $this->_initAction();
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
}
