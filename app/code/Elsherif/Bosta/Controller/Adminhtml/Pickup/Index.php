<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Adminhtml\Pickup;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Elsherif_Bosta::pickup';

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
        $page->setActiveMenu('Elsherif_Bosta::pickup');
        $page->getConfig()->getTitle()->prepend(__('Bosta Pickups'));
        return $page;
    }
}
