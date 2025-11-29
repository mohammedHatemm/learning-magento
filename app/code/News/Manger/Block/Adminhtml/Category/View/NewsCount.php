<?php

namespace News\Manger\Block\Adminhtml\Category\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use News\Manger\Model\ResourceModel\News\CollectionFactory as NewsCollectionFactory;
use Magento\Framework\Registry;

class NewsCount extends Template
{
  protected $newsCollectionFactory;
  protected $registry;

  public function __construct(
    Context $context,
    NewsCollectionFactory $newsCollectionFactory,
    Registry $registry,
    array $data = []
  ) {
    $this->newsCollectionFactory = $newsCollectionFactory;
    $this->registry = $registry;
    parent::__construct($context, $data);
  }

  public function getNewsCount()
  {
    $category = $this->registry->registry('current_category');
    if (!$category || !$category->getId()) {
      return 0;
    }

    $collection = $this->newsCollectionFactory->create();
    $collection->getSelect()->join(
      ['news_category' => $collection->getTable('news_news_category')],
      'main_table.news_id = news_category.news_id',
      []
    )->where('news_category.category_id = ?', $category->getId());

    return $collection->getSize();
  }
}
