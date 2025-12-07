<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Adminhtml\Delivery;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\DeliveryFactory;
use Elsherif\Bosta\Model\ResourceModel\Delivery as DeliveryResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Create extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Elsherif_Bosta::delivery';

    private JsonFactory $resultJsonFactory;
    private OrderRepositoryInterface $orderRepository;
    private BostaHelper $bostaHelper;
    private DeliveryFactory $deliveryFactory;
    private DeliveryResource $deliveryResource;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        BostaHelper $bostaHelper,
        DeliveryFactory $deliveryFactory,
        DeliveryResource $deliveryResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->bostaHelper = $bostaHelper;
        $this->deliveryFactory = $deliveryFactory;
        $this->deliveryResource = $deliveryResource;
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

        $orderId = (int) $this->getRequest()->getParam('order_id');

        if (!$orderId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Order ID is required.')
            ]);
        }

        try {
            // Load order
            $order = $this->orderRepository->get($orderId);

            if (!$order->getId()) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Order not found.')
                ]);
            }

            // Check if order uses Bosta shipping
            if (strpos($order->getShippingMethod(), 'customshipping') === false) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('This order does not use Bosta shipping method.')
                ]);
            }

            // Check if delivery already exists
            $existingDelivery = $this->deliveryFactory->create();
            $this->deliveryResource->load($existingDelivery, $orderId, 'order_id');

            if ($existingDelivery->getId()) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('A Bosta delivery already exists for this order. Tracking Number: %1',
                        $existingDelivery->getTrackingNumber())
                ]);
            }

            // Create delivery via Bosta API
            $deliveryData = $this->bostaHelper->createDeliveryForOrder($order);

            if (!$deliveryData || !isset($deliveryData['trackingNumber'])) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Failed to create delivery in Bosta system. Please check the logs.')
                ]);
            }

            // Save delivery to database
            $delivery = $this->deliveryFactory->create();
            $delivery->setData([
                'order_id' => $order->getId(),
                'tracking_number' => $deliveryData['trackingNumber'],
                'bosta_delivery_id' => $deliveryData['_id'] ?? null,
                'delivery_type' => $deliveryData['type'] ?? 10,
                'status' => $deliveryData['state']['value'] ?? 'PENDING',
                'cod_amount' => $deliveryData['cod'] ?? 0,
                'shipping_cost' => $order->getShippingAmount(),
                'delivery_data' => json_encode($deliveryData),
                'awb_url' => $deliveryData['airWayBillUrl'] ?? null
            ]);

            $this->deliveryResource->save($delivery);

            // Update order status to Bosta - Pending and add comment
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->addCommentToStatusHistory(
                __('Order sent to Bosta for shipping. Tracking Number: %1', $deliveryData['trackingNumber']),
                'bosta_pending'  // Set custom Bosta status
            );
            $this->orderRepository->save($order);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Delivery created successfully! Tracking Number: %1', $deliveryData['trackingNumber']),
                'tracking_number' => $deliveryData['trackingNumber'],
                'awb_url' => $deliveryData['airWayBillUrl'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating Bosta delivery: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id' => $orderId
            ]);

            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
