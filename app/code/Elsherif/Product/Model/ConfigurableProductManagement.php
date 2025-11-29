<?php

namespace Elsherif\Product\Model;

use Elsherif\Product\Api\ConfigurableProductInterface;
use Elsherif\Product\Service\CreateConfigurableProduct;

class ConfigurableProductManagement implements ConfigurableProductInterface
{
    private $createService;

    public function __construct(CreateConfigurableProduct $createService)
    {
        $this->createService = $createService;
    }

    public function create(
        $name,
        $sku,
        $price,
        array $categoryIds,
        array $attributes,
        array $variants
    )
    {
        // تحويل البيانات للـ Service
        $colors = $this->extractAttributeValues($attributes, 'color');
        $sizes = $this->extractAttributeValues($attributes, 'size');

        $product = $this->createService->execute(
            $name,
            $sku,
            $colors,
            $sizes,
            $price,
            100, // default qty
            $categoryIds
        );

        // إرجاع Response
        $response = new \Magento\Framework\DataObject();
        $response->setProductId($product->getId());
        $response->setSku($product->getSku());
        $response->setMessage('Product created successfully');

        return $response;
    }

    private function extractAttributeValues($attributes, $code)
    {
        foreach ($attributes as $attr) {
            if ($attr['code'] === $code) {
                return $attr['values'];
            }
        }
        return [];
    }
}
