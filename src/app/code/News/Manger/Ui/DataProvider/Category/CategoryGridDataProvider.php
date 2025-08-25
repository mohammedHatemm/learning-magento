<?php

namespace News\Manger\Ui\DataProvider\Category;

use Magento\Ui\DataProvider\AbstractDataProvider;
use News\Manger\Model\ResourceModel\Category\CollectionFactory;

class CategoryGridDataProvider extends AbstractDataProvider
{
  /**
   * @var array
   */
  protected $loadedData;

  /**
   * @param string $name
   * @param string $primaryFieldName
   * @param string $requestFieldName
   * @param CollectionFactory $collectionFactory
   * @param array $meta
   * @param array $data
   */
  public function __construct(
    $name,
    $primaryFieldName,
    $requestFieldName,
    CollectionFactory $collectionFactory,
    array $meta = [],
    array $data = []
  ) {
    $this->collection = $collectionFactory->create();
    parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
  }
}
