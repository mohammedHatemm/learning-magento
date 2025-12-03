<?php
/**
 * Copyright © Elsherif. All rights reserved.
 * ERP API Client - Handles communication with ERP system
 */

namespace Elsherif\ErpIntegration\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ErpClient
{
    const XML_PATH_ENABLED = 'erp_integration/general/enabled';
    const XML_PATH_API_URL = 'erp_integration/general/api_url';
    const XML_PATH_API_TOKEN = 'erp_integration/general/api_token';
    const XML_PATH_TIMEOUT = 'erp_integration/general/timeout';
    const XML_PATH_LOG_ENABLED = 'erp_integration/sync_settings/log_enabled';

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Check if ERP integration is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API URL
     *
     * @return string
     */
    protected function getApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API Token (decrypted)
     *
     * @return string
     */
    protected function getApiToken()
    {
        $encryptedToken = $this->scopeConfig->getValue(
            self::XML_PATH_API_TOKEN,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($encryptedToken);
    }

    /**
     * Get API timeout
     *
     * @return int
     */
    protected function getTimeout()
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE
        ) ?: 30;
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    protected function isLogEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Send customer data to ERP
     *
     * @param array $customerData
     * @return array Response with 'success' and 'message' keys
     */
    public function sendCustomerToErp(array $customerData)
    {
        if (!$this->isEnabled()) {
            $this->log('ERP Integration is disabled');
            return ['success' => false, 'message' => 'ERP Integration is disabled'];
        }

        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if (empty($apiUrl) || empty($apiToken)) {
            $this->log('ERP API URL or Token is not configured');
            return ['success' => false, 'message' => 'API URL or Token not configured'];
        }

        try {
            $this->log('Sending customer data to ERP: ' . $this->json->serialize($customerData));

            // Wrap data in 'data' object for Frappe/ERPNext API
            $payload = ['data' => $customerData];

            // Configure cURL client
            $this->curl->setOption(CURLOPT_TIMEOUT, $this->getTimeout());
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

            // Set headers
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', $apiToken);

            // Send POST request
            $this->curl->post($apiUrl, $this->json->serialize($payload));

            // Get response
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->log("ERP Response Code: {$responseCode}, Body: {$responseBody}");

            if ($responseCode >= 200 && $responseCode < 300) {
                return [
                    'success' => true,
                    'message' => 'Customer data sent successfully to ERP',
                    'response' => $responseBody
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "ERP returned error code: {$responseCode}",
                    'response' => $responseBody
                ];
            }
        } catch (\Exception $e) {
            $this->log('Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update customer data in ERP
     *
     * @param string $erpCustomerId ERP customer ID
     * @param array $customerData
     * @return array Response with 'success' and 'message' keys
     */
    public function updateCustomerInErp($erpCustomerId, array $customerData)
    {
        if (!$this->isEnabled()) {
            $this->log('ERP Integration is disabled');
            return ['success' => false, 'message' => 'ERP Integration is disabled'];
        }

        $apiUrl = $this->getApiUrl() . '/' . $erpCustomerId;
        $apiToken = $this->getApiToken();

        if (empty($apiUrl) || empty($apiToken)) {
            $this->log('ERP API URL or Token is not configured');
            return ['success' => false, 'message' => 'API URL or Token not configured'];
        }

        try {
            $this->log('Updating customer in ERP: ' . $erpCustomerId . ' with data: ' . $this->json->serialize($customerData));

            // Wrap data in 'data' object for Frappe/ERPNext API
            $payload = ['data' => $customerData];

            // Configure cURL client
            $this->curl->setOption(CURLOPT_TIMEOUT, $this->getTimeout());
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');

            // Set headers
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', $apiToken);

            // Send PUT request
            $this->curl->post($apiUrl, $this->json->serialize($payload));

            // Get response
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->log("ERP Update Response Code: {$responseCode}, Body: {$responseBody}");

            if ($responseCode >= 200 && $responseCode < 300) {
                return [
                    'success' => true,
                    'message' => 'Customer data updated successfully in ERP',
                    'response' => $responseBody
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "ERP returned error code: {$responseCode}",
                    'response' => $responseBody
                ];
            }
        } catch (\Exception $e) {
            $this->log('Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Log message if logging is enabled
     *
     * @param string $message
     * @return void
     */
    protected function log($message)
    {
        if ($this->isLogEnabled()) {
            $this->logger->info('[ERP Integration] ' . $message);
        }
    }
}
