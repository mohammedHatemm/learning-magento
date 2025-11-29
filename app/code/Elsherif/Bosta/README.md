# Bosta Shipping Integration for Magento 2

Complete Bosta shipping integration module for Magento 2 with API client and flexible rate calculation.

## Features
//helow 

- Full Bosta API integration
- Multiple rate calculation methods:
  - Fixed Rate
  - By Weight
  - By City/Zone
  - By City and Weight (Recommended)
- Free shipping threshold support
- Handling fee configuration
- Debug mode for API logging
- Staging and Production API modes
- Secure API key storage (encrypted)

## Installation

1. Copy the module to your Magento installation:
   ```bash
   cp -r Elsherif /path/to/magento/app/code/
   ```

2. Enable the module:
   ```bash
   php bin/magento module:enable Elsherif_Bosta
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

## Configuration

Navigate to: **Stores → Configuration → Sales → Shipping Methods → Bosta Shipping**

### Basic Configuration

1. **Enabled**: Set to "Yes" to enable Bosta shipping
2. **Title**: Display title for customers (e.g., "Bosta Shipping")
3. **Method Name**: Shipping method name (e.g., "Bosta Express Delivery")

### API Configuration

1. **API Key**:
   - Get from [Bosta Business Dashboard](https://business.bosta.co) → Settings → API Integration
   - For testing, use: `fake-api-key-for-testing`

2. **API Mode**:
   - Yes = Production (https://app.bosta.co)
   - No = Staging (https://stg-app.bosta.co)

3. **Pickup Location ID**: Your default pickup location from Bosta dashboard

4. **Debug Mode**: Enable to log all API requests/responses

### Rate Calculation Settings

1. **Calculation Method**:
   - **Fixed Rate**: Uses configured flat rate
   - **By Weight**: Base rate + (weight × per kg rate)
   - **By City**: Rates based on destination city
   - **By City and Weight**: Most accurate, combines city and weight (Recommended)

2. **Fixed Shipping Cost**: Used when calculation method is "Fixed Rate"

3. **Free Shipping Threshold**: Offer free shipping above this order value

4. **Handling Fee**: Additional fee added to all shipments

## Rate Calculation Examples

### By City and Weight (Default)

Current rate matrix (adjust in `Helper/Data.php`):

| City | Base Rate | Per KG |
|------|-----------|--------|
| Cairo | 30 EGP | 5 EGP |
| Giza | 30 EGP | 5 EGP |
| Alexandria | 40 EGP | 7 EGP |
| Mansoura | 45 EGP | 8 EGP |
| Assiut | 50 EGP | 10 EGP |
| Other | 50 EGP | 10 EGP |

**Example Calculation**:
- Destination: Cairo
- Weight: 3 kg
- Rate: 30 + (3 × 5) = 45 EGP

## API Client Usage

The module includes a full API client in `Helper/Data.php`:

```php
// Get cities
$cities = $this->bostaHelper->getCities();

// Get city zones
$zones = $this->bostaHelper->getCityZones('cityId');

// Create delivery
$deliveryData = [
    'type' => 10, // 10 = Send Package, 20 = Cash Collection
    'cod' => 150.00,
    'dropOffAddress' => [
        'city' => 'Cairo',
        'zone' => 'Nasr City',
        'district' => 'Street Name',
        'buildingNumber' => '123'
    ],
    'receiver' => [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '+201234567890',
        'email' => 'john@example.com'
    ]
];
$result = $this->bostaHelper->createDelivery($deliveryData);

// Track delivery
$tracking = $this->bostaHelper->trackDelivery('TRACKING_NUMBER');
```

## File Structure

```
Elsherif/Bosta/
├── etc/
│   ├── adminhtml/
│   │   └── system.xml          # Admin configuration
│   ├── config.xml              # Default configuration values
│   └── module.xml              # Module declaration
├── Helper/
│   └── Data.php                # API Client and helper methods
├── Model/
│   ├── Carrier/
│   │   └── Customshipping.php  # Shipping carrier implementation
│   └── Config/
│       └── Source/
│           └── CalculationMethod.php  # Calculation method options
└── registration.php            # Module registration
```

## Customization

### Adjust Rate Matrix

Edit `Helper/Data.php` → `calculateShippingRate()` method:

```php
$rateMatrix = [
    'Cairo' => [
        'base' => 30.00,
        'per_kg' => 5.00
    ],
    // Add more cities...
];
```

### Add New Cities

Edit `Helper/Data.php` → `normalizeCityName()` method:

```php
$cityMap = [
    'cairo' => 'Cairo',
    'your_city' => 'YourCity',
    // Add more city mappings...
];
```

## Testing

1. Set API Mode to Staging (No)
2. Use fake API key: `fake-api-key-for-testing`
3. Enable Debug Mode (Yes)
4. Check logs at: `var/log/system.log`

## Troubleshooting

### Shipping method not showing

1. Check if module is enabled: `php bin/magento module:status`
2. Verify configuration: **Stores → Configuration → Sales → Shipping Methods**
3. Clear cache: `php bin/magento cache:flush`
4. Check debug logs if enabled

### API errors

1. Enable Debug Mode in configuration
2. Check `var/log/system.log` for detailed API requests/responses
3. Verify API key is correct
4. Check API mode (Staging vs Production)

## Requirements

- Magento 2.4.x or higher
- PHP 8.1 or higher
- Bosta Business account
- Valid Bosta API key

## Support

- Bosta Documentation: https://docs.bosta.co
- Bosta Dashboard: https://business.bosta.co

## License

Proprietary - All rights reserved

## Version

1.0.0
