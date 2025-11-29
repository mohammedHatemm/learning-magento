<?php

namespace Elsherif\Product\Api;

use Magento\Tests\NamingConvention\true\string;

interface ConfigurableProductDataInterface
{

    /**
     * @return int
     * */

    public function getProductId();

    /**
     * @param int $productId
     * @return $this
     * */
    public function setProductId($productId);


    /**
     * @return string
     *
     */
    public function getSku();

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * @param string
     */
    public function getMassage();

    /**
     * @param string $massage
     * @return $this
     */

    public function setMassage($massage);

    /**
     * @return  int[]
     */
    public function getVariantIds();

    /**
     * @param int[] $variantIds
     * @retuen $this
     * */

    public function setVariantIds(array $variantIds);


}
