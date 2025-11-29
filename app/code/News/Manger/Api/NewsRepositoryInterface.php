<?php

namespace News\Manger\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use News\Manger\Api\Data\NewsInterface;

/**
 * Interface NewsRepositoryInterface
 * @api
 */
interface NewsRepositoryInterface
{
  /**
   * Save news
   *
   * @param \News\Manger\Api\Data\NewsInterface $news
   * @return \News\Manger\Api\Data\NewsInterface
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  public function save(\News\Manger\Api\Data\NewsInterface $news);

  /**
   * Get news by ID
   *
   * @param int $newsId
   * @return \News\Manger\Api\Data\NewsInterface
   * @throws NoSuchEntityException
   */
  public function getById($newsId);

  /**
   * Get news list
   *
   * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
   * @return \News\Manger\Api\Data\NewsSearchResultsInterface
   */
  public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null);

  /**
   * Delete news
   *
   * @param \News\Manger\Api\Data\NewsInterface $news
   * @return bool
   * @throws CouldNotDeleteException
   */
  public function delete(\News\Manger\Api\Data\NewsInterface $news);

  /**
   * Delete news by ID
   *
   * @param int $newsId
   * @return bool
   * @throws CouldNotDeleteException
   * @throws NoSuchEntityException
   */
  public function deleteById($newsId);

  /**
   * Get categories for news
   *
   * @param int $newsId
   * @return \News\Manger\Api\Data\CategorySearchResultsInterface
   */
  public function getCategories($newsId);

  /**
   * Add category to news
   *
   * @param int $newsId
   * @param int $categoryId
   * @return bool
   */
  public function addCategory($newsId, $categoryId);

  /**
   * Remove category from news
   *
   * @param int $newsId
   * @param int $categoryId
   * @return bool
   */
  public function removeCategory($newsId, $categoryId);

  /**
   * Set categories for news (replaces existing categories)
   *
   * @param int $newsId
   * @param int[] $categoryIds
   * @return bool
   */
  public function setCategories($newsId, array $categoryIds);

  /**
   * Validate news data
   *
   * @param \News\Manger\Api\Data\NewsInterface $news
   * @return bool
   * @throws \Magento\Framework\Exception\LocalizedException
   */


  public function validate(\News\Manger\Api\Data\NewsInterface $news);

  /**
   * Check if a news exists.
   *
   * @param int $newId
   * @return bool
   * @throws NoSuchEntityException
   */

  public function exists($newsId): bool;
}
