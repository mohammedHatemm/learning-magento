<?php


namespace News\Manger\Block\Adminhtml\Category\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use News\Manger\Model\CategoryFactory;
use Magento\Framework\Registry;

class Info extends Template
{
  protected $categoryFactory;
  protected $registry;

  public function __construct(
    Context $context,
    CategoryFactory $categoryFactory,
    Registry $registry,
    array $data = []
  ) {
    $this->categoryFactory = $categoryFactory;
    $this->registry = $registry;
    parent::__construct($context, $data);
  }

  public function getCategory()
  {
    return $this->registry->registry('current_category');
  }
}
