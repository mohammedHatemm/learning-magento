<?php

namespace News\Manger\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class NewsActions extends Column
{
  const URL_PATH_EDIT = 'news/news/edit';
  const URL_PATH_DELETE = 'news/news/delete'; // تأكد من وجود هذا الكونترولر

  /**
   * @var UrlInterface
   */
  protected $urlBuilder;

  /**
   * NewsActions constructor.
   */
  public function __construct(
    ContextInterface $context,
    UiComponentFactory $uiComponentFactory,
    UrlInterface $urlBuilder,
    array $components = [],
    array $data = []
  ) {
    $this->urlBuilder = $urlBuilder;
    parent::__construct($context, $uiComponentFactory, $components, $data);
  }

  /**
   * إعداد الأعمدة الخاصة بالأكشن في الـ Grid
   */
  public function prepareDataSource(array $dataSource)
  {
    if (isset($dataSource['data']['items'])) {
      foreach ($dataSource['data']['items'] as &$item) {
        if (isset($item['news_id'])) {
          $item[$this->getData('name')] = [
            'edit' => [
              'href' => $this->urlBuilder->getUrl(
                self::URL_PATH_EDIT,
                ['news_id' => $item['news_id']]
              ),
              'label' => __('Edit')
            ],
            'delete' => [
              'href' => $this->urlBuilder->getUrl(
                self::URL_PATH_DELETE,
                ['news_id' => $item['news_id']]
              ),
              'label' => __('Delete'),
              'confirm' => [
                'title' => __('Delete News'),
                'message' => __('Are you sure you want to delete this news item?')
              ]
            ]
          ];
        }
      }
    }

    return $dataSource;
  }
}
