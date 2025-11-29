<?php

namespace News\Manger\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class Children implements ResolverInterface
{
  /**
   * @var CategoryRepositoryInterface
   */
  private $categoryRepository;

  public function __construct(
    CategoryRepositoryInterface $categoryRepository
  ) {
    $this->categoryRepository = $categoryRepository;
  }

  /**
   * @inheritdoc
   */
  public function resolve(
    Field $field,
    $context,
    ResolveInfo $info,
    array $value = null,
    array $args = null
  ) {
    if (!isset($value['category_id'])) {
      return [];
    }

    $categoryId = $value['category_id'];

    try {
      $childrenResult = $this->categoryRepository->getChildren($categoryId);
      $children = [];

      foreach ($childrenResult->getItems() as $child) {
        $children[] = [
          'category_id' => $child->getCategoryId(),
          'category_name' => $child->getCategoryName(),
          'category_description' => $child->getCategoryDescription(),
          'category_status' => $child->getCategoryStatus(),
          'created_at' => $child->getCreatedAt(),
          'updated_at' => $child->getUpdatedAt(),
          'parent_ids' => $child->getParentIds(),
          'model' => $child
        ];
      }

      return $children;
    } catch (\Exception $e) {
      return [];
    }
  }
}
