<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;

class NewAction extends Action
{
  protected $_resultForwardFactory;

  public function __construct(
    Context $context,
    ForwardFactory $resultForwardFactory
  ) {
    parent::__construct($context);
    $this->_resultForwardFactory = $resultForwardFactory;
  }

  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed('News_Manger::category_save');
  }

  public function execute()
  {
    return $this->_resultForwardFactory->create()->forward('edit');
  }
}
