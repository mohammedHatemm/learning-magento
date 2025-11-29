<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use News\Manger\Api\CategoryRepositoryInterface;

class UpdateCategory implements ResolverInterface
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
    $categoryId = $args['id'];
    $input = $args['input'];

    try {
      $category = $this->categoryRepository->getById($categoryId);
    } catch (NoSuchEntityException $e) {
      throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
    }

    if (isset($input['category_name'])) {
      $category->setCategoryName($input['category_name']);
    }
    if (isset($input['category_description'])) {
      $category->setCategoryDescription($input['category_description']);
    }
    if (isset($input['category_status'])) {
      $category->setCategoryStatus($input['category_status']);
    }
    if (isset($input['parent_ids'])) {
      $category->setParentIds($input['parent_ids']);
    }

    try {
      $this->categoryRepository->save($category);
    } catch (\Exception $e) {
      throw new GraphQlInputException(__('Could not update category: %1', $e->getMessage()));
    }

    return [
      'category_id' => $category->getCategoryId(),
      'category_name' => $category->getCategoryName(),
      'category_description' => $category->getCategoryDescription(),
      'category_status' => $category->getCategoryStatus(),
      'created_at' => $category->getCreatedAt(),
      'updated_at' => $category->getUpdatedAt(),
      'parent_ids' => $category->getParentIds(),
    ];
  }
}
