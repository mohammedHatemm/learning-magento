<?php
/**
 * Address Repository Plugin
 *
 * This plugin intercepts the AddressRepository save method to:
 * 1. Prevent duplicate addresses for customers
 * 2. Update existing default address instead of creating new ones
 * 3. Set proper default flags so addresses appear in customer dashboard
 *
 * @author Custom Development Team
 * @version 1.0.0
 */

namespace Custom\AddressManager\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class AddressRepositoryPlugin
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Constructor
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Around plugin for save method
     *
     * Intercepts address save to prevent duplicates and set default flags
     *
     * @param AddressRepositoryInterface $subject
     * @param callable $proceed
     * @param AddressInterface $address
     * @return AddressInterface
     * @throws LocalizedException
     */
    public function aroundSave(
        AddressRepositoryInterface $subject,
        callable $proceed,
        AddressInterface $address
    ) {
        // Get customer ID
        $customerId = $address->getCustomerId();

        // If no customer ID, proceed normally (shouldn't happen, but safety check)
        if (!$customerId) {
            return $proceed($address);
        }

        try {
            // Check if this customer already has a default shipping address
            $existingDefaultAddress = $this->getCustomerDefaultShippingAddress($customerId);

            if ($existingDefaultAddress) {
                // Customer has existing default address - UPDATE it instead of creating new
                $this->logger->info("AddressManager: Updating existing address for customer {$customerId}");

                // Transfer data from new address to existing address
                $this->updateExistingAddress($existingDefaultAddress, $address);

                // Save the updated existing address
                return $proceed($existingDefaultAddress);
            } else {
                // Customer has NO default address - Create new and set as default
                $this->logger->info("AddressManager: Creating first address for customer {$customerId}");

                // Proceed with normal save (creates new address)
                $savedAddress = $proceed($address);

                // Set this address as default
                $this->setAsDefaultAddress($savedAddress);

                return $savedAddress;
            }
        } catch (\Exception $e) {
            // Log error but don't break the checkout process
            $this->logger->error("AddressManager Error: " . $e->getMessage());

            // Proceed with normal save if our logic fails
            return $proceed($address);
        }
    }

    /**
     * Get customer's default shipping address if exists
     *
     * @param int $customerId
     * @return AddressInterface|null
     */
    protected function getCustomerDefaultShippingAddress($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);

            // Get default shipping address ID from customer
            $defaultShippingId = $customer->getDefaultShipping();

            if ($defaultShippingId) {
                // Try to load the address
                try {
                    return $this->addressRepository->getById($defaultShippingId);
                } catch (NoSuchEntityException $e) {
                    // Address doesn't exist anymore, return null
                    return null;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error("AddressManager: Error getting default address: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update existing address with new data
     *
     * @param AddressInterface $existingAddress
     * @param AddressInterface $newAddress
     * @return void
     */
    protected function updateExistingAddress(AddressInterface $existingAddress, AddressInterface $newAddress)
    {
        // Copy all data from new address to existing address
        $existingAddress->setFirstname($newAddress->getFirstname());
        $existingAddress->setLastname($newAddress->getLastname());
        $existingAddress->setCompany($newAddress->getCompany());
        $existingAddress->setStreet($newAddress->getStreet());
        $existingAddress->setCity($newAddress->getCity());
        $existingAddress->setRegion($newAddress->getRegion());
        $existingAddress->setRegionId($newAddress->getRegionId());
        $existingAddress->setPostcode($newAddress->getPostcode());
        $existingAddress->setCountryId($newAddress->getCountryId());
        $existingAddress->setTelephone($newAddress->getTelephone());
        $existingAddress->setFax($newAddress->getFax());

        // Keep default flags (they should already be set)
        $existingAddress->setIsDefaultBilling(true);
        $existingAddress->setIsDefaultShipping(true);

        $this->logger->info("AddressManager: Address data updated for entity_id {$existingAddress->getId()}");
    }

    /**
     * Set address as default for both billing and shipping
     *
     * @param AddressInterface $address
     * @return void
     */
    protected function setAsDefaultAddress(AddressInterface $address)
    {
        try {
            // Set address as default
            $address->setIsDefaultBilling(true);
            $address->setIsDefaultShipping(true);

            // Save the updated flags
            $this->addressRepository->save($address);

            // Update customer entity to reference this address
            $customerId = $address->getCustomerId();
            $addressId = $address->getId();

            $customer = $this->customerRepository->getById($customerId);
            $customer->setDefaultBilling($addressId);
            $customer->setDefaultShipping($addressId);
            $this->customerRepository->save($customer);

            $this->logger->info("AddressManager: Set address {$addressId} as default for customer {$customerId}");
        } catch (\Exception $e) {
            $this->logger->error("AddressManager: Error setting default address: " . $e->getMessage());
        }
    }
}
