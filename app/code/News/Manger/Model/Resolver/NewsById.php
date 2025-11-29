<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\NewsRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

class NewsById implements ResolverInterface
{
  /**
   * @var NewsRepositoryInterface
   */
  private $newsRepository;

  public function __construct(
    NewsRepositoryInterface $newsRepository
  ) {
    $this->newsRepository = $newsRepository;
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
    if (!isset($args['id'])) {
      throw new GraphQlInputException(__('News ID is required'));
    }

    try {
      $news = $this->newsRepository->getById($args['id']);
      return [
        'news_id' => $news->getNewsId(),
        'news_title' => $news->getNewsTitle(),
        'news_content' => $news->getNewsContent(),
        'news_status' => $news->getNewsStatus(),
        'created_at' => $news->getCreatedAt(),
        'updated_at' => $news->getUpdatedAt(),
        'category_ids' => $news->getCategoryIds(),
        'model' => $news
      ];
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('News not found'));
    }
  }
}
