<?php

namespace News\Manger\Block\Adminhtml\Category\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class Hierarchy extends Template
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

  // تغيير اسم الـ method لتتطابق مع الموجود في الـ model
  public function getBreadcrumbPaths()
  {
    $category = $this->registry->registry('current_category');
    if (!$category || !$category->getId()) {
      return [];
    }
    return $category->getBreadcrumbPaths();
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
}
