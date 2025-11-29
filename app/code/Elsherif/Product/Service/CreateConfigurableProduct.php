<?php

namespace Elsherif\Product\Service;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\CouldNotSaveException;

class CreateConfigurableProduct
{
    private $productRepository;
    private $productFactory;
    private $configurableType;
    private $stockRegistry;
    private $optionsFactory;
    private $categoryLinkManagement;

    public function __construct(
        ProductRepositoryInterface      $productRepository,
        ProductFactory                  $productFactory,
        Configurable                    $configurableType,
        StockRegistryInterface          $stockRegistry,
        OptionsFactory                  $optionsFactory,
        CategoryLinkManagementInterface $categoryLinkManagement
    )
    {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->configurableType = $configurableType;
        $this->stockRegistry = $stockRegistry;
        $this->optionsFactory = $optionsFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
    }

    /**
     * Create configurable product with simple products
     *
     * @param string $name
     * @param string $skuprefix
     * @param array $colors ['red' => 'Red', 'blue' => 'Blue']
     * @param array $sizes ['s' => 'Small', 'm' => 'Medium']
     * @param float $price
     * @param int $qty
     * @param array $categories
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws CouldNotSaveException
     */


}
