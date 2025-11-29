<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model;

use Magento\Framework\Model\AbstractModel;

class TrackingEvent extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Elsherif\Bosta\Model\ResourceModel\TrackingEvent::class);
    }

    public function getDeliveryId(): ?int
    {
        return $this->getData('delivery_id') ? (int) $this->getData('delivery_id') : null;
    }

    public function setDeliveryId(int $deliveryId): self
    {
        return $this->setData('delivery_id', $deliveryId);
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    public function setDescription(?string $description): self
    {
        return $this->setData('description', $description);
    }

    public function getLocation(): ?string
    {
        return $this->getData('location');
    }

    public function setLocation(?string $location): self
    {
        return $this->setData('location', $location);
    }

    public function getEventTimestamp(): ?string
    {
        return $this->getData('event_timestamp');
    }

    public function setEventTimestamp(string $timestamp): self
    {
        return $this->setData('event_timestamp', $timestamp);
    }
}
