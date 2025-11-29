<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\NewsRepositoryInterface;

class UpdateNews implements ResolverInterface
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
    if (!isset($args['id']) || !isset($args['input'])) {
      throw new GraphQlInputException(__('ID and input data are required'));
    }

    $newsId = $args['id'];
    $input = $args['input'];

    try {
      // Get existing news
      $news = $this->newsRepository->getById($newsId);

      // Update fields
      if (isset($input['news_title'])) {
        $news->setNewsTitle($input['news_title']);
      }

      if (isset($input['news_content'])) {
        $news->setNewsContent($input['news_content']);
      }

      if (isset($input['news_status'])) {
        $news->setNewsStatus($input['news_status']);
      }

      if (isset($input['category_ids'])) {
        $news->setCategoryIds($input['category_ids']);
      }

      $updatedNews = $this->newsRepository->save($news);

      return [
        'news_id' => $updatedNews->getNewsId(),
        'news_title' => $updatedNews->getNewsTitle(),
        'news_content' => $updatedNews->getNewsContent(),
        'news_status' => $updatedNews->getNewsStatus(),
        'created_at' => $updatedNews->getCreatedAt(),
        'updated_at' => $updatedNews->getUpdatedAt(),
        'category_ids' => $updatedNews->getCategoryIds(),
        'model' => $updatedNews
      ];
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('Could not update news: %1', $e->getMessage()));
    }
  }
}
