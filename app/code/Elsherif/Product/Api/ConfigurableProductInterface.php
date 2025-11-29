<?php

namespace Elsherif\Product\Api;

interface ConfigurableProductInterface
{
    /**
     * Create configurable product with variants
     *
     * @param string $name
     * @param string $sku
     * @param float $price
     * @param int[] $categoryIds
     * @param mixed[] $attributes
     * @param mixed[] $variants
     * @return              \Elsherif\Product\Api\Data\ConfigurableProductDataInterface
     * @throws              \Magento\Framework\Exception\CouldNotSaveException
     */
    public function create(
        $name,
        $sku,
        $price,
        array $categoryIds,
        array $attributes,
        array $variants
    );
}
