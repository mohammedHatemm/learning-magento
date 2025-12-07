<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Block\Adminhtml\Order\View\Tab;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\ResourceModel\Delivery\CollectionFactory as DeliveryCollectionFactory;
use Elsherif\Bosta\Model\ResourceModel\TrackingEvent\CollectionFactory as TrackingEventCollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class Bosta extends Template
{
    protected $_template = 'Elsherif_Bosta::order/view/tab/bosta.phtml';

    private Registry $coreRegistry;
    private DeliveryCollectionFactory $deliveryCollectionFactory;
    private TrackingEventCollectionFactory $trackingEventCollectionFactory;
    private BostaHelper $bostaHelper;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DeliveryCollectionFactory $deliveryCollectionFactory,
        TrackingEventCollectionFactory $trackingEventCollectionFactory,
        BostaHelper $bostaHelper,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
        $this->trackingEventCollectionFactory = $trackingEventCollectionFactory;
        $this->bostaHelper = $bostaHelper;
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

    /**
     * Check if this block should be displayed
     * Only show for orders using Bosta shipping
     *
     * @return bool
     */
    public function canShow()
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $shippingMethod = $order->getShippingMethod();
        return $shippingMethod && strpos($shippingMethod, 'customshipping') !== false;
    }

    public function getBostaTrackingUrl($trackingNumber)
    {
        // Bosta's public customer tracking page
        return 'https://bosta.co/tracking-shipments?shipment-number=' . urlencode($trackingNumber);
    }

    public function formatStatus($status)
    {
        return str_replace('_', ' ', ucwords(strtolower($status)));
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get shipping policy information
     *
     * @return array
     */
    public function getShippingPolicy()
    {
        return $this->bostaHelper->getShippingPolicy($this->getDelivery());
    }
}
