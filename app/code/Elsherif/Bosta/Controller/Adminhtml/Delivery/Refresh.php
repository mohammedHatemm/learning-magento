<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Adminhtml\Delivery;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\DeliveryFactory;
use Elsherif\Bosta\Model\ResourceModel\Delivery as DeliveryResource;
use Elsherif\Bosta\Model\TrackingEventFactory;
use Elsherif\Bosta\Model\ResourceModel\TrackingEvent as TrackingEventResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Refresh extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Elsherif_Bosta::delivery';

    private JsonFactory $resultJsonFactory;
    private BostaHelper $bostaHelper;
    private DeliveryFactory $deliveryFactory;
    private DeliveryResource $deliveryResource;
    private TrackingEventFactory $trackingEventFactory;
    private TrackingEventResource $trackingEventResource;
    private OrderRepositoryInterface $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        BostaHelper $bostaHelper,
        DeliveryFactory $deliveryFactory,
        DeliveryResource $deliveryResource,
        TrackingEventFactory $trackingEventFactory,
        TrackingEventResource $trackingEventResource,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->bostaHelper = $bostaHelper;
        $this->deliveryFactory = $deliveryFactory;
        $this->deliveryResource = $deliveryResource;
        $this->trackingEventFactory = $trackingEventFactory;
        $this->trackingEventResource = $trackingEventResource;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid request method.')
            ]);
        }

        $deliveryId = (int) $this->getRequest()->getParam('delivery_id');

        if (!$deliveryId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Delivery ID is required.')
            ]);
        }

        try {
            // Load delivery
            $delivery = $this->deliveryFactory->create();
            $this->deliveryResource->load($delivery, $deliveryId);

            if (!$delivery->getId()) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Delivery not found.')
                ]);
            }

            $trackingNumber = $delivery->getTrackingNumber();
            if (!$trackingNumber) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Tracking number not found.')
                ]);
            }

            // Fetch tracking info from Bosta API
            $apiResponse = $this->bostaHelper->trackDelivery($trackingNumber);

            if (!$apiResponse['success'] || !isset($apiResponse['data'])) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Failed to fetch tracking information from Bosta.')
                ]);
            }

            $trackingData = $apiResponse['data'];

            // Update delivery status
            $oldStatus = $delivery->getStatus();
            $newStatus = $trackingData['state']['value'] ?? $oldStatus;

            $delivery->setStatus($newStatus);
            $delivery->setDeliveryData(json_encode($trackingData));

            if (isset($trackingData['airWayBillUrl'])) {
                $delivery->setAwbUrl($trackingData['airWayBillUrl']);
            }

            $this->deliveryResource->save($delivery);

            // Create tracking event if status changed
            if ($oldStatus !== $newStatus) {
                $trackingEvent = $this->trackingEventFactory->create();
                $trackingEvent->setData([
                    'delivery_id' => $delivery->getId(),
                    'status' => $newStatus,
                    'description' => $trackingData['state']['label'] ?? 'Status updated',
                    'location' => '',
                    'event_timestamp' => date('Y-m-d H:i:s')
                ]);
                $this->trackingEventResource->save($trackingEvent);

                // Update order status comment
                $order = $this->orderRepository->get($delivery->getOrderId());
                $order->addCommentToStatusHistory(
                    __('Bosta tracking updated: %1', $newStatus)
                );
                $this->orderRepository->save($order);
            }

            $statusChanged = $oldStatus !== $newStatus;
            $message = $statusChanged
                ? __('Tracking updated! Status changed from %1 to %2', $oldStatus, $newStatus)
                : __('Tracking refreshed. Current status: %1', $newStatus);

            return $resultJson->setData([
                'success' => true,
                'message' => $message,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'status_changed' => $statusChanged
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error refreshing Bosta tracking: ' . $e->getMessage(), [
                'exception' => $e,
                'delivery_id' => $deliveryId
            ]);

            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
