<?php

namespace News\Manger\Model\Resolver\News;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

class Categories implements ResolverInterface
{
  /**
   * @var CategoryRepositoryInterface
   */
  private $categoryRepository;

  /**
   * @var SearchCriteriaBuilder
   */
  private $searchCriteriaBuilder;

  /**
   * @var FilterBuilder
   */
  private $filterBuilder;

  public function __construct(
    CategoryRepositoryInterface $categoryRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder,
    FilterBuilder $filterBuilder
  ) {
    $this->categoryRepository = $categoryRepository;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->filterBuilder = $filterBuilder;
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
    if (!isset($value['category_ids']) || empty($value['category_ids'])) {
      return [];
    }

    $categoryIds = $value['category_ids'];
    $categories = [];

    foreach ($categoryIds as $categoryId) {
      try {
        $category = $this->categoryRepository->getById($categoryId);
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
      } catch (\Exception $e) {
        // Skip invalid categories
        continue;
      }
    }

    return $categories;
  }
}
