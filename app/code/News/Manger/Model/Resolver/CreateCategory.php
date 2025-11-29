<?php

declare(strict_types=1);


namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;
use News\Manger\Api\Data\CategoryInterfaceFactory;
use Psr\Log\LoggerInterface;

class CreateCategory implements ResolverInterface
{
  /**
   * @var CategoryRepositoryInterface
   */
  private $categoryRepository;

  /**
   * @var CategoryInterfaceFactory (Data Object Factory)
   */
  private $categoryDataFactory;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * Constructor
   *
   * @param CategoryRepositoryInterface $categoryRepository
   * @param CategoryInterfaceFactory $categoryDataFactory
   * @param LoggerInterface $logger
   */
  public function __construct(
    CategoryRepositoryInterface $categoryRepository,
    CategoryInterfaceFactory $categoryDataFactory,
    LoggerInterface $logger
  ) {
    $this->categoryRepository = $categoryRepository;
    $this->categoryDataFactory = $categoryDataFactory;
    $this->logger = $logger;
  }

  /**
   * Resolve method for createCategory mutation
   *
   * @param Field $field
   * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
   * @param ResolveInfo $info
   * @param array|null $value
   * @param array|null $args
   *
   * @return array
   * @throws GraphQlInputException
   */
  public function resolve(
    Field $field,
    $context,
    ResolveInfo $info,
    array $value = null,
    array $args = null
  ) {
    if (!isset($args['input'])) {
      throw new GraphQlInputException(__('Input data is required'));
    }

    $input = $args['input'];
    $this->logger->info('CreateCategory Input: ' . json_encode($input));

    if (empty($input['category_name'])) {
      throw new GraphQlInputException(__('Category name is required'));
    }

    try {
      // إنشاء Data Object جديد من Factory
      $categoryData = $this->categoryDataFactory->create();
      $categoryData->setCategoryName($input['category_name']);
      $categoryData->setCategoryDescription($input['category_description'] ?? '');
      $categoryData->setCategoryStatus(isset($input['category_status']) ? (int)$input['category_status'] : 1);

      if (isset($input['parent_ids']) && is_array($input['parent_ids'])) {
        $categoryData->setParentIds($input['parent_ids']);
      } else {
        $categoryData->setParentIds([]);
      }

      // حفظ الكاتيجوري عبر الريبو
      $savedCategory = $this->categoryRepository->save($categoryData);

      $this->logger->info('Category created with ID: ' . $savedCategory->getCategoryId());

      // تجهيز النتيجة للإرجاع
      $result = [
        'category_id' => (int)$savedCategory->getCategoryId(),
        'category_name' => $savedCategory->getCategoryName(),
        'category_description' => $savedCategory->getCategoryDescription(),
        'category_status' => (int)$savedCategory->getCategoryStatus(),
        'created_at' => $savedCategory->getCreatedAt(),
        'updated_at' => $savedCategory->getUpdatedAt(),
        'parent_ids' => $savedCategory->getParentIds(),
        'model' => $savedCategory
      ];

      $this->logger->info('Final createCategory result: ' . json_encode($result));

      return $result;
    } catch (\Exception $e) {
      $this->logger->error('CreateCategory Error: ' . $e->getMessage());
      $this->logger->error('CreateCategory Stack Trace: ' . $e->getTraceAsString());
      throw new GraphQlInputException(__('Could not create category: %1', $e->getMessage()));
    }
  }
}
