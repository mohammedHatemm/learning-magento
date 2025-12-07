<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Observer;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\DeliveryFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Enhanced observer that saves delivery to database
 */
class SaveDeliveryToDatabase implements ObserverInterface
{
    private BostaHelper $bostaHelper;
    private DeliveryFactory $deliveryFactory;
    private LoggerInterface $logger;

    public function __construct(
        BostaHelper $bostaHelper,
        DeliveryFactory $deliveryFactory,
        LoggerInterface $logger
    ) {
        $this->bostaHelper = $bostaHelper;
        $this->deliveryFactory = $deliveryFactory;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();

            // Check if order uses Bosta shipping
            if (strpos($order->getShippingMethod(), 'customshipping') === false) {
                return;
            }

            if (!$this->bostaHelper->isEnabled($order->getStoreId())) {
                return;
            }

            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                return;
            }

            // Prepare delivery data
            $deliveryData = $this->prepareDeliveryData($order, $shippingAddress);

            // Create delivery via API
            $result = $this->bostaHelper->createDelivery($deliveryData, $order->getStoreId());

            if ($result['success'] && isset($result['data'])) {
                // Save to database
                $delivery = $this->deliveryFactory->create();
                $delivery->setOrderId($order->getId());
                $delivery->setTrackingNumber($result['data']['trackingNumber'] ?? '');
                $delivery->setBostaDeliveryId($result['data']['_id'] ?? '');
                $delivery->setDeliveryType($deliveryData['type']);
                $delivery->setStatus($result['data']['state'] ?? 'PENDING');
                $delivery->setCodAmount($deliveryData['cod']);
                $delivery->setShippingCost($order->getShippingAmount());
                $delivery->setDeliveryData($result['data']);

                // Extract and save AWB URL if present
                if (isset($result['data']['airWayBillUrl'])) {
                    $delivery->setAwbUrl($result['data']['airWayBillUrl']);
                }

                $delivery->save();

                // Add comment to order
                $order->addCommentToStatusHistory(
                    sprintf(
                        'Bosta delivery created successfully. Tracking: %s',
                        $result['data']['trackingNumber'] ?? 'N/A'
                    )
                );
                $order->save();

                $this->logger->info('Bosta delivery saved to database', [
                    'order_id' => $order->getIncrementId(),
                    'delivery_id' => $delivery->getId(),
                    'tracking' => $delivery->getTrackingNumber()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to save Bosta delivery: ' . $e->getMessage());
        }
    }

    private function prepareDeliveryData(Order $order, $shippingAddress): array
    {
        $street = $shippingAddress->getStreet();
        $streetAddress = is_array($street) ? implode(', ', $street) : $street;

        $paymentMethod = $order->getPayment()->getMethod();
        $isCOD = (strpos($paymentMethod, 'cashondelivery') !== false);

        // Sanitize phone number: remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $shippingAddress->getTelephone());

        // Validate city
        $city = $shippingAddress->getCity();
        if (empty($city) || strtolower($city) === 'select city') {
            throw new \Exception('Invalid city specified for Bosta delivery.');
        }

        return [
            'type' => $isCOD ? 20 : 10,
            'cod' => $isCOD ? $order->getGrandTotal() : 0,
            'dropOffAddress' => [
                'city' => $city,
                'zone' => $shippingAddress->getRegion(),
                'district' => $streetAddress,
                'firstLine' => $streetAddress
            ],
            'receiver' => [
                'firstName' => $shippingAddress->getFirstname(),
                'lastName' => $shippingAddress->getLastname(),
                'phone' => $phone,
                'email' => $order->getCustomerEmail()
            ],
            'businessReference' => $order->getIncrementId(),
            'notes' => 'Order #' . $order->getIncrementId()
        ];
    }
}
