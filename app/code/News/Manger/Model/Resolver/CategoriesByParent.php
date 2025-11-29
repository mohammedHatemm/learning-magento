<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class CategoriesByParent implements ResolverInterface
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
    if (!isset($args['parentId'])) {
      throw new GraphQlInputException(__('Parent ID is required'));
    }

    $parentId = $args['parentId'];

    try {
      $childrenResult = $this->categoryRepository->getChildren($parentId);
      $categories = [];

      foreach ($childrenResult->getItems() as $category) {
        $categories[] = [
          'category_id' => $category->getCategoryId(),
          'category_name' => $category->getCategoryName(),
          'category_description' => $category->getCategoryDescription(),
          'category_status' => $category->getCategoryStatus(),
          'created_at' => $category->getCreatedAt(),
          'updated_at' => $category->getUpdatedAt(),
          'parent_ids' => $category->getParentIds(),
          'model' => $category
        ];
      }

      return $categories;
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('Could not get categories: %1', $e->getMessage()));
    }
  }
}
