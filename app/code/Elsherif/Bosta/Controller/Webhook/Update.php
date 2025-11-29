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

    private function updateOrderStatus($delivery, string $status): void
    {
        try {
            $order = $this->orderRepository->get($delivery->getOrderId());

            $message = sprintf('Bosta status update: %s', $status);

            switch ($status) {
                case 'DELIVERED':
                    $order->addCommentToStatusHistory($message);
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                    $order->setStatus('complete');
                    break;

                case 'CANCELLED':
                case 'TERMINATED':
                    $order->addCommentToStatusHistory($message);
                    break;

                default:
                    $order->addCommentToStatusHistory($message);
            }

            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update order: ' . $e->getMessage());
        }
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
