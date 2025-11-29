<?php

namespace News\Manger\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface NewsSearchResultsInterface extends SearchResultsInterface
{
  /**
   * Get news list.
   *
   * @return \News\Manger\Api\Data\NewsInterface[]
   */
  public function getItems();

  /**
   * Set news list.
   *
   * @param \News\Manger\Api\Data\NewsInterface[] $items
   * @return $this
   */
  public function setItems(array $items);
}
