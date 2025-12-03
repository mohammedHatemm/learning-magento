# Bosta Shipping Integration for Magento 2

Complete Bosta shipping and logistics integration module for Magento 2 with real-time pricing, automated delivery creation, webhook tracking, and comprehensive admin management.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Integration](#api-integration)
- [Shipping Price Calculation](#shipping-price-calculation)
- [Delivery Management](#delivery-management)
- [Pickup Management](#pickup-management)
- [Webhook Integration](#webhook-integration)
- [Admin Features](#admin-features)
- [Frontend Features](#frontend-features)
- [Database Schema](#database-schema)
- [File Structure](#file-structure)
- [Customization](#customization)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

---

## Features

### Core Features

✅ **Real-time Shipping Price Calculation** - Uses Bosta's Pricing Calculator API
✅ **Automated Delivery Creation** - Auto-creates deliveries when orders are placed
✅ **Multiple Calculation Methods** - Fixed, By Weight, By City, By City & Weight
✅ **Webhook Integration** - Real-time status updates from Bosta
✅ **Pickup Management** - Schedule and manage courier pickups
✅ **Order Tracking** - Complete tracking timeline in admin panel
✅ **City/Zone Autocomplete** - Dynamic city and zone selection during checkout
✅ **COD Support** - Cash on Delivery integration
✅ **Admin Dashboard** - View all deliveries and tracking events
✅ **Free Shipping Threshold** - Configurable free shipping rules
✅ **Handling Fees & VAT** - Add handling fees and VAT to shipping costs
✅ **Debug Mode** - Detailed API logging for troubleshooting
✅ **Staging & Production** - Support for both API environments
✅ **Caching** - API response caching for better performance

---

## Architecture

The module is built with a clean, modular architecture:

```
┌─────────────────────────────────────────────────────────┐
│                  MAGENTO FRONTEND                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Checkout   │  │ City Select  │  │   Tracking   │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
└─────────┼──────────────────┼──────────────────┼─────────┘
          │                  │                  │
┌─────────┼──────────────────┼──────────────────┼─────────┐
│         ▼                  ▼                  ▼         │
│  ┌─────────────────────────────────────────────────┐   │
│  │         Carrier: Customshipping.php             │   │
│  │  - collectRates()                               │   │
│  │  - calculateShippingCost()                      │   │
│  └──────────────────┬──────────────────────────────┘   │
│                     │                                   │
│  ┌──────────────────▼──────────────────────────────┐   │
│  │         Helper: Data.php (API Client)           │   │
│  │  - getDeliveryPrice()                           │   │
│  │  - createDelivery()                             │   │
│  │  - getCities(), getZones()                      │   │
│  │  - createPickup(), trackDelivery()              │   │
│  └──────────────────┬──────────────────────────────┘   │
│                     │                                   │
│         ┌───────────┴───────────┐                       │
│         ▼                       ▼                       │
│  ┌─────────────┐         ┌─────────────┐               │
│  │  Observers  │         │   Models    │               │
│  │  - Auto     │         │  - Delivery │               │
│  │    Create   │         │  - Pickup   │               │
│  │    Delivery │         │  - Tracking │               │
│  └─────────────┘         └─────────────┘               │
│                                                          │
└─────────────────┬────────────────────────────────────────┘
                  │
         ┌────────▼────────┐
         │   BOSTA API     │
         │  app.bosta.co   │
         └─────────────────┘
```

### Data Flow

1. **Checkout**: Customer enters shipping address with city
2. **Price Calculation**: Module calls Bosta Pricing Calculator API
3. **Order Placement**: Customer places order
4. **Auto-Delivery**: Observer creates Bosta delivery via API
5. **Webhook**: Bosta sends status updates to webhook endpoint
6. **Tracking**: Status updates saved to database and displayed in admin

---

## Installation

### Step 1: Copy Module

```bash
# Copy module to Magento app/code directory
cp -r Elsherif /path/to/magento/app/code/
```

### Step 2: Enable Module

```bash
cd /path/to/magento

# Enable module
php bin/magento module:enable Elsherif_Bosta

# Run setup upgrade (creates database tables)
php bin/magento setup:upgrade

# Compile dependency injection
php bin/magento setup:di:compile

# Deploy static content (production only)
php bin/magento setup:static-content:deploy -f

# Clear cache
php bin/magento cache:flush
```

### Step 3: Verify Installation

```bash
# Check module status
php bin/magento module:status Elsherif_Bosta

# Expected output: "Module is enabled"
```

---

## Configuration

### Admin Configuration Path

**Stores → Configuration → Sales → Shipping Methods → Bosta Shipping**

### Basic Settings

| Setting | Description | Example |
|---------|-------------|---------|
| **Enabled** | Enable/disable Bosta shipping | Yes |
| **Title** | Display name in checkout | "Bosta Shipping" |
| **Method Name** | Shipping method name | "Express Delivery" |
| **Sort Order** | Display order in shipping methods | 10 |

### API Configuration

| Setting | Description | How to Get |
|---------|-------------|------------|
| **API Key** | Bosta API authentication key | Dashboard → Settings → API Integration |
| **API Mode** | Production or Staging | Yes = Production, No = Staging |
| **Pickup Location ID** | Default pickup location | Dashboard → Pickup Locations |
| **Debug Mode** | Enable API logging | Yes (for testing) |

**API URLs:**
- Production: `https://app.bosta.co`
- Staging: `https://stg-app.bosta.co`

### Rate Calculation Settings

#### Calculation Method

Choose how shipping costs are calculated:

1. **Fixed Rate** - Single flat rate for all orders
   - Configure: Set "Fixed Shipping Cost"

2. **By Weight** - Base rate + per-kilogram charge
   - Formula: `Base Rate + (Weight × Per KG Rate)`
   - Hardcoded in module: Base 25 EGP + 5 EGP/kg

3. **By City** - Different rates per city
   - Uses Bosta API to get city-specific pricing

4. **By City and Weight** ⭐ **RECOMMENDED**
   - Most accurate method
   - Uses real-time Bosta Pricing Calculator API
   - Considers destination city sector and package weight

#### Additional Settings

| Setting | Description | Example |
|---------|-------------|---------|
| **Fixed Shipping Cost** | Used for "Fixed Rate" method | 30.00 EGP |
| **Free Shipping Threshold** | Free shipping above this order value | 500.00 EGP |
| **Handling Fee** | Additional fee added to all shipments | 5.00 EGP |
| **Include VAT** | Add VAT to shipping cost | Yes |
| **VAT Percentage** | VAT rate (if enabled) | 14.0% |

---

## API Integration

The module integrates with multiple Bosta API endpoints:

### 1. Pricing Calculator API

**Endpoint:** `GET /api/v2/pricing/calculator`

**Purpose:** Calculate real-time shipping costs based on city and service type

**Parameters:**
- `type` - Service type: `SEND`, `CASH_COLLECTION`, `RTO`
- `cod` - Cash on delivery amount
- `cityId` - Bosta city ID
- `pickupSectorId` - Pickup location sector (1-7)

**Implementation:** `Helper/Data.php:262-386`

```php
$priceResult = $this->bostaHelper->getDeliveryPrice(
    $cityId,        // '6044b12f463cb700137fc9f9'
    $citySector,    // 1 (Cairo), 2 (Alexandria), etc.
    'CASH_COLLECTION',
    0,              // COD amount (0 for rate estimation)
    $storeId
);
```

**Response:**
```json
{
  "success": true,
  "data": {
    "prices": [
      {
        "dropoffSectorId": 1,
        "dropoffSectorName": "Cairo",
        "tierServiceTypes": [
          {
            "typeName": "CASH_COLLECTION",
            "tierSizes": [
              {
                "sizeName": "Normal",
                "cost": 45.5
              }
            ]
          }
        ]
      }
    ]
  }
}
```

### 2. Cities API

**Endpoint:** `GET /api/v2/cities`

**Purpose:** Get list of all supported cities with IDs and sectors

**Implementation:** `Helper/Data.php:192-219`

**Response:**
```json
{
  "success": true,
  "data": {
    "list": [
      {
        "_id": "6044b12f463cb700137fc9f9",
        "name": "Cairo",
        "nameAr": "القاهرة",
        "sector": 1
      }
    ]
  }
}
```

**Caching:** 1 hour

### 3. City Zones API

**Endpoint:** `GET /api/v2/cities/{cityId}/zones`

**Purpose:** Get zones/districts within a specific city

**Implementation:** `Helper/Data.php:224-227`

### 4. Create Delivery API

**Endpoint:** `POST /api/v2/deliveries`

**Purpose:** Create a new delivery request

**Implementation:** `Helper/Data.php:232-235`

**Request Body:**
```json
{
  "type": 20,
  "cod": 150.00,
  "dropOffAddress": {
    "city": "Cairo",
    "zone": "Nasr City",
    "district": "Street Name",
    "buildingNumber": "123",
    "floor": "2",
    "apartment": "5",
    "firstLine": "123 Street Name",
    "secondLine": "Nasr City"
  },
  "receiver": {
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+201234567890",
    "email": "john@example.com"
  },
  "businessReference": "100000001",
  "notes": "Order #100000001",
  "allowToOpenPackage": false,
  "pickupLocationId": "6044b12f463cb700137fc9f9"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "_id": "delivery_id_here",
    "trackingNumber": "BOSTA123456",
    "state": "PENDING"
  }
}
```

### 5. Track Delivery API

**Endpoint:** `GET /api/v2/deliveries/{trackingNumber}`

**Purpose:** Get delivery status and tracking history

**Implementation:** `Helper/Data.php:240-243`

### 6. Pickup Locations API

**Endpoint:** `GET /api/v2/pickups/business-locations`

**Purpose:** Get your business pickup locations

**Implementation:** `Helper/Data.php:248-251`

### 7. Create Pickup API

**Endpoint:** `POST /api/v2/pickups`

**Purpose:** Schedule a pickup request

**Implementation:** `Helper/Data.php:256-259`

**Request Body:**
```json
{
  "businessLocationId": "6044b12f463cb700137fc9f9",
  "scheduledDate": "2025-12-10",
  "scheduledTimeSlot": "10:00 to 13:00",
  "contactPerson": {
    "name": "Contact Name",
    "phone": "01001001000",
    "email": "contact@example.com"
  },
  "notes": "Please call before arrival"
}
```

---

## Shipping Price Calculation

### How It Works

The module uses Bosta's real-time Pricing Calculator API for the most accurate shipping costs.

#### Flow Diagram

```
Customer enters city in checkout
         ↓
Module calls calculateShippingRate('Cairo', 2.5kg)
         ↓
Helper gets city info from cache or API
  - City ID: '6044b12f463cb700137fc9f9'
  - Sector: 1 (Cairo)
         ↓
Helper calls getDeliveryPrice()
         ↓
API Request: GET /api/v2/pricing/calculator?
  type=CASH_COLLECTION&
  cod=0&
  cityId=6044b12f463cb700137fc9f9&
  pickupSectorId=1
         ↓
Bosta API returns pricing for all 7 sectors
         ↓
Module extracts price for sector 1 (Cairo)
  → Price: 45.5 EGP
         ↓
Price cached for 15 minutes
         ↓
Shipping cost displayed: 45.5 EGP
```

#### Implementation Details

**File:** `Helper/Data.php:392-447`

```php
public function calculateShippingRate(
    string $city,
    float $weight,
    ?int $storeId = null
): float
```

**Process:**

1. **Get City Information**
   - Looks up city ID and sector from Bosta cities API
   - Uses caching to reduce API calls

2. **Call Pricing Calculator**
   - Requests price for specific city sector
   - Uses `CASH_COLLECTION` type (most common in Egypt)

3. **Extract Price**
   - Parses API response
   - Finds price for correct sector (not first one!)
   - Gets "Normal" package size cost

4. **Cache Result**
   - Caches price for 15 minutes
   - Reduces API load

5. **Return Price**
   - Returns price in EGP
   - Returns 0.0 if city not found or API fails

#### Service Types & Pricing

| Service Type | Code | Description | Typical Use |
|--------------|------|-------------|-------------|
| SEND | `SEND` | Regular delivery | Simple shipping |
| Cash Collection | `CASH_COLLECTION` | COD delivery | Most common in Egypt |
| Return to Origin | `RTO` | Returns | Customer returns |

#### Package Sizes

| Size | Weight Range | Typical Items |
|------|--------------|---------------|
| Normal | 0-5 kg | Most products |
| Large | 5-10 kg | Heavier items |
| Extra Large | 10+ kg | Bulky products |

#### Additional Fees

After getting base price from API, module can add:

1. **Handling Fee** (if configured)
2. **VAT** (if configured, typically 14% in Egypt)

**Formula:**
```
Final Price = Base Price + Handling Fee + (VAT % of total)
```

**Example:**
- Base Price: 40.00 EGP
- Handling Fee: 5.00 EGP
- Subtotal: 45.00 EGP
- VAT (14%): 6.30 EGP
- **Final: 51.30 EGP**

---

## Delivery Management

### Automatic Delivery Creation

When an order is placed with Bosta shipping, the module automatically creates a delivery via Bosta API.

**Trigger:** `sales_order_save_commit_after` event

**Observer:** `Observer/SaveDeliveryToDatabase.php`

**Process:**

1. **Order Validation**
   - Checks if order uses Bosta shipping (`customshipping`)
   - Verifies module is enabled
   - Validates shipping address exists

2. **Prepare Delivery Data**
   ```php
   [
       'type' => 20,  // 10 = Package, 20 = COD
       'cod' => $order->getGrandTotal(),
       'dropOffAddress' => [...],
       'receiver' => [...],
       'businessReference' => $order->getIncrementId(),
       'pickupLocationId' => $pickupLocationId
   ]
   ```

3. **Create Delivery**
   - Calls `POST /api/v2/deliveries`
   - Receives tracking number

4. **Save to Database**
   - Saves delivery to `bosta_delivery` table
   - Links to Magento order
   - Stores tracking number

5. **Update Order**
   - Adds tracking number to order
   - Adds comment to order history

### Delivery Types

The module automatically detects payment method:

| Payment Method | Delivery Type | COD Amount |
|----------------|---------------|------------|
| Cash on Delivery | 20 (COD) | Order Grand Total |
| Other (Credit Card, etc.) | 10 (Package) | 0.00 |

### Manual Delivery Creation

Admins can manually create/refresh deliveries from the order view:

**Location:** Sales → Orders → View Order → Bosta Delivery Tab

**Actions:**
- **Create Delivery** - Create delivery if not exists
- **Refresh Status** - Update delivery status from Bosta API

---

## Pickup Management

Schedule courier pickups for multiple deliveries.

### Pickup Workflow

```
1. Admin schedules pickup
   ↓
2. API: POST /api/v2/pickups
   ↓
3. Bosta assigns courier
   ↓
4. Courier arrives at scheduled time
   ↓
5. Collects all pending deliveries
```

### Pickup Data

Stored in `bosta_pickup` table with fields:

- Pickup ID (Bosta)
- Business Location ID
- Scheduled Date
- Time Slot (e.g., "10:00 to 13:00")
- Status
- Contact Person
- Notes

### Pickup-Delivery Relation

Many-to-many relationship via `bosta_pickup_delivery` table:
- One pickup can collect multiple deliveries
- One delivery can be in one pickup

---

## Webhook Integration

Real-time status updates from Bosta.

### Webhook Endpoint

**URL:** `https://yourdomain.com/bosta/webhook/update`

**Method:** POST

**Authentication:** None (CSRF disabled for this endpoint)

### Configure in Bosta Dashboard

1. Go to Bosta Dashboard → Settings → Webhooks
2. Add webhook URL: `https://yourdomain.com/bosta/webhook/update`
3. Select events to track:
   - Delivery status changes
   - Pickup confirmations
   - Delivery attempts

### Webhook Payload Example

```json
{
  "trackingNumber": "BOSTA123456",
  "state": "DELIVERED",
  "reason": "Package delivered successfully",
  "hub": "Cairo Hub",
  "timestamp": "2025-12-02T10:30:00Z"
}
```

### Webhook Handler

**File:** `Controller/Webhook/Update.php`

**Process:**

1. **Receive Webhook** - Parse JSON payload
2. **Find Delivery** - Lookup by tracking number
3. **Update Status** - Update delivery status
4. **Save Event** - Store tracking event with timestamp
5. **Update Order** - Update Magento order status if needed
6. **Send Response** - Return 200 OK

### Status Mapping

| Bosta Status | Magento Action |
|--------------|----------------|
| `DELIVERED` | Set order to "Complete" |
| `CANCELLED` | Add comment to order |
| `TERMINATED` | Add comment to order |
| Other | Add comment with status |

### Tracking Events

All webhook updates are logged to `bosta_tracking_event` table:

- Status
- Description
- Location (hub name)
- Event timestamp
- Created timestamp

This provides a complete audit trail of delivery progress.

---

## Admin Features

### 1. Order View - Bosta Tab

**Location:** Sales → Orders → View Order → Bosta Delivery Tab

Displays:
- Delivery status
- Tracking number
- Tracking URL (links to Bosta tracking page)
- Timeline of all tracking events
- Create/Refresh delivery buttons

**File:** `Block/Adminhtml/Order/View/Tab/Bosta.php`

### 2. Deliveries Grid

**Location:** Sales → Bosta Deliveries

View all Bosta deliveries:
- Order ID
- Tracking number
- Status
- COD amount
- Created date
- Actions (View, Track)

**File:** `Controller/Adminhtml/Delivery/Index.php`

### 3. Pickups Grid

**Location:** Sales → Bosta Pickups

Manage pickup requests:
- Scheduled date
- Time slot
- Status
- Contact person
- Actions (View, Cancel)

**File:** `Controller/Adminhtml/Pickup/Index.php`

### 4. Configuration

**Location:** Stores → Configuration → Sales → Shipping Methods → Bosta Shipping

All module settings in one place.

---

## Frontend Features

### 1. City Autocomplete

Dynamic city selection during checkout with Bosta cities.

**Files:**
- `view/frontend/web/js/bosta-cities.js`
- `view/frontend/web/js/view/form/element/city-mixin.js`

**Features:**
- Auto-loads Bosta cities
- Validates city against Bosta API
- Shows city in English and Arabic

### 2. Zone Selection

After selecting city, shows zones/districts.

**Endpoint:** `/bosta/api/zones?city=Cairo`

**File:** `Controller/Api/Zones.php`

### 3. Checkout Integration

**File:** `view/frontend/templates/checkout/bosta-init.phtml`

Initializes Bosta-specific checkout behavior:
- City validation
- Zone loading
- Shipping rate refresh

### 4. Customer Address Form

**File:** `view/frontend/templates/customer/address-init.phtml`

Adds Bosta city validation to customer address forms.

---

## Database Schema

### bosta_delivery

Stores delivery information.

| Column | Type | Description |
|--------|------|-------------|
| entity_id | INT | Primary key |
| order_id | INT | Magento order ID (FK) |
| tracking_number | VARCHAR(100) | Bosta tracking number (UNIQUE) |
| bosta_delivery_id | VARCHAR(100) | Bosta delivery ID |
| delivery_type | SMALLINT | 10=Package, 20=COD |
| status | VARCHAR(50) | Current status |
| cod_amount | DECIMAL(12,4) | COD amount |
| shipping_cost | DECIMAL(12,4) | Shipping cost |
| delivery_data | TEXT | Full API response (JSON) |
| awb_url | VARCHAR(255) | AWB PDF URL |
| created_at | TIMESTAMP | Created timestamp |
| updated_at | TIMESTAMP | Updated timestamp |

**Indexes:**
- Primary: entity_id
- Foreign Key: order_id → sales_order.entity_id (CASCADE)
- Unique: tracking_number
- Index: order_id, status

### bosta_tracking_event

Stores tracking event history.

| Column | Type | Description |
|--------|------|-------------|
| entity_id | INT | Primary key |
| delivery_id | INT | Delivery ID (FK) |
| status | VARCHAR(50) | Event status |
| description | TEXT | Event description |
| location | VARCHAR(255) | Hub/location name |
| event_timestamp | TIMESTAMP | When event occurred |
| created_at | TIMESTAMP | When record created |

**Indexes:**
- Primary: entity_id
- Foreign Key: delivery_id → bosta_delivery.entity_id (CASCADE)
- Index: delivery_id, status

### bosta_pickup

Stores pickup requests.

| Column | Type | Description |
|--------|------|-------------|
| entity_id | INT | Primary key |
| bosta_pickup_id | VARCHAR(100) | Bosta pickup ID |
| business_location_id | VARCHAR(100) | Business location |
| scheduled_date | DATE | Pickup date |
| scheduled_time_slot | VARCHAR(50) | Time slot |
| status | VARCHAR(50) | Pickup status |
| contact_person_name | VARCHAR(255) | Contact name |
| contact_person_phone | VARCHAR(50) | Contact phone |
| notes | TEXT | Pickup notes |
| created_at | TIMESTAMP | Created timestamp |
| updated_at | TIMESTAMP | Updated timestamp |

**Indexes:**
- Primary: entity_id
- Index: status, scheduled_date

### bosta_pickup_delivery

Links pickups to deliveries (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| pickup_id | INT | Pickup ID (FK) |
| delivery_id | INT | Delivery ID (FK) |

**Indexes:**
- Primary: (pickup_id, delivery_id)
- Foreign Keys: CASCADE on delete
- Index: pickup_id, delivery_id

---

## File Structure

```
Elsherif/Bosta/
├── Block/
│   ├── Adminhtml/
│   │   └── Order/
│   │       └── View/
│   │           └── Tab/
│   │               └── Bosta.php              # Order view tab
│   └── Tracking/
│       └── Info.php                           # Tracking info block
│
├── Controller/
│   ├── Adminhtml/
│   │   ├── Delivery/
│   │   │   ├── Index.php                      # Deliveries grid
│   │   │   ├── Create.php                     # Create delivery
│   │   │   └── Refresh.php                    # Refresh status
│   │   └── Pickup/
│   │       └── Index.php                      # Pickups grid
│   ├── Api/
│   │   ├── Cities.php                         # Cities API endpoint
│   │   └── Zones.php                          # Zones API endpoint
│   ├── Tracking/
│   │   └── Index.php                          # Frontend tracking page
│   └── Webhook/
│       └── Update.php                         # Webhook handler
│
├── etc/
│   ├── adminhtml/
│   │   ├── routes.xml                         # Admin routes
│   │   └── system.xml                         # Configuration fields
│   ├── frontend/
│   │   └── routes.xml                         # Frontend routes
│   ├── acl.xml                                # Admin permissions
│   ├── config.xml                             # Default config values
│   ├── db_schema.xml                          # Database schema
│   ├── di.xml                                 # Dependency injection
│   ├── events.xml                             # Event observers
│   └── module.xml                             # Module declaration
│
├── Helper/
│   └── Data.php                               # API client & helpers
│
├── Model/
│   ├── Carrier/
│   │   └── Customshipping.php                 # Shipping carrier
│   ├── Config/
│   │   └── Source/
│   │       ├── CalculationMethod.php          # Calculation methods
│   │       └── DeliveryStatus.php             # Status options
│   ├── ResourceModel/
│   │   ├── Delivery.php                       # Delivery resource
│   │   ├── Delivery/
│   │   │   └── Collection.php                 # Delivery collection
│   │   ├── Pickup.php                         # Pickup resource
│   │   ├── Pickup/
│   │   │   └── Collection.php                 # Pickup collection
│   │   ├── TrackingEvent.php                  # Event resource
│   │   └── TrackingEvent/
│   │       └── Collection.php                 # Event collection
│   ├── Delivery.php                           # Delivery model
│   ├── Pickup.php                             # Pickup model
│   └── TrackingEvent.php                      # Tracking event model
│
├── Observer/
│   ├── CreateBostaDelivery.php                # Auto-create delivery
│   └── SaveDeliveryToDatabase.php             # Save delivery to DB
│
├── Plugin/
│   └── CsrfValidatorSkip.php                  # Skip CSRF for webhook
│
├── view/
│   ├── adminhtml/
│   │   ├── layout/
│   │   │   └── sales_order_view.xml           # Add tab to order view
│   │   └── templates/
│   │       └── order/
│   │           └── view/
│   │               └── tab/
│   │                   └── bosta.phtml        # Tab template
│   └── frontend/
│       ├── layout/
│       │   ├── checkout_index_index.xml       # Checkout layout
│       │   └── customer_address_form.xml      # Address form layout
│       ├── templates/
│       │   ├── checkout/
│       │   │   └── bosta-init.phtml           # Checkout init
│       │   └── customer/
│       │       └── address-init.phtml         # Address init
│       ├── web/
│       │   └── js/
│       │       ├── bosta-cities.js            # Cities data
│       │       ├── checkout-bosta-init.js     # Checkout JS
│       │       └── view/
│       │           └── form/
│       │               └── element/
│       │                   └── city-mixin.js  # City field mixin
│       └── requirejs-config.js                # RequireJS config
│
├── registration.php                           # Module registration
└── README.md                                  # This file
```

---

## Customization

### 1. Add Custom Calculation Method

Edit `Model/Config/Source/CalculationMethod.php`:

```php
const METHOD_CUSTOM = 'custom';

public function toOptionArray()
{
    return [
        // ... existing methods
        ['value' => self::METHOD_CUSTOM, 'label' => __('Custom Method')]
    ];
}
```

Then add method in `Model/Carrier/Customshipping.php`:

```php
case CalculationMethod::METHOD_CUSTOM:
    return $this->calculateCustomMethod($request);
```

### 2. Customize Rate Calculation

Override `Helper/Data.php::calculateShippingRate()`:

```php
// Add custom logic before calling API
if ($customCondition) {
    return $customPrice;
}

// Or modify API price
$price = $priceResult['price'];
$price = $price * 1.1; // Add 10% markup
return $price;
```

### 3. Add Custom Delivery Fields

1. **Add database column:**

Edit `etc/db_schema.xml`:
```xml
<column xsi:type="varchar" name="custom_field" nullable="true" length="255"/>
```

2. **Run upgrade:**
```bash
php bin/magento setup:upgrade
```

3. **Update model:**

Add getter/setter to `Model/Delivery.php`

### 4. Customize Webhook Behavior

Edit `Controller/Webhook/Update.php::updateOrderStatus()`:

```php
switch ($status) {
    case 'CUSTOM_STATUS':
        // Your custom logic
        $order->setCustomStatus('custom');
        break;
}
```

### 5. Add City Name Mappings

Edit `Helper/Data.php::normalizeCityName()`:

```php
$cityMap = [
    'alex' => 'Alexandria',
    'giza' => 'Giza',
    'your_city_alias' => 'Official City Name'
];
```

---

## Testing

### Test Mode Setup

1. **Enable Staging Mode:**
   - Stores → Configuration → Sales → Shipping Methods → Bosta Shipping
   - Set "API Mode" to "No" (Staging)

2. **Use Test API Key:**
   - Get test key from Bosta support
   - Or use: `test-api-key-12345`

3. **Enable Debug Mode:**
   - Set "Debug Mode" to "Yes"
   - Check logs at: `var/log/system.log`

### Testing Checklist

#### Shipping Rate Calculation

- [ ] Create test product with weight
- [ ] Add to cart
- [ ] Go to checkout
- [ ] Enter shipping address with Bosta city
- [ ] Verify shipping cost appears
- [ ] Check debug log for API call

#### Delivery Creation

- [ ] Place test order with Bosta shipping
- [ ] Check `bosta_delivery` table for new record
- [ ] Verify tracking number assigned
- [ ] Check order comment for tracking number
- [ ] View order in admin → Bosta Delivery tab

#### Webhook Testing

- [ ] Use Postman to send test webhook
- [ ] URL: `https://yourdomain.com/bosta/webhook/update`
- [ ] Body:
  ```json
  {
    "trackingNumber": "TEST123",
    "state": "DELIVERED",
    "timestamp": "2025-12-02T10:30:00Z"
  }
  ```
- [ ] Check `bosta_tracking_event` table
- [ ] Verify order status updated

#### API Endpoints

Test all endpoints with Postman:

```bash
# Get cities
GET https://app.bosta.co/api/v2/cities
Authorization: YOUR_API_KEY

# Get pricing
GET https://app.bosta.co/api/v2/pricing/calculator?type=CASH_COLLECTION&cod=0&cityId=CITY_ID&pickupSectorId=1
Authorization: YOUR_API_KEY

# Create delivery
POST https://app.bosta.co/api/v2/deliveries
Authorization: YOUR_API_KEY
Body: { delivery data }
```

---

## Troubleshooting

### Shipping Method Not Showing

**Possible Causes:**

1. **Module Not Enabled**
   ```bash
   php bin/magento module:status Elsherif_Bosta
   # Should show: "Module is enabled"
   ```

2. **Configuration Disabled**
   - Stores → Configuration → Sales → Shipping Methods → Bosta Shipping
   - Check "Enabled" is set to "Yes"

3. **API Key Invalid**
   - Enable Debug Mode
   - Check `var/log/system.log` for API errors
   - Verify API key in configuration

4. **City Not Found**
   - City must be valid Bosta city
   - Check cities at: `/bosta/api/cities`
   - Add city mapping if needed

5. **Cache Issues**
   ```bash
   php bin/magento cache:flush
   ```

### Shipping Price Shows 0.00

**Causes:**

1. **City Not in Bosta System**
   - API returns empty for unknown cities
   - Check debug log for API response

2. **API Call Failed**
   - Check network connectivity
   - Verify API endpoint reachable
   - Enable debug mode to see errors

3. **Calculation Method Misconfigured**
   - If using "Fixed Rate", set "Fixed Shipping Cost"
   - If using API method, verify API key

### Delivery Not Auto-Created

**Causes:**

1. **Event Not Triggered**
   - Check events.xml configuration
   - Verify observer class exists

2. **Shipping Method Mismatch**
   - Order must use shipping code: `customshipping_customshipping`
   - Check order shipping method

3. **API Error**
   - Enable debug mode
   - Check `var/log/system.log`
   - Look for "Failed to create Bosta delivery"

4. **Missing Required Fields**
   - Shipping address must have: city, street, phone
   - Customer email required

### Webhook Not Working

**Causes:**

1. **URL Not Configured in Bosta**
   - Add webhook URL in Bosta Dashboard
   - URL: `https://yourdomain.com/bosta/webhook/update`

2. **CSRF Token Issue**
   - Module should disable CSRF for webhook
   - Check `Plugin/CsrfValidatorSkip.php` is registered

3. **Delivery Not Found**
   - Webhook uses tracking number to find delivery
   - Delivery must exist in `bosta_delivery` table

4. **JSON Parse Error**
   - Check webhook payload format
   - Enable debug to see received data

### Debug Mode

Enable detailed logging:

**Configuration:**
- Stores → Configuration → Sales → Shipping Methods → Bosta Shipping
- Set "Debug Mode" to "Yes"

**Log Location:**
```bash
tail -f var/log/system.log | grep Bosta
```

**What Gets Logged:**
- All API requests (URL, method, data)
- All API responses (status, body)
- Delivery creation attempts
- Webhook payloads
- Price calculations

---

## Requirements

### System Requirements

- **Magento Version:** 2.4.x or higher
- **PHP Version:** 8.1 or higher
- **Database:** MySQL 8.0+ or MariaDB 10.4+
- **Extensions:**
  - `php-curl`
  - `php-json`
  - `php-mbstring`

### Bosta Requirements

- Active Bosta Business account
- Valid API key (Production or Staging)
- Configured pickup location
- Webhook endpoint (for tracking updates)

---

## Support & Resources

### Bosta Resources

- **Dashboard:** https://business.bosta.co
- **Documentation:** https://docs.bosta.co
- **API Docs:** https://app.bosta.co/api/v2 (with API key)
- **Support:** support@bosta.co

### Module Support

- **Version:** 1.0.0
- **Author:** Elsherif
- **License:** Proprietary

### Useful Commands

```bash
# Check module status
php bin/magento module:status Elsherif_Bosta

# Flush cache
php bin/magento cache:flush

# Recompile (after code changes)
php bin/magento setup:di:compile

# Database upgrade (after schema changes)
php bin/magento setup:upgrade

# View logs
tail -f var/log/system.log
tail -f var/log/exception.log

# Clear generated files
rm -rf generated/* var/cache/* var/page_cache/* var/view_preprocessed/*
```

---

## Changelog

### Version 1.0.0 (2025-12-02)

**Features:**
- ✅ Real-time Bosta Pricing Calculator API integration
- ✅ Automated delivery creation on order placement
- ✅ Webhook support for real-time tracking updates
- ✅ Multiple shipping calculation methods
- ✅ Admin order view Bosta delivery tab
- ✅ Frontend city/zone autocomplete
- ✅ Pickup management
- ✅ Complete tracking event history
- ✅ COD support
- ✅ Free shipping threshold
- ✅ VAT and handling fee configuration
- ✅ API response caching
- ✅ Debug mode with detailed logging
- ✅ Staging and production environment support

---

## License

Proprietary - All rights reserved

© 2025 Elsherif. Unauthorized copying, modification, or distribution is prohibited.
