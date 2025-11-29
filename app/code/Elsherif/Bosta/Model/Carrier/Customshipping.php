<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\Carrier;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\Config\Source\CalculationMethod;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Customshipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'customshipping';

    protected $_isFixed = true;

    private ResultFactory $rateResultFactory;

    private MethodFactory $rateMethodFactory;

    private BostaHelper $bostaHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory         $rateErrorFactory,
        LoggerInterface      $logger,
        ResultFactory        $rateResultFactory,
        MethodFactory        $rateMethodFactory,
        BostaHelper          $bostaHelper,
        array                $data = []
    )
    {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->bostaHelper = $bostaHelper;
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Check if Bosta helper is configured properly
        if (!$this->bostaHelper->isEnabled()) {
            return false;
        }

        // Get calculation method
        $calculationMethod = $this->getConfigData('calculation_method') ?: CalculationMethod::METHOD_FIXED;

        // Calculate shipping cost based on selected method
        $shippingCost = $this->calculateShippingCost($request, $calculationMethod);

        // Check for free shipping threshold
        $freeShippingThreshold = (float) $this->getConfigData('free_shipping_threshold');
        if ($freeShippingThreshold > 0 && $request->getPackageValue() >= $freeShippingThreshold) {
            $shippingCost = 0.00;
        }

        // Add handling fee if configured
        $handlingFee = (float) $this->getConfigData('handling_fee');
        if ($handlingFee > 0) {
            $shippingCost += $handlingFee;
        }

        // Add VAT if configured
        $includeVat = (bool) $this->getConfigData('include_vat');
        if ($includeVat && $shippingCost > 0) {
            $vatPercentage = (float) $this->getConfigData('vat_percentage') ?: 14.0;
            $vatAmount = $shippingCost * ($vatPercentage / 100);
            $shippingCost += $vatAmount;
        }

        // Create shipping method
        /** @var Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);

        /** @var Result $result */
        $result = $this->rateResultFactory->create();
        $result->append($method);

        return $result;
    }

    /**
     * Calculate shipping cost based on calculation method
     *
     * @param RateRequest $request
     * @param string $calculationMethod
     * @return float
     */
    private function calculateShippingCost(RateRequest $request, string $calculationMethod): float
    {
        switch ($calculationMethod) {
            case CalculationMethod::METHOD_FIXED:
                return $this->calculateFixedRate();

            case CalculationMethod::METHOD_BY_WEIGHT:
                return $this->calculateByWeight($request);

            case CalculationMethod::METHOD_BY_CITY:
                return $this->calculateByCity($request);

            case CalculationMethod::METHOD_BY_CITY_AND_WEIGHT:
                return $this->calculateByCityAndWeight($request);

            default:
                return $this->calculateFixedRate();
        }
    }

    /**
     * Calculate fixed rate
     *
     * @return float
     */
    private function calculateFixedRate(): float
    {
        return (float) $this->getConfigData('shipping_cost');
    }

    /**
     * Calculate rate by weight
     *
     * @param RateRequest $request
     * @return float
     */
    private function calculateByWeight(RateRequest $request): float
    {
        $weight = (float) $request->getPackageWeight();

        // Set minimum weight if empty or zero
        if ($weight <= 0) {
            $weight = 1.0;
        }

        $baseRate = 25.00; // Base rate in EGP
        $perKgRate = 5.00; // Rate per kg

        return $baseRate + ($weight * $perKgRate);
    }

    /**
     * Calculate rate by city
     *
     * @param RateRequest $request
     * @return float
     */
    private function calculateByCity(RateRequest $request): float
    {
        $city = $request->getDestCity();

        if (empty($city)) {
            return $this->calculateFixedRate();
        }

        // Normalize city name
        $normalizedCity = $this->bostaHelper->normalizeCityName($city);

        // Get rate from helper (uses city-based rate matrix)
        $weight = 1.0; // Default weight for city-only calculation
        return $this->bostaHelper->calculateShippingRate($normalizedCity, $weight);
    }

    /**
     * Calculate rate by city and weight (Recommended method)
     *
     * @param RateRequest $request
     * @return float
     */
    private function calculateByCityAndWeight(RateRequest $request): float
    {
        $city = $request->getDestCity();
        $weight = (float) $request->getPackageWeight();

        // Set minimum weight if empty or zero
        if ($weight <= 0) {
            $weight = 1.0;
        }

        if (empty($city)) {
            return $this->calculateByWeight($request);
        }

        // Normalize city name
        $normalizedCity = $this->bostaHelper->normalizeCityName($city);

        // Calculate using Bosta helper with city and weight
        return $this->bostaHelper->calculateShippingRate($normalizedCity, $weight);
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }

    /**
     * Get tracking information
     *
     * @param string $trackingNumber
     * @return \Magento\Shipping\Model\Tracking\Result|bool
     */
    public function getTrackingInfo($trackingNumber)
    {
        // This can be implemented to fetch tracking info from Bosta API
        // For now, returning false
        return false;
    }
}
