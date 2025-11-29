<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class DeleteCategory implements ResolverInterface
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
    // التحقق من وجود الـ ID
    if (!isset($args['id']) || empty($args['id'])) {
      throw new GraphQlInputException(__('Category ID is required'));
    }

    $categoryId = (int) $args['id'];

    // التحقق من صحة الـ ID
    if ($categoryId <= 0) {
      throw new GraphQlInputException(__('Invalid category ID provided'));
    }

    try {
      // التحقق من وجود الفئة قبل الحذف
      $category = $this->categoryRepository->getById($categoryId);

      if (!$category) {
        throw new GraphQlNoSuchEntityException(
          __('Category with ID "%1" does not exist', $categoryId)
        );
      }

      // تنفيذ عملية الحذف
      $result = $this->categoryRepository->deleteById($categoryId);

      return $result;
    } catch (NoSuchEntityException $e) {
      throw new GraphQlNoSuchEntityException(
        __('Category with ID "%1" does not exist', $categoryId)
      );
    } catch (LocalizedException $e) {
      throw new GraphQlInputException(
        __('Could not delete category: %1', $e->getMessage())
      );
    } catch (\Exception $e) {
      throw new GraphQlInputException(
        __('An error occurred while deleting the category: %1', $e->getMessage())
      );
    }
  }
}
