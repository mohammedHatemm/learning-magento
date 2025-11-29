<?php

namespace News\Manger\Plugin\Block\User\News;

use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session as CustomerSession;
use News\Manger\Block\User\News\Index as NewsIndex;

class IndexPlugin
{
  /**
   * @var RequestInterface
   */
  protected $request;

  /**
   * @var CustomerSession
   */
  protected $customerSession;

  /**
   * @param RequestInterface $request
   * @param CustomerSession $customerSession
   */
  public function __construct(
    RequestInterface $request,
    CustomerSession $customerSession
  ) {
    $this->request = $request;
    $this->customerSession = $customerSession;
  }

  /**
   * Add pagination to news collection only for logged-in users
   *
   * @param NewsIndex $subject
   * @param \Closure $proceed
   * @return \News\Manger\Model\ResourceModel\News\Collection
   */
  public function aroundGetNewsCollection(
    NewsIndex $subject,
    \Closure $proceed
  ) {
    $collection = $proceed();

    // Only apply pagination for logged-in users
    if ($this->customerSession->isLoggedIn()) {
      $page = (int)$this->request->getParam('p', 1);
      $pageSize = 5; // Number of news items per page

      $collection->setPageSize($pageSize);
      $collection->setCurPage($page);
    }

    return $collection;
  }
}
