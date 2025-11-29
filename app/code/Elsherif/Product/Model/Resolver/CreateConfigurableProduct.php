<?php

namespace Elsherif\Product\Model\Resolver;

use Elsherif\Product\Api\ConfigurableProductInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CreateConfigurableProduct implements ResolverInterface
{
    private $configurableProduct;

    public function __construct(ConfigurableProductInterface $configurableProduct)
    {
        $this->configurableProduct = $configurableProduct;
    }

    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    )
    {
        $input = $args['input'];

        $result = $this->configurableProduct->create(
            $input['name'],
            $input['sku'],
            $input['price'],
            $input['category_ids'],
            $input['attributes'],
            $input['variants']
        );

        return [
            'product_id' => $result->getProductId(),
            'sku' => $result->getSku(),
            'message' => $result->getMessage(),
            'variant_ids' => []
        ];
    }
}
