<?php

namespace News\Manger\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use News\Manger\Api\Data\CategoryInterface;
use News\Manger\Api\Data\CategorySearchResultsInterface;
use News\Manger\Api\Data\NewsSearchResultsInterface;
use News\Manger\Model\CategoryFactory;

/**
 * Interface for category repository.
 *
 * @api
 */
interface CategoryRepositoryInterface
{
  /**
   * Save a category.
   *
   * @param CategoryInterface $categoryto
   * @param bool $saveOptions
   * @return CategoryInterface
   * @throws CouldNotSaveException
   */
  public function save(CategoryInterface $category, $saveOptions = false): CategoryInterface;

  /**
   * Retrieve a category by ID.
   *
   * @param int $categoryId
   * @return CategoryInterface
   * @throws NoSuchEntityException
   */
  public function getById($categoryId);

  /**
   * Retrieve categories matching the specified criteria.
   *
   * @param SearchCriteriaInterface|null $searchCriteria
   * @return CategorySearchResultsInterface
   */
  public function getList(SearchCriteriaInterface $searchCriteria = null);

  /**
   * Delete a category.
   *
   * @param CategoryInterface $category
   * @return bool
   * @throws CouldNotDeleteException
   */
  public function delete(CategoryInterface $category);

  /**
   * Delete a category by ID.
   *
   * @param int $categoryId
   * @return bool
   * @throws CouldNotDeleteException
   * @throws NoSuchEntityException
   */
  public function deleteById($categoryId);

  /**
   * Retrieve child categories of a given category.
   *
   * @param int $categoryId
   * @return CategorySearchResultsInterface
   * @throws NoSuchEntityException
   */
  public function getChildren($categoryId);

  /**
   * Retrieve parent categories of a given category.
   *
   * @param int $categoryId
   * @return CategorySearchResultsInterface
   * @throws NoSuchEntityException
   */
  public function getParents($categoryId);

  /**
   * Retrieve news items associated with a category.
   *
   * @param int $categoryId
   * @return NewsSearchResultsInterface
   * @throws NoSuchEntityException
   */
  public function getNews($categoryId);

  /**
   * Add a parent category to a category.
   *
   * @param int $categoryId
   * @param int $parentId
   * @return bool
   * @throws NoSuchEntityException
   * @throws CouldNotSaveException
   */
  public function addParent($categoryId, $parentId);

  /**
   * Remove a parent category from a category.
   *
   * @param int $categoryId
   * @param int $parentId
   * @return bool
   * @throws NoSuchEntityException
   * @throws CouldNotSaveException
   */
  public function removeParent($categoryId, $parentId);

  /**
   * Create a new category.
   *
   * @param CategoryInterface $category
   * @return CategoryInterface
   * @throws CouldNotSaveException
   */
  // public function create(CategoryInterface $category): CategoryInterface;
  /**
   * Update an existing category.
   *
   * @param int $categoryId
   * @param CategoryInterface $category
   * @return CategoryInterface
   * @throws CouldNotSaveException
   * @throws NoSuchEntityException
   */
  public function update($categoryId, CategoryInterface $category): CategoryInterface;

  /**
   * Validate category data.
   *
   * @param CategoryInterface $category
   * @return bool
   * @throws \Magento\Framework\Exception\ValidatorException
   */
  public function validate(CategoryInterface $category);

  /**
   * Check if a category exists.
   *
   * @param int $categoryId
   * @return bool
   * @throws NoSuchEntityException
   */
  public function exists($categoryId);
}
