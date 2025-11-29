<?php

namespace News\Manger\Plugin\Controller\User\News;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Customer\Model\Session as CustomerSession;

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
   * Add pagination data
   *
   * @param \News\Manger\Controller\User\News\Index $subject
   * @param \Magento\Framework\View\Result\Page $result
   * @return \Magento\Framework\View\Result\Page
   */
  public function afterExecute(
    \News\Manger\Controller\User\News\Index $subject,
    $result
  ) {
    if ($result instanceof Page) {
      $currentPage = (int)$this->request->getParam('p', 1);
      $result->getLayout()->getBlock('news.list')->setData('current_page', $currentPage);
    }

    return $result;
  }
}
