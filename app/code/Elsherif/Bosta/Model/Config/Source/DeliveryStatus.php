<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DeliveryStatus implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'PENDING', 'label' => __('Pending')],
            ['value' => 'PICKED_UP', 'label' => __('Picked Up')],
            ['value' => 'IN_TRANSIT', 'label' => __('In Transit')],
            ['value' => 'OUT_FOR_DELIVERY', 'label' => __('Out for Delivery')],
            ['value' => 'DELIVERED', 'label' => __('Delivered')],
            ['value' => 'CANCELLED', 'label' => __('Cancelled')],
            ['value' => 'TERMINATED', 'label' => __('Terminated')],
            ['value' => 'EXCEPTION', 'label' => __('Exception')],
        ];
    }
}
