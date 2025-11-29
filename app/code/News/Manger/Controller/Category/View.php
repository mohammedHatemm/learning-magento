<?php

namespace News\Manger\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use News\Manger\Model\CategoryRepository;
use News\Manger\Model\ResourceModel\News\CollectionFactory;

class View extends Action
{
  protected $resultPageFactory;
  protected $categoryRepository;
  protected $newsCollection;

  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    CategoryRepository $categoryRepository,
    CollectionFactory $newsCollection
  ) {
    $this->resultPageFactory = $resultPageFactory;
    $this->categoryRepository = $categoryRepository;
    $this->newsCollection = $newsCollection;
    parent::__construct($context);
  }

  public function execute()
  {
    $categoryId = $this->getRequest()->getParam('id');
    $resultPage = $this->resultPageFactory->create();

    try {
      $category = $this->categoryRepository->getById($categoryId);
      $resultPage->getConfig()->getTitle()->set(__('News in category: %1', $category->getCategoryName()));

      return $resultPage;
    } catch (\Exception $e) {
      $resultPage->getConfig()->getTitle()->set(__('Category Not Found'));
      return $resultPage;
    }
  }
}
