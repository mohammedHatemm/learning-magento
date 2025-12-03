<?php
/**
 * Copyright © Elsherif. All rights reserved.
 * Observer for customer registration success event
 */

namespace Elsherif\ErpIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Elsherif\ErpIntegration\Model\ErpClient;
use Elsherif\ErpIntegration\Model\CustomerDataFormatter;
use Psr\Log\LoggerInterface;

class CustomerRegisterSuccess implements ObserverInterface
{
    const XML_PATH_SYNC_ON_REGISTRATION = 'erp_integration/sync_settings/sync_on_registration';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ErpClient
     */
    protected $erpClient;

    /**
     * @var CustomerDataFormatter
     */
    protected $customerDataFormatter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErpClient $erpClient
     * @param CustomerDataFormatter $customerDataFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErpClient $erpClient,
        CustomerDataFormatter $customerDataFormatter,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->erpClient = $erpClient;
        $this->customerDataFormatter = $customerDataFormatter;
        $this->logger = $logger;
    }

    /**
     * Execute observer when customer registers
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if sync on registration is enabled
        $syncEnabled = $this->scopeConfig->getValue(
            self::XML_PATH_SYNC_ON_REGISTRATION,
            ScopeInterface::SCOPE_STORE
        );

        if (!$syncEnabled || !$this->erpClient->isEnabled()) {
            return;
        }

        try {
            // Get customer from event
            $customer = $observer->getEvent()->getCustomer();

            if (!$customer || !$customer->getId()) {
                $this->logger->warning('[ERP Integration] Customer object not found in registration event');
                return;
            }

            // Format customer data for ERP
            $customerData = $this->customerDataFormatter->formatCustomerData($customer);

            $customerId = $customer->getId();

            // Always try to update since customers are pre-created in ERP
            // The ERP uses the same numeric IDs as Magento customer IDs
            $result = $this->erpClient->updateCustomerInErp($customerId, $customerData);

            if ($result['success']) {
                $this->logger->info('[ERP Integration] Customer synced successfully to ERP. Customer ID: ' . $customerId);
            } else {
                $this->logger->error('[ERP Integration] Failed to sync customer to ERP: ' . $result['message']);
                // Note: If you need to CREATE new customers in ERP, you must first create them manually
                // in the ERP system, or use the ERP's customer creation API endpoint separately
            }
        } catch (\Exception $e) {
            $this->logger->error('[ERP Integration] Exception in customer registration observer: ' . $e->getMessage());
        }
    }
}
