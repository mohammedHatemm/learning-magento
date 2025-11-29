<?php

namespace News\Manger\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class CategoryActions extends Column
{
    const URL_PATH_EDIT = 'news/category/edit';
    const URL_PATH_DELETE = 'news/category/delete';
    const URL_PATH_VIEW = 'news/category/view';

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var string */
    private $editUrl;

    public function __construct(
        UrlInterface $urlBuilder,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = [],
        $editUrl = self::URL_PATH_EDIT
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['category_id'])) {
                    $name = $this->getData('name');

                    // Edit action
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            $this->editUrl,
                            ['category_id' => $item['category_id']]
                        ),
                        'label' => __('Edit'),
                        'hidden' => false,
                    ];

                    // Delete action
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_DELETE,
                            ['id' => $item['category_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', $item['category_name']),
                            'message' => __('Are you sure you want to delete the category "%1"?', $item['category_name']),
                        ],
                        'post' => true,
                    ];

                    // View action
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_VIEW,
                            ['id' => $item['category_id']]
                        ),
                        'label' => __('View'),
                        'hidden' => false,
                    ];
                }
            }
        }

        return $dataSource;
    }
}
