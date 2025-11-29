<?php

namespace News\Manger\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Base Search Results implementation
 */
class SearchResults extends AbstractSimpleObject implements SearchResultsInterface
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

  /**
   * @inheritDoc
   */
  public function getSearchCriteria()
  {
    return $this->_get('search_criteria');
  }

  /**
   * @inheritDoc
   */
  public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
  {
    return $this->setData('search_criteria', $searchCriteria);
  }

  /**
   * @inheritDoc
   */
  public function getTotalCount()
  {
    return $this->_get('total_count');
  }

  /**
   * @inheritDoc
   */
  public function setTotalCount($totalCount)
  {
    return $this->setData('total_count', $totalCount);
  }
}
