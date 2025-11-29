<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\NewsRepositoryInterface;

class AddNewsToCategory implements ResolverInterface
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
    if (!isset($args['newsId']) || !isset($args['categoryId'])) {
      throw new GraphQlInputException(__('News ID and Category ID are required'));
    }

    $newsId = $args['newsId'];
    $categoryId = $args['categoryId'];

    try {
      $result = $this->newsRepository->addCategory($newsId, $categoryId);
      return $result;
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('Could not add news to category: %1', $e->getMessage()));
    }
  }
}
