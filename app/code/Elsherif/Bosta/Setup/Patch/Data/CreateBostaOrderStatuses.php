<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

/**
 * Create custom order statuses for Bosta delivery tracking
 */
class CreateBostaOrderStatuses implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private StatusFactory $statusFactory;
    private StatusResourceFactory $statusResourceFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Define Bosta order statuses
        $statuses = [
            [
                'status' => 'bosta_pending',
                'label' => 'Bosta - Pending',
                'state' => Order::STATE_PROCESSING,
                'visible_on_front' => true,
                'description' => 'Order has been sent to Bosta and is pending pickup'
            ],
            [
                'status' => 'bosta_picked_up',
                'label' => 'Bosta - Picked Up',
                'state' => Order::STATE_PROCESSING,
                'visible_on_front' => true,
                'description' => 'Package has been picked up by Bosta'
            ],
            [
                'status' => 'bosta_in_transit',
                'label' => 'Bosta - In Transit',
                'state' => Order::STATE_PROCESSING,
                'visible_on_front' => true,
                'description' => 'Package is in transit to destination'
            ],
            [
                'status' => 'bosta_out_for_delivery',
                'label' => 'Bosta - Out for Delivery',
                'state' => Order::STATE_PROCESSING,
                'visible_on_front' => true,
                'description' => 'Package is out for delivery with courier'
            ],
            [
                'status' => 'bosta_delivered',
                'label' => 'Bosta - Delivered',
                'state' => Order::STATE_COMPLETE,
                'visible_on_front' => true,
                'description' => 'Package has been successfully delivered'
            ],
            [
                'status' => 'bosta_cancelled',
                'label' => 'Bosta - Cancelled',
                'state' => Order::STATE_CANCELED,
                'visible_on_front' => true,
                'description' => 'Bosta delivery has been cancelled'
            ],
            [
                'status' => 'bosta_terminated',
                'label' => 'Bosta - Terminated',
                'state' => Order::STATE_CANCELED,
                'visible_on_front' => true,
                'description' => 'Bosta delivery has been terminated/failed'
            ]
        ];

        foreach ($statuses as $statusData) {
            $this->createStatus(
                $statusData['status'],
                $statusData['label'],
                $statusData['state'],
                $statusData['visible_on_front']
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Create order status and assign to state
     *
     * @param string $statusCode
     * @param string $statusLabel
     * @param string $state
     * @param bool $visibleOnFront
     * @return void
     */
    private function createStatus(
        string $statusCode,
        string $statusLabel,
        string $state,
        bool $visibleOnFront = true
    ): void {
        /** @var Status $status */
        $status = $this->statusFactory->create();
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();

        // Load existing status if exists
        $statusResource->load($status, $statusCode);

        // Only create if doesn't exist
        if (!$status->getStatus()) {
            $status->setData([
                'status' => $statusCode,
                'label' => $statusLabel
            ]);

            try {
                $statusResource->save($status);

                // Assign status to state
                $status->assignState($state, false, $visibleOnFront);
            } catch (\Exception $e) {
                // Status might already exist, continue
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
