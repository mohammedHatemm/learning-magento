<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Observer;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Observer to create Bosta delivery when order is placed
 * To enable this observer, add events.xml configuration
 */
class CreateBostaDelivery implements ObserverInterface
{
    private BostaHelper $bostaHelper;
    private LoggerInterface $logger;

    public function __construct(
        BostaHelper $bostaHelper,
        LoggerInterface $logger
    ) {
        $this->bostaHelper = $bostaHelper;
        $this->logger = $logger;
    }

    /**
     * Create Bosta delivery when order is placed
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();

            // Check if order uses Bosta shipping
            $shippingMethod = $order->getShippingMethod();
            if (strpos($shippingMethod, 'customshipping') === false) {
                return; // Not a Bosta shipment
            }

            // Check if module is enabled
            if (!$this->bostaHelper->isEnabled($order->getStoreId())) {
                return;
            }

            // Get shipping address
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                $this->logger->warning('Bosta: No shipping address found for order #' . $order->getIncrementId());
                return;
            }

            // Prepare delivery data
            $deliveryData = $this->prepareDeliveryData($order, $shippingAddress);

            // Create delivery via Bosta API
            $result = $this->bostaHelper->createDelivery($deliveryData, $order->getStoreId());

            if ($result['success']) {
                $trackingNumber = $result['data']['trackingNumber'] ?? null;
                $deliveryId = $result['data']['_id'] ?? null;

                // Save tracking information to order
                if ($trackingNumber) {
                    $this->addTrackingToOrder($order, $trackingNumber);
                    $this->logger->info('Bosta delivery created successfully', [
                        'order_id' => $order->getIncrementId(),
                        'tracking_number' => $trackingNumber,
                        'delivery_id' => $deliveryId
                    ]);
                }
            } else {
                $this->logger->error('Failed to create Bosta delivery', [
                    'order_id' => $order->getIncrementId(),
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Bosta delivery creation error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * Prepare delivery data for Bosta API
     *
     * @param Order $order
     * @param \Magento\Sales\Model\Order\Address $shippingAddress
     * @return array
     */
    private function prepareDeliveryData(Order $order, $shippingAddress): array
    {
        $street = $shippingAddress->getStreet();
        $streetAddress = is_array($street) ? implode(', ', $street) : $street;

        // Determine delivery type and COD
        $paymentMethod = $order->getPayment()->getMethod();
        $isCOD = (strpos($paymentMethod, 'cashondelivery') !== false);
        $codAmount = $isCOD ? $order->getGrandTotal() : 0;

        return [
            'type' => $isCOD ? 20 : 10, // 10 = Send Package, 20 = Cash Collection (COD)
            'cod' => $codAmount,
            'dropOffAddress' => [
                'city' => $shippingAddress->getCity(),
                'zone' => $shippingAddress->getRegion(),
                'district' => $streetAddress,
                'buildingNumber' => '',
                'floor' => '',
                'apartment' => '',
                'firstLine' => $streetAddress,
                'secondLine' => ''
            ],
            'receiver' => [
                'firstName' => $shippingAddress->getFirstname(),
                'lastName' => $shippingAddress->getLastname(),
                'phone' => $shippingAddress->getTelephone(),
                'email' => $order->getCustomerEmail()
            ],
            'businessReference' => $order->getIncrementId(),
            'notes' => 'Order #' . $order->getIncrementId(),
            'allowToOpenPackage' => false,
            'pickupLocationId' => $this->bostaHelper->getPickupLocationId($order->getStoreId())
        ];
    }

    /**
     * Add tracking information to order
     *
     * @param Order $order
     * @param string $trackingNumber
     * @return void
     */
    private function addTrackingToOrder(Order $order, string $trackingNumber): void
    {
        try {
            $shipment = $order->getShipmentsCollection()->getFirstItem();

            if ($shipment && $shipment->getId()) {
                // Add tracking to existing shipment
                $track = $shipment->getTracksCollection()->getFirstItem();
                if (!$track->getId()) {
                    $shipment->addTrack(
                        $this->createTrack($trackingNumber)
                    )->save();
                }
            } else {
                // Save tracking in order comments for now
                $order->addCommentToStatusHistory(
                    'Bosta Tracking Number: ' . $trackingNumber
                )->save();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to add tracking to order: ' . $e->getMessage());
        }
    }

    /**
     * Create tracking object
     *
     * @param string $trackingNumber
     * @return \Magento\Sales\Model\Order\Shipment\Track
     */
    private function createTrack(string $trackingNumber)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $track = $objectManager->create(\Magento\Sales\Model\Order\Shipment\Track::class);

        $track->setCarrierCode('customshipping')
            ->setTitle('Bosta Shipping')
            ->setTrackNumber($trackingNumber);

        return $track;
    }
}
