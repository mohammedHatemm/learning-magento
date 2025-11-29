<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Adminhtml\Delivery;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Elsherif_Bosta::delivery';

    private PageFactory $pageFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Elsherif_Bosta::delivery');
        $page->getConfig()->getTitle()->prepend(__('Bosta Deliveries'));
        return $page;
    }
}
