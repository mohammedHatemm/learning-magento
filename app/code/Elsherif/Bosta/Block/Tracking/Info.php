<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Block\Tracking;

use Elsherif\Bosta\Model\ResourceModel\Delivery\CollectionFactory as DeliveryCollectionFactory;
use Elsherif\Bosta\Model\ResourceModel\TrackingEvent\CollectionFactory as TrackingEventCollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Info extends Template
{
    private DeliveryCollectionFactory $deliveryCollectionFactory;
    private TrackingEventCollectionFactory $trackingEventCollectionFactory;

    public function __construct(
        Context $context,
        DeliveryCollectionFactory $deliveryCollectionFactory,
        TrackingEventCollectionFactory $trackingEventCollectionFactory,
        array $data = []
    ) {
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
        $this->trackingEventCollectionFactory = $trackingEventCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getTrackingNumber()
    {
        return $this->getRequest()->getParam('tracking');
    }

    public function getDelivery()
    {
        $trackingNumber = $this->getTrackingNumber();
        if (!$trackingNumber) {
            return null;
        }

        $collection = $this->deliveryCollectionFactory->create();
        return $collection->addFieldToFilter('tracking_number', $trackingNumber)->getFirstItem();
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

    public function getBostaTrackingUrl($trackingNumber)
    {
        return 'https://app.bosta.co/tracking/' . $trackingNumber;
    }

    public function formatStatus($status)
    {
        $statuses = [
            'PENDING' => __('Pending'),
            'IN_TRANSIT' => __('In Transit'),
            'OUT_FOR_DELIVERY' => __('Out for Delivery'),
            'DELIVERED' => __('Delivered'),
            'CANCELLED' => __('Cancelled'),
            'TERMINATED' => __('Terminated')
        ];
        return $statuses[$status] ?? str_replace('_', ' ', ucwords(strtolower($status)));
    }

    public function getStatusIcon($status)
    {
        $icons = [
            'PENDING' => '⏱️',
            'IN_TRANSIT' => '🚚',
            'OUT_FOR_DELIVERY' => '📦',
            'DELIVERED' => '✅',
            'CANCELLED' => '❌',
            'TERMINATED' => '⛔'
        ];
        return $icons[$status] ?? '📍';
    }
}
