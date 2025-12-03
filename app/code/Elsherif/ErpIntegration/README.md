# Elsherif ERP Integration Module

This Magento 2 module integrates customer data synchronization with an external ERP system. When customers register or update their information in Magento, the data is automatically sent to the ERP system via API.

## Features

- ✅ Automatic customer sync on registration
- ✅ Automatic customer sync on profile updates
- ✅ Secure API token storage (encrypted)
- ✅ Configurable sync settings via admin panel
- ✅ Comprehensive logging for debugging
- ✅ Error handling and retry logic
- ✅ Support for customer addresses and custom attributes

## Installation

### Step 1: Enable the Module

```bash
cd /home/sherif/backup/task-intern/fetal/src

# Enable the module
php bin/magento module:enable Elsherif_ErpIntegration

# Run setup upgrade
php bin/magento setup:upgrade

# Compile dependency injection
php bin/magento setup:di:compile

# Deploy static content (if needed)
php bin/magento setup:static-content:deploy -f

# Clear cache
php bin/magento cache:clean
php bin/magento cache:flush
```

### Step 2: Configure the Module

1. Log in to Magento Admin Panel
2. Navigate to **Stores → Configuration → Elsherif Extensions → ERP Integration**
3. Configure the following settings:

#### General Settings
- **Enable ERP Integration**: Set to "Yes" to activate the integration
- **ERP API URL**: Enter your ERP API endpoint
  - Example: `https://testerp.a2za1.com/api/resource/Customer`
- **API Token**: Enter your ERP authentication token
  - Example: `2fc36d992eed023cd8665f9feb6561S`
- **API Timeout**: Set request timeout in seconds (default: 30)

#### Sync Settings
- **Sync on Customer Registration**: Enable to sync when new customers register
- **Sync on Customer Update**: Enable to sync when customers update their profile
- **Enable Logging**: Enable to log all sync activities to `var/log/erp_integration.log`

4. Click **Save Config**
5. Clear cache: `php bin/magento cache:clean`

## How It Works

### Customer Registration Flow

```
Customer fills registration form
         ↓
Magento creates customer account
         ↓
Event: customer_register_success
         ↓
Observer: CustomerRegisterSuccess
         ↓
CustomerDataFormatter formats data
         ↓
ErpClient sends POST request to ERP
         ↓
ERP receives customer data
```

### Customer Update Flow

```
Customer updates profile
         ↓
Magento saves customer data
         ↓
Event: customer_save_after_data_object
         ↓
Observer: CustomerSaveAfter
         ↓
CustomerDataFormatter formats data
         ↓
ErpClient sends PUT request to ERP
         ↓
ERP updates customer data
```

## Customer Data Format

The module sends the following data to the ERP:

```json
{
    "customer_name": "John Doe",
    "customer_type": "Individual",
    "customer_group": "General",
    "territory": "All Territories",
    "email_id": "john@example.com",
    "mobile_no": "+1234567890",
    "website": "1",
    "custom_magento_customer_id": "123",
    "customer_primary_address": "123 Main St, City, State, 12345, US",
    "city": "City",
    "country": "US",
    "custom_gender": "Male",
    "custom_dob": "1990-01-01",
    "custom_taxvat": "123456789"
}
```

## File Structure

```
Elsherif/ErpIntegration/
├── registration.php                      # Module registration
├── etc/
│   ├── module.xml                        # Module declaration
│   ├── config.xml                        # Default configuration
│   ├── acl.xml                           # Access control list
│   ├── events.xml                        # Event observer configuration
│   ├── di.xml                            # Dependency injection
│   └── adminhtml/
│       └── system.xml                    # Admin configuration panel
├── Model/
│   ├── ErpClient.php                     # ERP API client
│   └── CustomerDataFormatter.php        # Data formatter
├── Observer/
│   ├── CustomerRegisterSuccess.php      # Registration observer
│   └── CustomerSaveAfter.php            # Update observer
├── Logger/
│   ├── Logger.php                        # Custom logger
│   └── Handler.php                       # Log handler
└── README.md                             # This file
```

## API Endpoints

### Create Customer (POST)
**Endpoint**: `https://testerp.a2za1.com/api/resource/Customer`
**Method**: POST
**Headers**:
- `Content-Type: application/json`
- `Authorization: {your_api_token}`

**Request Body**: Customer data JSON (see format above)

### Update Customer (PUT)
**Endpoint**: `https://testerp.a2za1.com/api/resource/Customer/{customer_id}`
**Method**: PUT
**Headers**:
- `Content-Type: application/json`
- `Authorization: {your_api_token}`

**Request Body**: Updated customer data JSON

## Logging and Debugging

### View Logs

All ERP integration activities are logged to:
```
var/log/erp_integration.log
```

To view logs in real-time:
```bash
tail -f var/log/erp_integration.log
```

### Log Entries Include:
- Customer data being sent to ERP
- API response codes and bodies
- Success/failure messages
- Exception details

### Example Log Entry:
```
[2025-12-03 09:45:11] ErpIntegration.INFO: [ERP Integration] Sending customer data to ERP: {"customer_name":"John Doe","email_id":"john@example.com"...}
[2025-12-03 09:45:12] ErpIntegration.INFO: [ERP Integration] ERP Response Code: 200, Body: {"name":"CUST-00123","message":"success"}
[2025-12-03 09:45:12] ErpIntegration.INFO: [ERP Integration] Customer registered successfully in ERP. Customer ID: 123
```

## Troubleshooting

### Integration Not Working

1. **Check if module is enabled**:
   ```bash
   php bin/magento module:status Elsherif_ErpIntegration
   ```

2. **Check configuration**:
   - Verify "Enable ERP Integration" is set to "Yes"
   - Verify API URL and Token are correct
   - Check sync settings are enabled

3. **Check logs**:
   ```bash
   tail -f var/log/erp_integration.log
   tail -f var/log/system.log
   ```

4. **Test API connection manually**:
   ```bash
   curl -X POST https://testerp.a2za1.com/api/resource/Customer \
     -H "Content-Type: application/json" \
     -H "Authorization: 2fc36d992eed023cd8665f9feb6561S" \
     -d '{"customer_name":"Test Customer","email_id":"test@example.com"}'
   ```

### Common Issues

**Issue**: "ERP Integration is disabled"
- **Solution**: Enable the module in admin configuration

**Issue**: "API URL or Token not configured"
- **Solution**: Configure API credentials in admin panel

**Issue**: "Connection timeout"
- **Solution**: Increase timeout value in configuration or check network connectivity

**Issue**: "Authentication failed"
- **Solution**: Verify API token is correct and not expired

## Customization

### Modify Customer Data Format

Edit `Model/CustomerDataFormatter.php` → `formatCustomerData()` method to customize which fields are sent to ERP.

### Add Custom Attributes

In `CustomerDataFormatter.php`, add your custom attributes to the sync:

```php
if (in_array($code, ['gender', 'dob', 'taxvat', 'your_custom_attribute'])) {
    $data['custom_' . $code] = $attribute->getValue();
}
```

### Change ERP Customer ID Mapping

In `Observer/CustomerSaveAfter.php`, modify how ERP customer IDs are stored/retrieved:

```php
// Option 1: Use Magento customer ID
$erpCustomerId = $customerId;

// Option 2: Use custom attribute (recommended)
$erpCustomerId = $customerDataObject->getCustomAttribute('erp_customer_id')
    ? $customerDataObject->getCustomAttribute('erp_customer_id')->getValue()
    : $customerId;
```

## Testing

### Test Customer Registration

1. Go to your Magento storefront
2. Click "Create an Account"
3. Fill in the registration form
4. Submit the form
5. Check `var/log/erp_integration.log` for sync logs
6. Verify customer appears in ERP system

### Test Customer Update

1. Log in to customer account
2. Go to "Account Information"
3. Update customer details
4. Save changes
5. Check logs for sync confirmation
6. Verify updates appear in ERP system

## Security Considerations

- ✅ API token is encrypted in database
- ✅ SSL/TLS for API communication (configure CURLOPT_SSL_VERIFYPEER in production)
- ✅ No sensitive data in logs (when logging is disabled)
- ✅ Admin ACL for configuration access

## Support

For issues or questions:
- Check logs: `var/log/erp_integration.log`
- Review configuration settings
- Verify ERP API is accessible
- Test API credentials manually

## Version History

- **1.0.0** - Initial release
  - Customer registration sync
  - Customer update sync
  - Admin configuration panel
  - Comprehensive logging

## License

Copyright © Elsherif. All rights reserved.
