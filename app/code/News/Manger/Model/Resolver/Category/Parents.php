<?php

namespace News\Manger\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class Parents implements ResolverInterface
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
      $parentsResult = $this->categoryRepository->getParents($categoryId);
      $parents = [];

      foreach ($parentsResult->getItems() as $parent) {
        $parents[] = [
          'category_id' => $parent->getCategoryId(),
          'category_name' => $parent->getCategoryName(),
          'category_description' => $parent->getCategoryDescription(),
          'category_status' => $parent->getCategoryStatus(),
          'created_at' => $parent->getCreatedAt(),
          'updated_at' => $parent->getUpdatedAt(),
          'parent_ids' => $parent->getParentIds(),
          'model' => $parent
        ];
      }

      return $parents;
    } catch (\Exception $e) {
      return [];
    }
  }
}
