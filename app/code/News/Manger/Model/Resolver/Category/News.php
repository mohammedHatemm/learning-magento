<?php

namespace News\Manger\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class News implements ResolverInterface
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
      return [];
    }
  }
}
