<?php

namespace News\Manger\Controller\Adminhtml\News;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;
use News\Manger\Model\NewsFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Save News Controller
 */
class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'News_Manger::news_save';
    const PAGE_TITLE = 'Save News';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var NewsFactory
     */
    protected $newsFactory;

    /**
     * @var DateFilter
     */
    protected $dateFilter;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param NewsFactory $newsFactory
     * @param DateFilter $dateFilter
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        NewsFactory $newsFactory,
        DateFilter $dateFilter,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->newsFactory = $newsFactory;
        $this->dateFilter = $dateFilter;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id = $this->getRequest()->getParam('news_id');

            // Initialize model
            $model = $this->newsFactory->create();
            if ($id) {
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This news no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            // Validate required fields
            if (empty($data['news_title'])) {
                $this->messageManager->addErrorMessage(__('Please provide the news title.'));
                return $this->redirectWithData($resultRedirect, $data, $id);
            }

            // Prepare data
            if (isset($data['created_at']) && $data['created_at']) {
                try {
                    // Filter date to ensure correct format
                    $data['created_at'] = $this->dateFilter->filter($data['created_at']);
                } catch (\Exception $e) {
                    $data['created_at'] = null;
                }
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            // Extract category_ids and remove from model data to be saved separately
            $categoryIds = isset($data['category_ids']) && is_array($data['category_ids']) ? $data['category_ids'] : [];
            unset($data['category_ids']);

            $model->setData($data);

            try {
                // Save the main news data
                $model->save();

                // Save category associations in the junction table
                $this->saveCategoryAssociations($model->getId(), $categoryIds);

                $this->messageManager->addSuccessMessage(__('The news has been saved.'));
                $this->dataPersistor->clear('news_news');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['news_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error('Error saving news: ' . $e->getMessage());
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the news.'));
            }

            // Restore category_ids for data persistence on failure
            $data['category_ids'] = $categoryIds;
            return $this->redirectWithData($resultRedirect, $data, $id);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Saves the association between news and its categories in a junction table.
     *
     * @param int $newsId The ID of the news item being saved.
     * @param array $categoryIds An array of associated category IDs.
     * @throws \Exception
     */
    private function saveCategoryAssociations($newsId, $categoryIds)
    {
        $this->logger->info('Saving categories for news ' . $newsId . ': ' . json_encode($categoryIds));
        $connection = $this->resourceConnection->getConnection();
        // Assumes a junction table named 'news_news_category'
        $table = $connection->getTableName('news_news_category');

        // Delete existing associations
        try {
            $connection->delete($table, ['news_id = ?' => $newsId]);
            $this->logger->info('Deleted existing category associations for news ' . $newsId);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting existing category associations: ' . $e->getMessage());
            throw $e;
        }

        // Insert new associations
        if (!empty($categoryIds)) {
            $dataToInsert = [];
            foreach ($categoryIds as $categoryId) {
                if (!empty($categoryId)) {
                    $dataToInsert[] = [
                        'news_id'     => (int)$newsId,
                        'category_id' => (int)$categoryId
                    ];
                }
            }
            if (!empty($dataToInsert)) {
                try {
                    $connection->insertMultiple($table, $dataToInsert);
                    $this->logger->info('Inserted new category associations for news ' . $newsId);
                } catch (\Exception $e) {
                    $this->logger->error('Error inserting category associations: ' . $e->getMessage());
                    throw $e;
                }
            }
        }
    }

    /**
     * Redirect back to the edit/new form with data.
     *
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     * @param array $data
     * @param int|null $id
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    private function redirectWithData($resultRedirect, $data, $id)
    {
        $this->dataPersistor->set('news_news', $data);
        if ($id) {
            return $resultRedirect->setPath('*/*/edit', ['news_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/new');
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
