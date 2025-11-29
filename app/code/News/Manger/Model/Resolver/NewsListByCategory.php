<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class NewsListByCategory implements ResolverInterface
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
    if (!isset($args['categoryId'])) {
      throw new GraphQlInputException(__('Category ID is required'));
    }

    $categoryId = $args['categoryId'];

    try {
      $newsResult = $this->categoryRepository->getNews($categoryId);
      $newsList = [];

      foreach ($newsResult->getItems() as $news) {
        $newsList[] = [
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

      return $newsList;
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('Could not get news: %1', $e->getMessage()));
    }
  }
}
