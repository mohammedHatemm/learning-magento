<?php

namespace News\Manger\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface CategorySearchResultsInterface extends SearchResultsInterface
{
  /**
   * Get categories list.
   *
   * @return \News\Manger\Api\Data\CategoryInterface[]
   */
  public function getItems();

  /**
   * Set categories list.
   *
   * @param \News\Manger\Api\Data\CategoryInterface[] $items
   * @return $this
   */
  public function setItems(array $items);
}
