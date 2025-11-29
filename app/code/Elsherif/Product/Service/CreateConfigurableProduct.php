<?php

namespace Elsherif\Product\Service;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
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
    public function execute(
        $name,
        $skuprefix,
        array $colors,
        array $sizes,
        $price,
        $qty,
        array $categories = []
    )
    {
        try {
            // 1. إنشاء Simple Products أولاً
            $simpleProducts = $this->createSimpleProducts(
                $skuprefix,
                $colors,
                $sizes,
                $price,
                $qty
            );

            // 2. إنشاء Configurable Product
            $configurableProduct = $this->createConfigurable(
                $name,
                $skuprefix,
                $price,
                $categories
            );

            // 3. ربط Simple Products بـ Configurable
            $this->linkSimpleProducts(
                $configurableProduct,
                $simpleProducts,
                ['color', 'size'] // الـ attributes المستخدمة
            );

            return $configurableProduct;

        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not create configurable product: %1', $e->getMessage())
            );
        }
    }

    /**
     * إنشاء Simple Products لكل تركيبة من Color و Size
     */
    private function createSimpleProducts($skuprefix, $colors, $sizes, $basePrice, $qty)
    {
        $simpleProducts = [];

        foreach ($colors as $colorValue => $colorLabel) {
            foreach ($sizes as $sizeValue => $sizeLabel) {
                $product = $this->productFactory->create();

                $sku = sprintf('%s-%s-%s', $skuprefix, $colorValue, $sizeValue);

                $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
                    ->setAttributeSetId(4) // Default attribute set
                    ->setName(sprintf('%s %s', $colorLabel, $sizeLabel))
                    ->setSku($sku)
                    ->setPrice($basePrice)
                    ->setStatus(Status::STATUS_ENABLED)
                    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
                    ->setColor($colorValue) // تأكد أن الـ attribute موجود
                    ->setSize($sizeValue);  // تأكد أن الـ attribute موجود

                $product = $this->productRepository->save($product);

                // تحديث Stock
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty($qty);
                $stockItem->setIsInStock(true);
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

                $simpleProducts[] = $product;
            }
        }

        return $simpleProducts;
    }

    /**
     * إنشاء Configurable Product الرئيسي
     */
    private function createConfigurable($name, $sku, $price, $categories)
    {
        $product = $this->productFactory->create();

        $product->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName($name)
            ->setSku($sku)
            ->setPrice($price)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_BOTH);

        $product = $this->productRepository->save($product);

        // إضافة Categories
        if (!empty($categories)) {
            $this->categoryLinkManagement->assignProductToCategories(
                $product->getSku(),
                $categories
            );
        }

        return $product;
    }

    /**
     * ربط Simple Products بـ Configurable
     */
    private function linkSimpleProducts($configurableProduct, $simpleProducts, $attributes)
    {
        $attributesData = [];
        $associatedProductIds = [];

        foreach ($simpleProducts as $simpleProduct) {
            $associatedProductIds[] = $simpleProduct->getId();
        }

        // إعداد بيانات الـ Configurable Options
        foreach ($attributes as $attributeCode) {
            $attribute = $configurableProduct->getResource()
                ->getAttribute($attributeCode);

            if ($attribute) {
                $attributesData[] = [
                    'attribute_id' => $attribute->getId(),
                    'code' => $attributeCode,
                    'label' => $attribute->getStoreLabel(),
                    'position' => 0,
                    'values' => $this->getAttributeValues($attribute)
                ];
            }
        }

        // ربط المنتجات
        $configurableProduct->setConfigurableAttributesData($attributesData);
        $configurableProduct->setAssociatedProductIds($associatedProductIds);

        $this->productRepository->save($configurableProduct);
    }

    /**
     * الحصول على قيم الـ Attribute
     */
    private function getAttributeValues($attribute)
    {
        $values = [];
        $options = $attribute->getOptions();

        foreach ($options as $option) {
            if ($option->getValue()) {
                $values[] = [
                    'value_index' => $option->getValue()
                ];
            }
        }

        return $values;
    }
}
