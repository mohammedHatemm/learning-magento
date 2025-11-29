<?php

namespace News\Manger\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class View extends Template
{
  protected $registry;

  public function __construct(
    Context $context,
    Registry $registry,
    array $data = []
  ) {
    $this->registry = $registry;
    parent::__construct($context, $data);
  }

  public function getCurrentCategory()
  {
    return $this->registry->registry('current_category');
  }

  public function hasCategory()
  {
    $category = $this->getCurrentCategory();
    return $category && $category->getId();
  }

  public function getBreadcrumbPaths()
  {
    $category = $this->getCurrentCategory();
    if (!$category || !$category->getId()) {
      return [];
    }
    return $category->getBreadcrumbPaths();
  }
}
