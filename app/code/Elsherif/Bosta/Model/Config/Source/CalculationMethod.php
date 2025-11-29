<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CalculationMethod implements OptionSourceInterface
{
    const METHOD_FIXED = 'fixed';
    const METHOD_BY_WEIGHT = 'by_weight';
    const METHOD_BY_CITY = 'by_city';
    const METHOD_BY_CITY_AND_WEIGHT = 'by_city_and_weight';

    /**
     * Get options
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::METHOD_FIXED,
                'label' => __('Fixed Rate')
            ],
            [
                'value' => self::METHOD_BY_WEIGHT,
                'label' => __('By Weight')
            ],
            [
                'value' => self::METHOD_BY_CITY,
                'label' => __('By City/Zone')
            ],
            [
                'value' => self::METHOD_BY_CITY_AND_WEIGHT,
                'label' => __('By City and Weight (Recommended)')
            ]
        ];
    }
}
