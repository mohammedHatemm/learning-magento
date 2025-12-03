<?php
/**
 * Copyright © Elsherif. All rights reserved.
 * Observer for customer save/update event
 */

namespace Elsherif\ErpIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Elsherif\ErpIntegration\Model\ErpClient;
use Elsherif\ErpIntegration\Model\CustomerDataFormatter;
use Psr\Log\LoggerInterface;

class CustomerSaveAfter implements ObserverInterface
{
    const XML_PATH_SYNC_ON_UPDATE = 'erp_integration/sync_settings/sync_on_update';

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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErpClient $erpClient
     * @param CustomerDataFormatter $customerDataFormatter
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErpClient $erpClient,
        CustomerDataFormatter $customerDataFormatter,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->erpClient = $erpClient;
        $this->customerDataFormatter = $customerDataFormatter;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Execute observer when customer is saved/updated
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if sync on update is enabled
        $syncEnabled = $this->scopeConfig->getValue(
            self::XML_PATH_SYNC_ON_UPDATE,
            ScopeInterface::SCOPE_STORE
        );

        if (!$syncEnabled || !$this->erpClient->isEnabled()) {
            return;
        }

        try {
            // Get customer from event
            $customerDataObject = $observer->getEvent()->getCustomerDataObject();

            if (!$customerDataObject) {
                // Try alternative event data
                $customer = $observer->getEvent()->getCustomer();
                if ($customer && $customer->getId()) {
                    $customerDataObject = $this->customerRepository->getById($customer->getId());
                }
            }

            if (!$customerDataObject || !$customerDataObject->getId()) {
                $this->logger->warning('[ERP Integration] Customer object not found in save event');
                return;
            }

            // Check if this is an existing customer (not a new registration)
            // New registrations are handled by CustomerRegisterSuccess observer
            $customerId = $customerDataObject->getId();

            // Format customer data for ERP
            $customerData = $this->customerDataFormatter->formatCustomerData($customerDataObject);

            // Check if customer has ERP ID stored (you may want to store this in a custom attribute)
            // For now, we'll use the Magento customer ID as the ERP ID
            // You can modify this logic based on how your ERP returns customer IDs
            $erpCustomerId = $customerId; // or get from custom attribute

            // Update in ERP
            $result = $this->erpClient->updateCustomerInErp($erpCustomerId, $customerData);

            if ($result['success']) {
                $this->logger->info('[ERP Integration] Customer updated successfully in ERP. Customer ID: ' . $customerId);
            } else {
                // If update fails, maybe customer doesn't exist in ERP yet, try to create
                $this->logger->warning('[ERP Integration] Update failed, attempting to create customer in ERP');
                $createResult = $this->erpClient->sendCustomerToErp($customerData);

                if ($createResult['success']) {
                    $this->logger->info('[ERP Integration] Customer created successfully in ERP. Customer ID: ' . $customerId);
                } else {
                    $this->logger->error('[ERP Integration] Failed to sync customer to ERP: ' . $createResult['message']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('[ERP Integration] Exception in customer save observer: ' . $e->getMessage());
        }
    }
}
