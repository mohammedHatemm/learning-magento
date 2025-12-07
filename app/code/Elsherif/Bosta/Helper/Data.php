<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ACTIVE = 'carriers/customshipping/active';
    const XML_PATH_API_KEY = 'carriers/customshipping/api_key';
    const XML_PATH_API_MODE = 'carriers/customshipping/api_mode';
    const XML_PATH_PICKUP_LOCATION = 'carriers/customshipping/pickup_location_id';
    const XML_PATH_DEBUG = 'carriers/customshipping/debug';

    const API_BASE_URL_PRODUCTION = 'https://app.bosta.co';
    const API_BASE_URL_STAGING = 'https://stg-app.bosta.co';

    const CACHE_TAG = 'bosta_api';
    const CACHE_LIFETIME = 3600; // 1 hour

    private Curl $curl;
    private Json $json;
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(
        Context         $context,
        Curl            $curl,
        Json            $json,
        LoggerInterface $logger,
        CacheInterface  $cache
    )
    {
        parent::__construct($context);
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API Key
     */
    public function getApiKey(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API Mode (production or staging)
     */
    public function isProductionMode(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_API_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Pickup Location ID
     */
    public function getPickupLocationId(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PICKUP_LOCATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API Base URL
     */
    public function getApiBaseUrl(?int $storeId = null): string
    {
        return $this->isProductionMode($storeId)
            ? self::API_BASE_URL_PRODUCTION
            : self::API_BASE_URL_STAGING;
    }

    /**
     * Make API Request
     */
    private function makeRequest(string $endpoint, string $method = 'GET', ?array $data = null, ?int $storeId = null): array
    {
        $apiKey = $this->getApiKey($storeId);
        $baseUrl = $this->getApiBaseUrl($storeId);
        $url = $baseUrl . $endpoint;

        try {
            // Set headers
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $apiKey,
                'Accept' => 'application/json'
            ]);

            // Set timeout
            $this->curl->setTimeout(30);

            // Make request
            if ($method === 'POST') {
                $this->curl->post($url, $data ? $this->json->serialize($data) : '');
            } elseif ($method === 'PUT') {
                $this->curl->put($url, $data ? $this->json->serialize($data) : '');
            } elseif ($method === 'DELETE') {
                $this->curl->delete($url);
            } else {
                $this->curl->get($url);
            }

            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            // Log request and response in debug mode
            if ($this->isDebugEnabled($storeId)) {
                $this->logger->info('Bosta API Request', [
                    'url' => $url,
                    'method' => $method,
                    'data' => $data,
                    'status' => $statusCode,
                    'response' => $response
                ]);
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $this->json->unserialize($response)
                ];
            } else {
                // Try to parse error message from Bosta API response
                $errorMessage = 'API returned status code: ' . $statusCode;
                try {
                    $errorData = $this->json->unserialize($response);
                    if (isset($errorData['message'])) {
                        $errorMessage = $errorData['message'];
                    } elseif (isset($errorData['error'])) {
                        $errorMessage = $errorData['error'];
                    }
                } catch (\Exception $e) {
                    // If JSON parsing fails, use the default error message
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Bosta API Error: ' . $e->getMessage(), [
                'url' => $url,
                'method' => $method,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all cities supported by Bosta (with caching)
     */
    public function getCities(?int $storeId = null): array
    {
        $cacheKey = self::CACHE_TAG . '_cities_' . ($storeId ?? 'default');

        // Try to get from cache
        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData) {
            $result = $this->json->unserialize($cachedData);
            if (isset($result['success']) && $result['success']) {
                return $result;
            }
        }

        // Fetch from API
        $result = $this->makeRequest('/api/v2/cities', 'GET', null, $storeId);

        // Cache successful responses
        if ($result['success']) {
            $this->cache->save(
                $this->json->serialize($result),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );
        }

        return $result;
    }

    /**
     * Get zones for a specific city
     */
    public function getCityZones(string $cityId, ?int $storeId = null): array
    {
        return $this->makeRequest('/api/v2/cities/' . $cityId . '/zones', 'GET', null, $storeId);
    }

    /**
     * Create delivery
     */
    public function createDelivery(array $deliveryData, ?int $storeId = null): array
    {
        return $this->makeRequest('/api/v2/deliveries', 'POST', $deliveryData, $storeId);
    }

    /**
     * Create delivery for a Magento order
     * Prepares delivery data from order and creates delivery via Bosta API
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array|null Returns delivery data from API or null on failure
     */
    public function createDeliveryForOrder($order): ?array
    {
        try {
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress) {
                $this->logger->error('Bosta: No shipping address found for order #' . $order->getIncrementId());
                return null;
            }

            // Prepare street address
            $street = $shippingAddress->getStreet();
            $streetAddress = is_array($street) ? implode(', ', $street) : $street;

            // Validate and format phone number
            $phone = $this->formatPhoneNumber($shippingAddress->getTelephone());
            if (!$phone) {
                $this->logger->error('Bosta: Invalid phone number for order #' . $order->getIncrementId(), [
                    'phone' => $shippingAddress->getTelephone()
                ]);
                throw new \Exception('Invalid phone number format. Egyptian numbers must start with 010, 011, 012, or 015.');
            }

            // Validate region/zone
            $region = $shippingAddress->getRegion();
            if (empty($region) || stripos($region, 'please select') !== false) {
                $this->logger->error('Bosta: Invalid region for order #' . $order->getIncrementId(), [
                    'region' => $region
                ]);
                throw new \Exception('Please select a valid region/state for shipping.');
            }

            // Determine delivery type and COD
            $paymentMethod = $order->getPayment()->getMethod();
            $isCOD = (strpos($paymentMethod, 'cashondelivery') !== false);
            $codAmount = $isCOD ? $order->getGrandTotal() : 0;

            // Get store ID as integer
            $storeId = (int)$order->getStoreId();

            // Prepare delivery data for Bosta API
            $deliveryData = [
                'type' => $isCOD ? 20 : 10, // 10 = Send Package, 20 = Cash Collection (COD)
                'cod' => $codAmount,
                'dropOffAddress' => [
                    'city' => $shippingAddress->getCity(),
                    'zone' => $region,
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
                    'phone' => $phone,
                    'email' => $order->getCustomerEmail()
                ],
                'businessReference' => $order->getIncrementId(),
                'notes' => 'Order #' . $order->getIncrementId(),
                'allowToOpenPackage' => false,
                'pickupLocationId' => $this->getPickupLocationId($storeId)
            ];

            // Create delivery via Bosta API
            $result = $this->createDelivery($deliveryData, $storeId);

            if ($result['success'] && isset($result['data'])) {
                // Bosta API returns double-wrapped response: {success, message, data: {...}}
                // Extract the inner data object which contains the actual delivery info
                $deliveryResponse = $result['data'];

                // Check if Bosta's response contains nested data
                if (isset($deliveryResponse['data'])) {
                    $deliveryResponse = $deliveryResponse['data'];
                }

                $this->logger->info('Bosta delivery created successfully', [
                    'order_id' => $order->getIncrementId(),
                    'tracking_number' => $deliveryResponse['trackingNumber'] ?? null
                ]);
                return $deliveryResponse;
            } else {
                $this->logger->error('Failed to create Bosta delivery', [
                    'order_id' => $order->getIncrementId(),
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Bosta delivery creation error: ' . $e->getMessage(), [
                'order_id' => $order->getIncrementId(),
                'exception' => $e
            ]);
            return null;
        }
    }
//

    /**
     * Track delivery by Bosta Delivery ID
     *
     * IMPORTANT: Bosta API doesn't provide a direct endpoint to get delivery details by ID.
     * The AWB URL is only available through the business dashboard, not via API.
     * This method exists for compatibility but may not work for all delivery IDs.
     *
     * @param string $deliveryId Bosta Delivery ID (from delivery creation response)
     * @param int|null $storeId
     * @return array
     */
    public function trackDelivery(string $deliveryId, ?int $storeId = null): array
    {
        // Note: This endpoint may return 404. Bosta API structure changed.
        // AWB URL might not be available via API - only in business dashboard
        return $this->makeRequest('/api/v2/deliveries/' . $deliveryId, 'GET', null, $storeId);
    }

    /**
     * Generate mass AWB (Air Waybill) PDF for deliveries
     *
     * This generates the AWB PDF directly and returns it as a file stream
     *
     * @param string|array $trackingNumbers Single tracking number or array of tracking numbers
     * @param string $awbType "A4" or "A6" paper size
     * @param string $lang "ar" or "en" language
     * @param int|null $storeId
     * @return array Response with PDF data or error
     */
    public function generateMassAwb($trackingNumbers, string $awbType = 'A4', string $lang = 'ar', ?int $storeId = null): array
    {
        // Convert array to comma-separated string if needed
        if (is_array($trackingNumbers)) {
            $trackingNumbers = implode(',', $trackingNumbers);
        }

        $requestData = [
            'trackingNumbers' => $trackingNumbers,
            'requestedAwbType' => $awbType, // A4 or A6
            'lang' => $lang  // ar or en
        ];

        return $this->makeRequestBinary('/api/v2/deliveries/mass-awb', 'POST', $requestData, $storeId);
    }

    /**
     * Make API request that returns binary data (PDF, images, etc)
     *
     * @param string $endpoint
     * @param string $method
     * @param array|null $data
     * @param int|null $storeId
     * @return array
     */
    /**
     * Make API request that returns binary data (PDF, images, etc)
     */
    private function makeRequestBinary(string $endpoint, string $method = 'GET', ?array $data = null, ?int $storeId = null): array
    {
        $apiKey = $this->getApiKey($storeId);
        $baseUrl = $this->getApiBaseUrl($storeId);
        $url = $baseUrl . $endpoint;

        try {
            // Set headers
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $apiKey,
                'Accept' => 'application/json'  // Changed from 'application/pdf'
            ]);

            // Set timeout
            $this->curl->setTimeout(30);

            // Make request
            if ($method === 'POST') {
                $this->curl->post($url, $data ? $this->json->serialize($data) : '');
            } elseif ($method === 'GET') {
                $this->curl->get($url);
            }

            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            // Log request in debug mode
            if ($this->isDebugEnabled($storeId)) {
                $this->logger->info('Bosta API Binary Request', [
                    'url' => $url,
                    'method' => $method,
                    'data' => $data,
                    'status' => $statusCode,
                    'response_size' => strlen($response) . ' bytes'
                ]);
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                // Try to parse as JSON first
                try {
                    $jsonData = $this->json->unserialize($response);

                    // Check if it's a JSON response with base64 data
                    if (isset($jsonData['success']) && isset($jsonData['data'])) {
                        return [
                            'success' => true,
                            'data' => $jsonData['data'],  // Base64 string
                            'is_base64' => true
                        ];
                    }
                } catch (\Exception $e) {
                    // Not JSON, treat as binary
                }

                // If not JSON, return raw binary data
                return [
                    'success' => true,
                    'data' => $response,  // Binary PDF data
                    'is_base64' => false
                ];
            } else {
                $errorMessage = 'API returned status code: ' . $statusCode;
                try {
                    $errorData = $this->json->unserialize($response);
                    if (isset($errorData['message'])) {
                        $errorMessage = $errorData['message'];
                    }
                } catch (\Exception $e) {
                    $errorMessage = $response;
                }

                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Bosta API Binary Request Error: ' . $e->getMessage(), [
                'url' => $url,
                'method' => $method
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get pickup locations
     */
    public function getPickupLocations(?int $storeId = null): array
    {
        return $this->makeRequest('/api/v2/pickups/business-locations', 'GET', null, $storeId);
    }

    /**
     * Create pickup request
     */
    public function createPickup(array $pickupData, ?int $storeId = null): array
    {
        return $this->makeRequest('/api/v2/pickups', 'POST', $pickupData, $storeId);
    }

    /**
     * Get delivery pricing from Bosta API
     * Uses Bosta's pricing calculator endpoint
     *
     * @param string $cityId The Bosta city ID
     * @param int $citySector The sector number (1-7) that the city belongs to
     * @param string $type Service type (SEND, CASH_COLLECTION, etc.)
     * @param float $codAmount Cash on delivery amount
     * @param int|null $storeId
     * @return array
     */
    public function getDeliveryPrice(string $cityId, int $citySector, string $type = 'SEND', float $codAmount = 0, ?int $storeId = null): array
    {
        // Build query parameters for pricing calculator
        $params = [
            'type' => $type,  // SEND, CASH_COLLECTION, RTO, etc.
            'cod' => $codAmount,  // COD amount if applicable
            'cityId' => $cityId,
            'pickupSectorId' => 1  // Cairo/Giza sector (default pickup location)
        ];

        $queryString = http_build_query($params);
        $endpoint = '/api/v2/pricing/calculator?' . $queryString;

        // Call Bosta pricing calculator API
        $result = $this->makeRequest($endpoint, 'GET', null, $storeId);

        if ($result['success']) {
            // Handle double-wrapped response: {success: true, data: {success: true, data: {...}}}
            $data = $result['data'] ?? [];

            // Check for nested data structure
            if (isset($data['data'])) {
                $data = $data['data'];
            }

            // Extract pricing from the complex response structure
            // Note: The 'cost' field in tierSizes already includes all fees
            if (isset($data['prices']) && is_array($data['prices'])) {
                // Find the price for the CORRECT dropoff sector (not the first one!)
                // The API returns prices for all 7 sectors, we need to match the city's sector
                foreach ($data['prices'] as $priceInfo) {
                    // Check if this price entry matches the city's sector
                    $dropoffSectorId = (int)($priceInfo['dropoffSectorId'] ?? 0);

                    if ($dropoffSectorId !== $citySector) {
                        // This is not the correct sector, skip it
                        continue;
                    }

                    // Found the correct sector! Now get the pricing
                    if (isset($priceInfo['tierServiceTypes']) && is_array($priceInfo['tierServiceTypes'])) {
                        // Find the requested service type
                        foreach ($priceInfo['tierServiceTypes'] as $serviceType) {
                            if ($serviceType['typeName'] === $type && isset($serviceType['tierSizes']) && is_array($serviceType['tierSizes'])) {
                                // Get "Normal" size price (most common), fallback to first available
                                foreach ($serviceType['tierSizes'] as $size) {
                                    if (isset($size['cost'])) {
                                        $sizeCost = $size['sizeName'] === 'Normal' ? $size['cost'] : null;
                                        if ($sizeCost !== null) {
                                            // The size cost from Bosta API is the complete delivery price
                                            $totalPrice = (float)$sizeCost;

                                            // Log the price for debugging
                                            if ($this->isDebugEnabled($storeId)) {
                                                $this->logger->info('Bosta Shipping Price', [
                                                    'price' => $totalPrice,
                                                    'size' => $size['sizeName'],
                                                    'city_id' => $cityId,
                                                    'city_sector' => $citySector,
                                                    'dropoff_sector_id' => $dropoffSectorId,
                                                    'sector_name' => $priceInfo['dropoffSectorName'] ?? 'Unknown'
                                                ]);
                                            }

                                            return [
                                                'success' => true,
                                                'price' => $totalPrice,
                                                'currency' => 'EGP',
                                                'sector' => $priceInfo['dropoffSectorName'] ?? 'Unknown'
                                            ];
                                        }
                                    }
                                }
                                // If Normal not found, use first available size
                                if (isset($serviceType['tierSizes'][0]['cost'])) {
                                    $totalPrice = (float)$serviceType['tierSizes'][0]['cost'];

                                    if ($this->isDebugEnabled($storeId)) {
                                        $this->logger->info('Bosta Shipping Price (fallback size)', [
                                            'price' => $totalPrice,
                                            'size' => $serviceType['tierSizes'][0]['sizeName'] ?? 'Unknown',
                                            'city_id' => $cityId,
                                            'city_sector' => $citySector,
                                            'sector_name' => $priceInfo['dropoffSectorName'] ?? 'Unknown'
                                        ]);
                                    }

                                    return [
                                        'success' => true,
                                        'price' => $totalPrice,
                                        'currency' => 'EGP',
                                        'sector' => $priceInfo['dropoffSectorName'] ?? 'Unknown'
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Log error with details
        $this->logger->error('Bosta pricing calculator API failed', [
            'endpoint' => $endpoint,
            'params' => $params,
            'city_sector' => $citySector,
            'response' => $result
        ]);

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Unable to calculate shipping rate from Bosta API',
            'price' => 0.0
        ];
    }

    /**
     * Calculate shipping rate using real Bosta API (with caching)
     * This method calls Bosta's pricing calculator API
     */
    public function calculateShippingRate(string $city, float $weight, ?int $storeId = null): float
    {
        // Create cache key based on city and weight (rounded to nearest kg)
        $weightRounded = ceil($weight);
        $cacheKey = self::CACHE_TAG . '_price_' . md5($city . '_' . $weightRounded . '_' . ($storeId ?? 'default'));

        // Try to get from cache (shorter cache time for pricing - 15 minutes)
        $cachedPrice = $this->cache->load($cacheKey);
        if ($cachedPrice !== false && $cachedPrice > 0) {
            return (float)$cachedPrice;
        }

        // Get city ID and sector from city name
        $cityInfo = $this->getCityIdByName($city, $storeId);

        if (!$cityInfo || !isset($cityInfo['city_id']) || !isset($cityInfo['sector'])) {
            $this->logger->error('Cannot calculate rate: City not found', [
                'city' => $city
            ]);
            return 0.0;
        }

        $cityId = $cityInfo['city_id'];
        $citySector = $cityInfo['sector'];

        // Call Bosta pricing calculator with city sector
        // type: CASH_COLLECTION = Most common in Egypt (COD)
        // cod: 0 (COD amount - 0 for rate estimation, actual amount set at order time)
        $priceResult = $this->getDeliveryPrice($cityId, $citySector, 'CASH_COLLECTION', 0, $storeId);

        if ($priceResult['success']) {
            $price = (float)$priceResult['price'];

            // Cache the price for 15 minutes
            $this->cache->save(
                (string)$price,
                $cacheKey,
                [self::CACHE_TAG],
                900 // 15 minutes
            );

            return $price;
        }

        // If API fails, log error and return 0 (will cause shipping method to not be available)
        $this->logger->error('Unable to calculate shipping rate from Bosta API', [
            'city' => $city,
            'city_id' => $cityId,
            'city_sector' => $citySector,
            'weight' => $weight,
            'error' => $priceResult['error'] ?? 'Unknown error'
        ]);

        // Return 0 to indicate shipping not available
        return 0.0;
    }

    /**
     * Get Bosta City ID and sector by city name
     * Fetches from API and caches result
     *
     * @return array|null Returns ['city_id' => string, 'sector' => int] or null if not found
     */
    private function getCityIdByName(string $cityName, ?int $storeId = null): ?array
    {
        // Get cities from Bosta API
        $citiesResult = $this->getCities($storeId);

        if (!$citiesResult['success']) {
            $this->logger->error('Failed to get cities from Bosta API', [
                'result' => $citiesResult
            ]);
            return null;
        }

        // Handle double-wrapped response from Bosta API
        // Response structure: {success: true, data: {success: true, data: {list: [...]}}}
        $data = $citiesResult['data'] ?? [];

        // Check if it's double-wrapped (Bosta returns success/data again)
        if (isset($data['data']) && isset($data['data']['list'])) {
            $cities = $data['data']['list'];
        } elseif (isset($data['list'])) {
            $cities = $data['list'];
        } else {
            $this->logger->error('Cities data structure unexpected', [
                'data_keys' => array_keys($data)
            ]);
            return null;
        }

        // Search for city by name (case-insensitive)
        $cityNameLower = strtolower(trim($cityName));

        foreach ($cities as $city) {
            $apiCityName = strtolower($city['name'] ?? '');
            $apiCityNameAr = strtolower($city['nameAr'] ?? '');

            if ($apiCityName === $cityNameLower || $apiCityNameAr === $cityNameLower) {
                // Return both city ID and sector number
                return [
                    'city_id' => $city['_id'] ?? null,
                    'sector' => (int)($city['sector'] ?? 1)  // Default to sector 1 (Cairo) if missing
                ];
            }
        }

        // City not found - log with available cities for debugging
        $this->logger->warning('City not found in Bosta API', [
            'city_name' => $cityName,
            'available_cities' => array_column($cities, 'name')
        ]);

        return null;
    }

    /**
     * Normalize city name for API lookup
     */
    public function normalizeCityName(string $city): string
    {
        // Common city name variations
        $cityMap = [
            'alex' => 'Alexandria',
            'asyut' => 'Assiut'
        ];

        $cityLower = strtolower(trim($city));
        return $cityMap[$cityLower] ?? $city;
    }

    /**
     * Get cities formatted for dropdown options
     * Returns array of ['value' => city_name, 'label' => 'City Name (Arabic)']
     */
    public function getCitiesForDropdown(?int $storeId = null): array
    {
        $citiesResult = $this->getCities($storeId);

        if (!$citiesResult['success']) {
            return [];
        }

        // Handle double-wrapped response
        $data = $citiesResult['data'] ?? [];

        if (isset($data['data']) && isset($data['data']['list'])) {
            $cities = $data['data']['list'];
        } elseif (isset($data['list'])) {
            $cities = $data['list'];
        } else {
            return [];
        }

        $options = [];
        foreach ($cities as $city) {
            $cityName = $city['name'] ?? '';
            $cityNameAr = $city['nameAr'] ?? '';

            if ($cityName) {
                $options[] = [
                    'value' => $cityName,
                    'label' => $cityName . ($cityNameAr ? ' - ' . $cityNameAr : '')
                ];
            }
        }

        // Sort alphabetically by label
        usort($options, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return $options;
    }

    /**
     * Get zones for a city formatted for dropdown
     */
    public function getZonesForDropdown(string $cityName, ?int $storeId = null): array
    {
        $cityInfo = $this->getCityIdByName($cityName, $storeId);

        if (!$cityInfo || !isset($cityInfo['city_id'])) {
            return [];
        }

        $cityId = $cityInfo['city_id'];
        $zonesResult = $this->getCityZones($cityId, $storeId);

        if (!$zonesResult['success']) {
            return [];
        }

        // Handle double-wrapped response
        $data = $zonesResult['data'] ?? [];

        if (isset($data['data']) && isset($data['data']['list'])) {
            $zones = $data['data']['list'];
        } elseif (isset($data['list'])) {
            $zones = $data['list'];
        } else {
            return [];
        }

        $options = [];
        foreach ($zones as $zone) {
            $zoneName = $zone['name'] ?? '';

            if ($zoneName) {
                $options[] = [
                    'value' => $zoneName,
                    'label' => $zoneName
                ];
            }
        }

        return $options;
    }

    /**
     * Format and validate Egyptian phone number for Bosta API
     * Bosta accepts Egyptian mobile numbers starting with 010, 011, 012, 015
     *
     * @param string $phone Raw phone number
     * @return string|null Formatted phone number or null if invalid
     */
    private function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading country code if present (+20 or 20)
        if (strlen($phone) === 12 && substr($phone, 0, 2) === '20') {
            $phone = '0' . substr($phone, 2);
        }

        // Egyptian mobile numbers should be 11 digits starting with 0
        if (strlen($phone) !== 11) {
            return null;
        }

        // Must start with valid Egyptian mobile prefixes: 010, 011, 012, 015
        $validPrefixes = ['010', '011', '012', '015'];
        $prefix = substr($phone, 0, 3);

        if (!in_array($prefix, $validPrefixes)) {
            return null;
        }

        return $phone;
    }

    /**
     * Get Bosta shipping terms and policies
     * Returns shipping policy information to display to customers
     *
     * @param \Elsherif\Bosta\Model\Delivery|null $delivery
     * @return array Shipping policy information
     */
    public function getShippingPolicy($delivery = null): array
    {
        $policy = [
            'title' => __('Bosta Shipping Policy & Terms'),
            'sections' => [
                [
                    'heading' => __('Delivery Times'),
                    'content' => __('Standard delivery within Egypt typically takes 2-5 business days depending on the destination city and sector.')
                ],
                [
                    'heading' => __('Tracking'),
                    'content' => __('You can track your shipment 24/7 using the tracking number provided. Updates are available in real-time.')
                ],
                [
                    'heading' => __('Cash on Delivery (COD)'),
                    'content' => __('COD payments are collected by the courier upon delivery. The amount will be transferred to your account according to Bosta\'s payment schedule.')
                ],
                [
                    'heading' => __('Returns & Exchanges'),
                    'content' => __('Returns are handled according to your store\'s return policy. Bosta facilitates the return shipping process.')
                ],
                [
                    'heading' => __('Lost or Damaged Packages'),
                    'content' => __('In case of lost or damaged packages, please contact Bosta customer support within 48 hours of delivery for assistance.')
                ],
                [
                    'heading' => __('Contact Bosta Support'),
                    'content' => __('For any shipping inquiries, contact Bosta at: 16672 or support@bosta.co')
                ]
            ]
        ];

        // Add delivery-specific information if available
        if ($delivery && $delivery->getId()) {
            $deliveryType = $delivery->getDeliveryType() == 20 ? __('Cash on Delivery (COD)') : __('Prepaid Package');
            $policy['delivery_info'] = [
                'type' => $deliveryType,
                'tracking' => $delivery->getTrackingNumber(),
                'status' => $delivery->getStatus()
            ];
        }

        return $policy;
    }
}
