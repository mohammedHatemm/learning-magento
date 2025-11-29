<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\NewsRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class DeleteNews implements ResolverInterface
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
    // التحقق من وجود الـ ID
    if (!isset($args['id']) || empty($args['id'])) {
      throw new GraphQlInputException(__('News ID is required'));
    }

    $newsId = (int) $args['id'];

    // التحقق من صحة الـ ID
    if ($newsId <= 0) {
      throw new GraphQlInputException(__('Invalid news ID provided'));
    }

    try {
      // التحقق من وجود الخبر قبل الحذف
      $news = $this->newsRepository->getById($newsId);

      if (!$news) {
        throw new GraphQlNoSuchEntityException(
          __('News with ID "%1" does not exist', $newsId)
        );
      }

      // تنفيذ عملية الحذف
      $result = $this->newsRepository->deleteById($newsId);

      return $result;
    } catch (NoSuchEntityException $e) {
      throw new GraphQlNoSuchEntityException(
        __('News with ID "%1" does not exist', $newsId)
      );
    } catch (LocalizedException $e) {
      throw new GraphQlInputException(
        __('Could not delete news: %1', $e->getMessage())
      );
    } catch (\Exception $e) {
      throw new GraphQlInputException(
        __('An error occurred while deleting the news: %1', $e->getMessage())
      );
    }
  }
}
