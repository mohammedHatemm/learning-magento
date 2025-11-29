<?php

namespace News\Manger\Model\Data;

use News\Manger\Api\Data\CategorySearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Service Data Object with Category search results.
 */
class CategorySearchResults extends SearchResults implements CategorySearchResultsInterface
{
  /**
   * @inheritDoc
   */
  public function getItems()
  {
    return $this->_get('items') === null ? [] : $this->_get('items');
  }

  /**
   * @inheritDoc
   */
  public function setItems(array $items)
  {
    return $this->setData('items', $items);
  }
}
