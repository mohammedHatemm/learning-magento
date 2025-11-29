<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model;

use Magento\Framework\Model\AbstractModel;

class Delivery extends AbstractModel
{
    const CACHE_TAG = 'bosta_delivery';

    const DELIVERY_TYPE_PACKAGE = 10;
    const DELIVERY_TYPE_COD = 20;

    protected function _construct()
    {
        $this->_init(\Elsherif\Bosta\Model\ResourceModel\Delivery::class);
    }

    public function getOrderId(): ?int
    {
        return $this->getData('order_id') ? (int) $this->getData('order_id') : null;
    }

    public function setOrderId(int $orderId): self
    {
        return $this->setData('order_id', $orderId);
    }

    public function getTrackingNumber(): ?string
    {
        return $this->getData('tracking_number');
    }

    public function setTrackingNumber(string $trackingNumber): self
    {
        return $this->setData('tracking_number', $trackingNumber);
    }

    public function getBostaDeliveryId(): ?string
    {
        return $this->getData('bosta_delivery_id');
    }

    public function setBostaDeliveryId(string $deliveryId): self
    {
        return $this->setData('bosta_delivery_id', $deliveryId);
    }

    public function getDeliveryType(): int
    {
        return (int) $this->getData('delivery_type');
    }

    public function setDeliveryType(int $type): self
    {
        return $this->setData('delivery_type', $type);
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    public function getCodAmount(): ?float
    {
        return $this->getData('cod_amount') ? (float) $this->getData('cod_amount') : null;
    }

    public function setCodAmount(float $amount): self
    {
        return $this->setData('cod_amount', $amount);
    }

    public function getShippingCost(): ?float
    {
        return $this->getData('shipping_cost') ? (float) $this->getData('shipping_cost') : null;
    }

    public function setShippingCost(float $cost): self
    {
        return $this->setData('shipping_cost', $cost);
    }

    public function getDeliveryData(): ?array
    {
        $data = $this->getData('delivery_data');
        return $data ? json_decode($data, true) : null;
    }

    public function setDeliveryData(array $data): self
    {
        return $this->setData('delivery_data', json_encode($data));
    }

    public function getAwbUrl(): ?string
    {
        return $this->getData('awb_url');
    }

    public function setAwbUrl(string $url): self
    {
        return $this->setData('awb_url', $url);
    }
}
