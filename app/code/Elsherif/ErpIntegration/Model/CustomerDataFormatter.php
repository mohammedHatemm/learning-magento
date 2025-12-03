<?php
/**
 * Copyright © Elsherif. All rights reserved.
 * Customer Data Formatter - Prepares customer data for ERP
 */

namespace Elsherif\ErpIntegration\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomerDataFormatter
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Format customer data for ERP
     *
     * @param CustomerInterface $customer
     * @return array
     */
    public function formatCustomerData(CustomerInterface $customer)
    {
        $data = [
            'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'customer_type' => 'Individual', // or 'Company' based on your logic
            // Remove customer_group if it doesn't exist in ERP, or set to valid ERP customer group
            // 'customer_group' => 'Commercial', // Use a valid customer group from your ERP
            'territory' => 'All Territories', // Adjust based on your needs
            'email_id' => $customer->getEmail(),
            'mobile_no' => $this->getCustomerPhone($customer),
            'custom_magento_customer_id' => (string)$customer->getId(),
        ];

        // Add address information if available
        // Note: customer_primary_address in ERP is a Link field, not text
        // It requires an existing Address doctype name, so we skip it here
        // You can create addresses separately in ERP if needed
        try {
            $billingAddress = $this->getDefaultBillingAddress($customer);
            if ($billingAddress) {
                // Store address components separately instead of as primary_address link
                $data['city'] = $billingAddress->getCity();
                $data['country'] = $billingAddress->getCountryId();
                // You can add custom fields for street, postcode, etc.
                // $data['custom_street'] = implode(', ', $billingAddress->getStreet());
                // $data['custom_postcode'] = $billingAddress->getPostcode();
            }
        } catch (\Exception $e) {
            // Address not available, continue without it
        }

        // Add custom attributes if needed
        $customAttributes = $customer->getCustomAttributes();
        if (!empty($customAttributes)) {
            foreach ($customAttributes as $attribute) {
                $code = $attribute->getAttributeCode();
                if (in_array($code, ['gender', 'dob', 'taxvat'])) {
                    $data['custom_' . $code] = $attribute->getValue();
                }
            }
        }

        return $data;
    }

    /**
     * Get customer's default billing address
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    protected function getDefaultBillingAddress(CustomerInterface $customer)
    {
        $billingAddressId = $customer->getDefaultBilling();
        if ($billingAddressId) {
            try {
                return $this->addressRepository->getById($billingAddressId);
            } catch (LocalizedException $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get customer phone number
     *
     * @param CustomerInterface $customer
     * @return string|null
     */
    protected function getCustomerPhone(CustomerInterface $customer)
    {
        try {
            $billingAddress = $this->getDefaultBillingAddress($customer);
            if ($billingAddress) {
                return $billingAddress->getTelephone();
            }
        } catch (\Exception $e) {
            // No phone available
        }
        return null;
    }

    /**
     * Format address for ERP
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    protected function formatAddress($address)
    {
        $addressParts = [];

        if ($address->getStreet()) {
            $addressParts[] = implode(', ', $address->getStreet());
        }
        if ($address->getCity()) {
            $addressParts[] = $address->getCity();
        }
        if ($address->getRegion() && $address->getRegion()->getRegion()) {
            $addressParts[] = $address->getRegion()->getRegion();
        }
        if ($address->getPostcode()) {
            $addressParts[] = $address->getPostcode();
        }
        if ($address->getCountryId()) {
            $addressParts[] = $address->getCountryId();
        }

        return implode(', ', $addressParts);
    }

    /**
     * Get customer group name by ID
     *
     * @param int $groupId
     * @return string
     */
    protected function getCustomerGroupName($groupId)
    {
        $groups = [
            0 => 'NOT LOGGED IN',
            1 => 'General',
            2 => 'Wholesale',
            3 => 'Retailer'
        ];
        return $groups[$groupId] ?? 'General';
    }
}
