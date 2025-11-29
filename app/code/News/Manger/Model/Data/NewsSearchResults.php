<?php

namespace News\Manger\Model\Data;

use Magento\Framework\Api\SearchResults;
use News\Manger\Api\Data\NewsSearchResultsInterface;

class NewsSearchResults extends SearchResults implements NewsSearchResultsInterface
{
  // This class inherits all necessary methods from SearchResults
  // The interface methods are already implemented in the parent class
}
