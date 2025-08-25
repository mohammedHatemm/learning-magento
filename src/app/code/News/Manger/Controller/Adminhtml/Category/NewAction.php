<?php

namespace News\Manger\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\App\Action\Context;

class NewAction extends Action
{
  /**
   * @var ForwardFactory
   */
  protected $resultForwardFactory;

  /**
   * @param Context $context
   * @param ForwardFactory $resultForwardFactory
   */
  public function __construct(
    Context $context,
    ForwardFactory $resultForwardFactory
  ) {
    $this->resultForwardFactory = $resultForwardFactory;
    parent::__construct($context);
  }

  /**
   * Forward to edit
   *
   * @return Forward
   */
  public function execute()
  {
    /** @var Forward $resultForward */
    $resultForward = $this->resultForwardFactory->create();
    return $resultForward->forward('edit');
  }
}
