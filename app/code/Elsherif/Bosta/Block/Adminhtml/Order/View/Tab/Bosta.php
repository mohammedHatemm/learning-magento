<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Block\Adminhtml\Order\View\Tab;

use Elsherif\Bosta\Model\ResourceModel\Delivery\CollectionFactory as DeliveryCollectionFactory;
use Elsherif\Bosta\Model\ResourceModel\TrackingEvent\CollectionFactory as TrackingEventCollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;

class Bosta extends Template implements TabInterface
{
    protected $_template = 'Elsherif_Bosta::order/view/tab/bosta.phtml';

    private Registry $coreRegistry;
    private DeliveryCollectionFactory $deliveryCollectionFactory;
    private TrackingEventCollectionFactory $trackingEventCollectionFactory;
    private FormKey $formKey;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DeliveryCollectionFactory $deliveryCollectionFactory,
        TrackingEventCollectionFactory $trackingEventCollectionFactory,
        FormKey $formKey,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
        $this->trackingEventCollectionFactory = $trackingEventCollectionFactory;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getDelivery()
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }

        $collection = $this->deliveryCollectionFactory->create();
        return $collection->addFieldToFilter('order_id', $order->getId())->getFirstItem();
    }

    public function getTrackingEvents()
    {
        $delivery = $this->getDelivery();
        if (!$delivery || !$delivery->getId()) {
            return [];
        }

        $collection = $this->trackingEventCollectionFactory->create();
        $collection->addFieldToFilter('delivery_id', $delivery->getId());
        $collection->setOrder('event_timestamp', 'DESC');
        return $collection;
    }

    public function getTabLabel()
    {
        return __('Bosta Delivery');
    }

    public function getTabTitle()
    {
        return __('Bosta Delivery Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        // Show tab if order uses Bosta shipping, even if delivery doesn't exist yet
        $order = $this->getOrder();
        if (!$order) {
            return true;
        }

        $shippingMethod = $order->getShippingMethod();
        if (!$shippingMethod || strpos($shippingMethod, 'customshipping') === false) {
            return true;
        }

        return false;
    }

    public function getBostaTrackingUrl($trackingNumber)
    {
        return 'https://app.bosta.co/tracking/' . $trackingNumber;
    }

    public function formatStatus($status)
    {
        return str_replace('_', ' ', ucwords(strtolower($status)));
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
