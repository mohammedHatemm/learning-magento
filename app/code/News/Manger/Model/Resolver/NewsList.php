<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\NewsRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Psr\Log\LoggerInterface;

class NewsList implements ResolverInterface
{
  /**
   * @var NewsRepositoryInterface
   */
  private $newsRepository;

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

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(
    NewsRepositoryInterface $newsRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder,
    FilterBuilder $filterBuilder,
    FilterGroupBuilder $filterGroupBuilder,
    LoggerInterface $logger
  ) {
    $this->newsRepository = $newsRepository;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->filterBuilder = $filterBuilder;
    $this->filterGroupBuilder = $filterGroupBuilder;
    $this->logger = $logger;
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

    $this->logger->info('NewsList Resolver called with args', ['pageSize' => $pageSize, 'currentPage' => $currentPage, 'filters' => $filters]);

    $this->searchCriteriaBuilder->setPageSize($pageSize);
    $this->searchCriteriaBuilder->setCurrentPage($currentPage);

    // Apply filters
    $this->applyFilters($filters);

    $searchCriteria = $this->searchCriteriaBuilder->create();

    // $this->logger->info('Built SearchCriteria', ['searchCriteria' => $searchCriteria->getFilters()]);

    try {
      $searchResults = $this->newsRepository->getList($searchCriteria);
    } catch (\Exception $e) {
      $this->logger->error('Error fetching news list: ' . $e->getMessage());
      throw $e;
    }

    $items = [];
    foreach ($searchResults->getItems() as $news) {
      $items[] = [
        'news_id' => $news->getNewsId(),
        'news_title' => $news->getNewsTitle(),
        'news_content' => $news->getNewsContent(),
        'news_status' => $news->getNewsStatus(),
        'created_at' => $news->getCreatedAt(),
        'updated_at' => $news->getUpdatedAt(),
        'category_ids' => $news->getCategoryIds(),
        'model' => $news
      ];
    }

    $totalCount = $searchResults->getTotalCount();
    $totalPages = ceil($totalCount / $pageSize);

    $this->logger->info('NewsList results', [
      'total_count' => $totalCount,
      'items_count' => count($items),
      'page_info' => [
        'page_size' => $pageSize,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
      ]
    ]);

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
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create(), null);
        $this->logger->info("Applied filter eq on {$field} with value {$condition['eq']}");
      }

      if (isset($condition['like'])) {
        $this->filterBuilder->setField($field);
        $this->filterBuilder->setValue('%' . $condition['like'] . '%');
        $this->filterBuilder->setConditionType('like');
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create(), null);
        $this->logger->info("Applied filter like on {$field} with value %{$condition['like']}%");
      }

      if (isset($condition['in'])) {
        $this->filterBuilder->setField($field);
        $this->filterBuilder->setValue($condition['in']);
        $this->filterBuilder->setConditionType('in');
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create(), null);
        $this->logger->info("Applied filter in on {$field} with values " . implode(',', $condition['in']));
      }
    }
  }
}
