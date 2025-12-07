<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Webhook;

use Elsherif\Bosta\Model\ResourceModel\Delivery\CollectionFactory as DeliveryCollectionFactory;
use Elsherif\Bosta\Model\TrackingEventFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class Update extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private DeliveryCollectionFactory $deliveryCollectionFactory;
    private TrackingEventFactory $trackingEventFactory;
    private OrderRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        DeliveryCollectionFactory $deliveryCollectionFactory,
        TrackingEventFactory $trackingEventFactory,
        OrderRepository $orderRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
        $this->trackingEventFactory = $trackingEventFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Get webhook data
            $content = $this->getRequest()->getContent();
            $data = json_decode($content, true);

            if (!$data) {
                return $result->setData(['error' => 'Invalid JSON'])->setHttpResponseCode(400);
            }

            $this->logger->info('Bosta webhook received', ['data' => $data]);

            $trackingNumber = $data['trackingNumber'] ?? null;
            $newStatus = $data['state'] ?? null;

            if (!$trackingNumber || !$newStatus) {
                return $result->setData(['error' => 'Missing required fields'])->setHttpResponseCode(400);
            }

            // Find delivery by tracking number
            $deliveryCollection = $this->deliveryCollectionFactory->create();
            $delivery = $deliveryCollection->addFieldToFilter('tracking_number', $trackingNumber)->getFirstItem();

            if (!$delivery->getId()) {
                $this->logger->warning('Delivery not found for tracking: ' . $trackingNumber);
                return $result->setData(['error' => 'Delivery not found'])->setHttpResponseCode(404);
            }

            // Update delivery status
            $delivery->setStatus($newStatus);
            $delivery->save();

            // Save tracking event
            $trackingEvent = $this->trackingEventFactory->create();
            $trackingEvent->setDeliveryId($delivery->getId());
            $trackingEvent->setStatus($newStatus);
            $trackingEvent->setDescription($data['reason'] ?? $data['state']);
            $trackingEvent->setLocation($data['hub'] ?? '');
            $trackingEvent->setEventTimestamp($data['timestamp'] ?? date('Y-m-d H:i:s'));
            $trackingEvent->save();

            // Update Magento order
            $this->updateOrderStatus($delivery, $newStatus);

            $this->logger->info('Webhook processed successfully', [
                'tracking' => $trackingNumber,
                'status' => $newStatus
            ]);

            return $result->setData(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing error: ' . $e->getMessage());
            return $result->setData(['error' => $e->getMessage()])->setHttpResponseCode(500);
        }
    }

    /**
     * Update Magento order status based on Bosta delivery state
     *
     * Maps Bosta delivery states to custom Magento order statuses
     *
     * @param $delivery
     * @param string $bostaStatus
     * @return void
     */
    private function updateOrderStatus($delivery, string $bostaStatus): void
    {
        try {
            $order = $this->orderRepository->get($delivery->getOrderId());

            // Map Bosta state to Magento status and state
            $statusMapping = $this->getStatusMapping($bostaStatus);

            if ($statusMapping) {
                $order->setState($statusMapping['state']);
                $order->addCommentToStatusHistory(
                    __('Bosta delivery status updated: %1', $statusMapping['label']),
                    $statusMapping['status']
                );
            } else {
                // Unknown status, just add comment
                $order->addCommentToStatusHistory(
                    __('Bosta delivery status: %1', $bostaStatus)
                );
            }

            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Get Magento status mapping for Bosta state
     *
     * @param string $bostaStatus
     * @return array|null
     */
    private function getStatusMapping(string $bostaStatus): ?array
    {
        $mappings = [
            'PENDING' => [
                'status' => 'bosta_pending',
                'label' => 'Pending',
                'state' => \Magento\Sales\Model\Order::STATE_PROCESSING
            ],
            'PICKED_UP' => [
                'status' => 'bosta_picked_up',
                'label' => 'Picked Up',
                'state' => \Magento\Sales\Model\Order::STATE_PROCESSING
            ],
            'IN_TRANSIT' => [
                'status' => 'bosta_in_transit',
                'label' => 'In Transit',
                'state' => \Magento\Sales\Model\Order::STATE_PROCESSING
            ],
            'OUT_FOR_DELIVERY' => [
                'status' => 'bosta_out_for_delivery',
                'label' => 'Out for Delivery',
                'state' => \Magento\Sales\Model\Order::STATE_PROCESSING
            ],
            'DELIVERED' => [
                'status' => 'bosta_delivered',
                'label' => 'Delivered',
                'state' => \Magento\Sales\Model\Order::STATE_COMPLETE
            ],
            'CANCELLED' => [
                'status' => 'bosta_cancelled',
                'label' => 'Cancelled',
                'state' => \Magento\Sales\Model\Order::STATE_CANCELED
            ],
            'TERMINATED' => [
                'status' => 'bosta_terminated',
                'label' => 'Terminated',
                'state' => \Magento\Sales\Model\Order::STATE_CANCELED
            ]
        ];

        return $mappings[$bostaStatus] ?? null;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
