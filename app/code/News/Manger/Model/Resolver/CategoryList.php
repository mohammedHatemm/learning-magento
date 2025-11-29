<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

class CategoryList implements ResolverInterface
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

  /**
   * @var FilterGroupBuilder
   */
  private $filterGroupBuilder;

  public function __construct(
    CategoryRepositoryInterface $categoryRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder,
    FilterBuilder $filterBuilder,
    FilterGroupBuilder $filterGroupBuilder
  ) {
    $this->categoryRepository = $categoryRepository;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->filterBuilder = $filterBuilder;
    $this->filterGroupBuilder = $filterGroupBuilder;
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
    $pageSize = $args['pageSize'] ?? 20;
    $currentPage = $args['currentPage'] ?? 1;
    $filters = $args['filter'] ?? [];

    $this->searchCriteriaBuilder->setPageSize($pageSize);
    $this->searchCriteriaBuilder->setCurrentPage($currentPage);

    // Apply filters
    $this->applyFilters($filters);

    $searchCriteria = $this->searchCriteriaBuilder->create();
    $searchResults = $this->categoryRepository->getList($searchCriteria);

    $items = [];
    foreach ($searchResults->getItems() as $category) {
      // Get complete category data by ID to ensure all fields are loaded
      $fullCategory = $this->categoryRepository->getById($category->getCategoryId());

      $items[] = [
        'category_id' => $fullCategory->getCategoryId(),
        'category_name' => $fullCategory->getCategoryName(),
        'category_description' => $fullCategory->getCategoryDescription(),
        'category_status' => $fullCategory->getCategoryStatus(),
        'created_at' => $fullCategory->getCreatedAt(),
        'updated_at' => $fullCategory->getUpdatedAt(),
        'parent_ids' => $fullCategory->getParentIds(),
        'model' => $fullCategory
      ];
    }

    $totalCount = $searchResults->getTotalCount();
    $totalPages = ceil($totalCount / $pageSize);

    return [
      'items' => $items,
      'page_info' => [
        'page_size' => $pageSize,
        'current_page' => $currentPage,
        'total_pages' => $totalPages
      ],
      'total_count' => $totalCount
    ];
  }

  /**
   * Apply filters to search criteria
   *
   * @param array $filters
   * @return void
   */
  private function applyFilters(array $filters)
  {
    foreach ($filters as $field => $condition) {
      if (isset($condition['eq'])) {
        $this->filterBuilder->setField($field);
        $this->filterBuilder->setValue($condition['eq']);
        $this->filterBuilder->setConditionType('eq');
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
      }

      if (isset($condition['like'])) {
        $this->filterBuilder->setField($field);
        $this->filterBuilder->setValue('%' . $condition['like'] . '%');
        $this->filterBuilder->setConditionType('like');
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
      }

      if (isset($condition['in'])) {
        $this->filterBuilder->setField($field);
        $this->filterBuilder->setValue($condition['in']);
        $this->filterBuilder->setConditionType('in');
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
      }
    }
  }
}
