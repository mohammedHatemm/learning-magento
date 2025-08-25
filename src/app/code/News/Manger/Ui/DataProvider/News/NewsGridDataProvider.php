<?php

namespace News\Manger\Ui\DataProvider\News;

use Magento\Ui\DataProvider\AbstractDataProvider;
use News\Manger\Model\ResourceModel\News\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class NewsGridDataProvider extends AbstractDataProvider
{
  /**
   * @var \Magento\Framework\App\Request\DataPersistorInterface
   */
  protected $dataPersistor;

  /**
   * @var array
   */
  protected $loadedData = [];

  /**
   * @param string $name
   * @param string $primaryFieldName
   * @param string $requestFieldName
   * @param CollectionFactory $collectionFactory
   * @param DataPersistorInterface $dataPersistor
   * @param array $meta
   * @param array $data
   */
  public function __construct(
    $name,
    $primaryFieldName,
    $requestFieldName,
    CollectionFactory $collectionFactory,
    DataPersistorInterface $dataPersistor,
    array $meta = [],
    array $data = []
  ) {
    $this->collection = $collectionFactory->create();
    $this->dataPersistor = $dataPersistor;
    parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
  }
}
