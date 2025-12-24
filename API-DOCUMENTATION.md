# WooCommerce Settings REST API Documentation

REST API endpoints for managing WooCommerce Coupon, Tax settings, and Tax Rates.

**Base URL:** `https://your-site.com/wp-json/woocommerce-ext/v1`

**Authentication:** All endpoints require `manage_woocommerce` capability.

**Version:** 1.3.0

---

## Table of Contents

1. [Coupons](#coupons)
   - [GET /coupons](#get-coupons)
   - [POST /coupons](#post-coupons)
2. [Taxes (Enable/Disable)](#taxes-enabledisable)
   - [GET /taxes](#get-taxes)
   - [POST /taxes](#post-taxes)
3. [Tax Options](#tax-options)
   - [GET /taxes-options](#get-taxes-options)
   - [POST /taxes-options](#post-taxes-options)
4. [Tax Rates](#tax-rates)
   - [GET /tax-rates](#get-tax-rates)
   - [GET /tax-rates/{id}](#get-tax-ratesid)
   - [POST /tax-rates](#post-tax-rates)
   - [PUT /tax-rates/{id}](#put-tax-ratesid)
   - [DELETE /tax-rates/{id}](#delete-tax-ratesid)
   - [POST /tax-rates/batch](#post-tax-ratesbatch)
   - [POST /tax-rates/import](#post-tax-ratesimport)
   - [GET /tax-rates/export](#get-tax-ratesexport)
   - [GET /tax-rates/classes](#get-tax-ratesclasses)
   - [DELETE /tax-rates/all](#delete-tax-ratesall)
5. [Error Responses](#error-responses)
6. [Code Examples](#code-examples)

---

## Coupons

Manage WooCommerce coupon functionality (enable/disable).

**WooCommerce Setting:** WooCommerce → Settings → General → Enable coupons

### GET /coupons

Retrieve current coupon status.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/coupons`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "enabled": true,
        "raw_value": "yes",
        "option_name": "woocommerce_enable_coupons"
    }
}
```

**cURL Example:**
```bash
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/coupons" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### POST /coupons

Enable or disable WooCommerce coupons.

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/coupons`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**

| Parameter | Type    | Required | Description                          |
|-----------|---------|----------|--------------------------------------|
| `enabled` | boolean | Yes      | `true` to enable, `false` to disable |

**Request Example:**
```json
{
    "enabled": true
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "enabled": true,
        "raw_value": "yes",
        "was_updated": true,
        "message": "Coupons have been enabled."
    }
}
```

**cURL Example:**
```bash
# Enable coupons
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/coupons" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"enabled": true}'

# Disable coupons
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/coupons" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"enabled": false}'
```

---

## Taxes (Enable/Disable)

Enable or disable WooCommerce tax calculations.

**WooCommerce Setting:** WooCommerce → Settings → General → Enable taxes

### GET /taxes

Retrieve current tax calculation status.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/taxes`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "enabled": true,
        "raw_value": "yes",
        "option_name": "woocommerce_calc_taxes"
    }
}
```

**cURL Example:**
```bash
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/taxes" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### POST /taxes

Enable or disable WooCommerce tax calculations.

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/taxes`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**

| Parameter | Type    | Required | Description                          |
|-----------|---------|----------|--------------------------------------|
| `enabled` | boolean | Yes      | `true` to enable, `false` to disable |

**Request Example:**
```json
{
    "enabled": true
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "enabled": true,
        "raw_value": "yes",
        "was_updated": true,
        "message": "Tax rates and calculations have been enabled."
    }
}
```

**cURL Example:**
```bash
# Enable taxes
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"enabled": true}'

# Disable taxes
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"enabled": false}'
```

---

## Tax Options

Manage detailed WooCommerce tax configuration options.

**WooCommerce Setting:** WooCommerce → Settings → Tax → Tax options

### GET /taxes-options

Retrieve all tax option settings.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/taxes-options`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "prices_include_tax": {
            "value": "no",
            "enabled": false,
            "option_name": "woocommerce_prices_include_tax",
            "description": "Prices entered with tax",
            "choices": {
                "yes": "Yes, I will enter prices inclusive of tax",
                "no": "No, I will enter prices exclusive of tax"
            }
        },
        "tax_based_on": {
            "value": "shipping",
            "option_name": "woocommerce_tax_based_on",
            "description": "Calculate tax based on",
            "choices": {
                "shipping": "Customer shipping address",
                "billing": "Customer billing address",
                "base": "Shop base address"
            }
        },
        "shipping_tax_class": {
            "value": "",
            "option_name": "woocommerce_shipping_tax_class",
            "description": "Shipping tax class",
            "choices": {
                "": "Shipping tax class based on cart items",
                "standard": "Standard",
                "reduced-rate": "Reduced rate",
                "zero-rate": "Zero rate"
            }
        },
        "tax_round_at_subtotal": {
            "value": "no",
            "enabled": false,
            "option_name": "woocommerce_tax_round_at_subtotal",
            "description": "Round tax at subtotal level, instead of rounding per line",
            "choices": {
                "yes": "Yes",
                "no": "No"
            }
        },
        "tax_display_shop": {
            "value": "excl",
            "option_name": "woocommerce_tax_display_shop",
            "description": "Display prices in the shop",
            "choices": {
                "incl": "Including tax",
                "excl": "Excluding tax"
            }
        },
        "tax_display_cart": {
            "value": "excl",
            "option_name": "woocommerce_tax_display_cart",
            "description": "Display prices during cart and checkout",
            "choices": {
                "incl": "Including tax",
                "excl": "Excluding tax"
            }
        },
        "tax_total_display": {
            "value": "itemized",
            "option_name": "woocommerce_tax_total_display",
            "description": "Display tax totals",
            "choices": {
                "single": "As a single total",
                "itemized": "Itemized"
            }
        },
        "price_display_suffix": {
            "value": "",
            "option_name": "woocommerce_price_display_suffix",
            "description": "Price display suffix",
            "help_text": "Define text to show after your product prices. This could be, for example, \"inc. Vat\" to explain your pricing. You can also have prices substituted here using one of the following: {price_including_tax}, {price_excluding_tax}."
        },
        "tax_classes": {
            "value": ["Reduced rate", "Zero rate"],
            "option_name": "woocommerce_tax_classes",
            "description": "Additional tax classes",
            "available_classes": {
                "": "Standard",
                "reduced-rate": "Reduced rate",
                "zero-rate": "Zero rate"
            }
        }
    },
    "meta": {
        "wc_version": "8.5.0",
        "tax_enabled": true,
        "store_address": {
            "country": "US",
            "state": "CA",
            "postcode": "90210",
            "city": "Beverly Hills"
        }
    }
}
```

**cURL Example:**
```bash
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### POST /taxes-options

Update tax option settings. All parameters are optional - only include the ones you want to update.

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/taxes-options`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**

| Parameter              | Type           | Required | Description                                                            |
|------------------------|----------------|----------|------------------------------------------------------------------------|
| `prices_include_tax`   | boolean/string | No       | `true`/`"yes"` for inclusive, `false`/`"no"` for exclusive             |
| `tax_based_on`         | string         | No       | `"shipping"`, `"billing"`, or `"base"`                                 |
| `shipping_tax_class`   | string         | No       | `""` (inherit), `"standard"`, `"reduced-rate"`, `"zero-rate"`, or custom |
| `tax_round_at_subtotal`| boolean/string | No       | `true`/`"yes"` to enable rounding at subtotal                          |
| `tax_display_shop`     | string         | No       | `"incl"` or `"excl"`                                                   |
| `tax_display_cart`     | string         | No       | `"incl"` or `"excl"`                                                   |
| `tax_total_display`    | string         | No       | `"single"` or `"itemized"`                                             |
| `price_display_suffix` | string         | No       | Text to display after prices (supports `{price_including_tax}`, `{price_excluding_tax}`) |
| `tax_classes`          | string/array   | No       | Newline-separated string or array of tax class names                   |

**Request Examples:**

**Update single option:**
```json
{
    "prices_include_tax": true
}
```

**Update multiple options:**
```json
{
    "prices_include_tax": false,
    "tax_based_on": "shipping",
    "shipping_tax_class": "",
    "tax_round_at_subtotal": false,
    "tax_display_shop": "excl",
    "tax_display_cart": "excl",
    "tax_total_display": "itemized",
    "price_display_suffix": "incl. VAT"
}
```

**Update tax classes (as array):**
```json
{
    "tax_classes": ["Reduced rate", "Zero rate", "Special rate"]
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "updated": {
            "prices_include_tax": "no",
            "tax_based_on": "shipping",
            "tax_display_shop": "excl",
            "tax_display_cart": "excl",
            "tax_total_display": "itemized"
        },
        "errors": {},
        "message": "Tax options have been updated."
    }
}
```

**cURL Example:**
```bash
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "prices_include_tax": false,
    "tax_based_on": "shipping",
    "tax_display_shop": "excl",
    "tax_display_cart": "excl",
    "tax_total_display": "itemized"
  }'
```

---

## Tax Options Reference

### prices_include_tax

| Value           | Description                        |
|-----------------|------------------------------------|
| `yes` / `true`  | Prices entered inclusive of tax    |
| `no` / `false`  | Prices entered exclusive of tax    |

### tax_based_on

| Value      | Description               |
|------------|---------------------------|
| `shipping` | Customer shipping address |
| `billing`  | Customer billing address  |
| `base`     | Shop base address         |

### shipping_tax_class

| Value          | Description                              |
|----------------|------------------------------------------|
| `""`(empty)    | Shipping tax class based on cart items   |
| `standard`     | Standard tax rate                        |
| `reduced-rate` | Reduced rate (if configured)             |
| `zero-rate`    | Zero rate (if configured)                |

### tax_round_at_subtotal

| Value          | Description                 |
|----------------|-----------------------------|
| `yes` / `true` | Round tax at subtotal level |
| `no` / `false` | Round tax per line item     |

### tax_display_shop / tax_display_cart

| Value  | Description    |
|--------|----------------|
| `incl` | Including tax  |
| `excl` | Excluding tax  |

### tax_total_display

| Value      | Description         |
|------------|---------------------|
| `single`   | As a single total   |
| `itemized` | Itemized            |

---

## Tax Rates

Manage WooCommerce tax rates (Standard rates, Reduced rates, Zero rates, etc.)

**WooCommerce Setting:** WooCommerce → Settings → Tax → Standard rates / Reduced rate rates / Zero rate rates

---

### GET /tax-rates

Retrieve all tax rates with optional filtering and pagination.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/tax-rates`

**Query Parameters:**

| Parameter  | Type    | Default          | Description                                                        |
|------------|---------|------------------|--------------------------------------------------------------------|
| `class`    | string  | -                | Filter by tax class (`standard`, `reduced-rate`, `zero-rate`, etc) |
| `page`     | integer | 1                | Page number                                                        |
| `per_page` | integer | 100              | Items per page (max 1000)                                          |
| `orderby`  | string  | `tax_rate_order` | Order by field                                                     |
| `order`    | string  | `ASC`            | Sort order (`ASC` or `DESC`)                                       |

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "country": "US",
            "state": "CA",
            "postcodes": ["90210", "90211"],
            "cities": ["Beverly Hills"],
            "postcode": "90210;90211",
            "city": "Beverly Hills",
            "rate": "7.2500",
            "name": "CA Tax",
            "priority": 1,
            "compound": false,
            "shipping": true,
            "order": 1,
            "class": "standard"
        }
    ],
    "meta": {
        "total": 150,
        "page": 1,
        "per_page": 100,
        "total_pages": 2
    }
}
```

**cURL Example:**
```bash
# Get all tax rates
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get only standard rates
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates?class=standard" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### GET /tax-rates/{id}

Retrieve a single tax rate by ID.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/tax-rates/{id}`

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "country": "US",
        "state": "CA",
        "postcodes": ["90210"],
        "cities": ["Beverly Hills"],
        "rate": "7.2500",
        "name": "CA Tax",
        "priority": 1,
        "compound": false,
        "shipping": true,
        "class": "standard"
    }
}
```

---

### POST /tax-rates

Create a new tax rate.

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/tax-rates`

**Request Body:**

| Parameter  | Type         | Required | Default    | Description                                      |
|------------|--------------|----------|------------|--------------------------------------------------|
| `country`  | string       | No       | `""`       | Two-letter country code (e.g., `US`, `GB`)       |
| `state`    | string       | No       | `""`       | State/province code (e.g., `CA`, `NY`)           |
| `postcode` | string/array | No       | `""`       | Postcode(s), semicolon-separated or array        |
| `city`     | string/array | No       | `""`       | City/cities, semicolon-separated or array        |
| `rate`     | string       | **Yes**  | -          | Tax rate percentage (e.g., `7.25`)               |
| `name`     | string       | No       | `""`       | Tax rate name (e.g., `VAT`, `Sales Tax`)         |
| `priority` | integer      | No       | `1`        | Priority for multiple rates                      |
| `compound` | boolean      | No       | `false`    | Whether tax is compound                          |
| `shipping` | boolean      | No       | `true`     | Whether tax applies to shipping                  |
| `class`    | string       | No       | `standard` | Tax class                                        |

**Request Example:**
```json
{
    "country": "US",
    "state": "CA",
    "postcode": "90210;90211;90212",
    "city": "Beverly Hills",
    "rate": "7.25",
    "name": "CA Sales Tax",
    "priority": 1,
    "compound": false,
    "shipping": true,
    "class": "standard"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 5,
        "country": "US",
        "state": "CA",
        "rate": "7.2500",
        "name": "CA Sales Tax",
        ...
    },
    "message": "Tax rate created successfully"
}
```

---

### PUT /tax-rates/{id}

Update an existing tax rate.

**Endpoint:** `PUT /wp-json/woocommerce-ext/v1/tax-rates/{id}`

**Request Body:** Same as POST (all fields optional for updates)

---

### DELETE /tax-rates/{id}

Delete a tax rate.

**Endpoint:** `DELETE /wp-json/woocommerce-ext/v1/tax-rates/{id}`

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Tax rate deleted successfully",
    "id": 5
}
```

---

### POST /tax-rates/batch

Perform batch operations (create, update, delete multiple rates).

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/tax-rates/batch`

**Request Body:**

| Parameter | Type  | Description                                        |
|-----------|-------|----------------------------------------------------|
| `create`  | array | Array of tax rates to create                       |
| `update`  | array | Array of tax rates to update (must include `id`)   |
| `delete`  | array | Array of tax rate IDs to delete                    |

**Request Example:**
```json
{
    "create": [
        { "country": "US", "state": "NY", "rate": "8.875", "name": "NY Tax" }
    ],
    "update": [
        { "id": 1, "rate": "7.50" }
    ],
    "delete": [3, 4, 5]
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "created": [...],
        "updated": [...],
        "deleted": [3, 4, 5],
        "errors": []
    }
}
```

---

### POST /tax-rates/import

Import tax rates from CSV content or file upload.

**Endpoint:** `POST /wp-json/woocommerce-ext/v1/tax-rates/import`

**Request Parameters:**

| Parameter         | Type    | Default | Description                                    |
|-------------------|---------|---------|------------------------------------------------|
| `csv_content`     | string  | -       | CSV content string                             |
| `update_existing` | boolean | `false` | Update existing rates if found                 |
| `delete_existing` | boolean | `false` | Delete all existing rates before import        |
| `class`           | string  | -       | Override tax class for all imported rates      |

**CSV Format:**
```csv
country,state,postcode,city,rate,name,priority,compound,shipping,class
US,CA,*,*,7.25,CA Tax,1,0,1,
US,NY,*,*,8.875,NY Tax,1,0,1,standard
GB,*,*,*,20,VAT,1,0,1,
```

**CSV Columns:**

| Column     | Description                                           |
|------------|-------------------------------------------------------|
| `country`  | Two-letter country code (e.g., `US`, `GB`)            |
| `state`    | State code or `*` for all states                      |
| `postcode` | Postcode or `*` for all, semicolon for multiple       |
| `city`     | City name or `*` for all, semicolon for multiple      |
| `rate`     | Tax rate percentage                                   |
| `name`     | Tax rate name                                         |
| `priority` | Priority number (1-99)                                |
| `compound` | `1` for compound, `0` for not                         |
| `shipping` | `1` to apply to shipping, `0` to not                  |
| `class`    | Tax class slug (empty for standard)                   |

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "imported": 45,
        "updated": 5,
        "skipped": 0,
        "errors": []
    },
    "message": "Import completed. Imported: 45, Updated: 5, Skipped: 0, Errors: 0"
}
```

**cURL Examples:**
```bash
# Import CSV content
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/import" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "csv_content": "country,state,postcode,city,rate,name,priority,compound,shipping,class\nUS,CA,*,*,7.25,CA Tax,1,0,1,",
    "delete_existing": true
  }'

# Import CSV file
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/import" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@tax_rates.csv" \
  -F "update_existing=true"
```

---

### GET /tax-rates/export

Export tax rates to CSV format.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/tax-rates/export`

**Query Parameters:**

| Parameter  | Type    | Default | Description                                 |
|------------|---------|---------|---------------------------------------------|
| `class`    | string  | -       | Export only rates for this tax class        |
| `download` | boolean | `false` | Return as downloadable file                 |

**Response (200 OK):**
```json
{
    "success": true,
    "csv_content": "country,state,postcode,city,rate,name,priority,compound,shipping,class\nUS,CA,*,*,7.2500,CA Tax,1,0,1,\n...",
    "filename": "tax_rates_all_2024-01-15.csv",
    "total": 50
}
```

**cURL Examples:**
```bash
# Get CSV content as JSON
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/export" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Download as file
curl -X GET "https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/export?download=true" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o tax_rates.csv
```

---

### GET /tax-rates/classes

Get all available tax classes.

**Endpoint:** `GET /wp-json/woocommerce-ext/v1/tax-rates/classes`

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        { "slug": "standard", "name": "Standard" },
        { "slug": "reduced-rate", "name": "Reduced rate" },
        { "slug": "zero-rate", "name": "Zero rate" }
    ]
}
```

---

### DELETE /tax-rates/all

Delete all tax rates for a specific class. **Use with caution!**

**Endpoint:** `DELETE /wp-json/woocommerce-ext/v1/tax-rates/all`

**Query Parameters:**

| Parameter | Type   | Default        | Description                    |
|-----------|--------|----------------|--------------------------------|
| `class`   | string | `""` (standard)| Tax class to delete rates for  |

**Response (200 OK):**
```json
{
    "success": true,
    "deleted": 150,
    "message": "150 tax rates deleted"
}
```

---

## Error Responses

### 400 Bad Request
```json
{
    "success": false,
    "message": "WooCommerce is not active",
    "code": "woocommerce_not_active"
}
```

### 403 Forbidden
```json
{
    "code": "rest_forbidden",
    "message": "You do not have permission to view WooCommerce settings.",
    "data": { "status": 403 }
}
```

### 404 Not Found
```json
{
    "success": false,
    "message": "Tax rate not found",
    "code": "not_found"
}
```

---

## Code Examples

### JavaScript - Import Tax Rates
```javascript
const importTaxRates = async (csvContent, options = {}) => {
    const response = await fetch('https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/import', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer YOUR_TOKEN'
        },
        body: JSON.stringify({
            csv_content: csvContent,
            delete_existing: options.deleteExisting || false,
            update_existing: options.updateExisting || false
        })
    });
    return response.json();
};
```

### JavaScript - Export Tax Rates
```javascript
const exportTaxRates = async (taxClass = null) => {
    const url = new URL('https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/export');
    if (taxClass) url.searchParams.set('class', taxClass);
    
    const response = await fetch(url, {
        headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
    });
    return response.json();
};
```

### PHP - Import Tax Rates
```php
$response = wp_remote_post('https://your-site.com/wp-json/woocommerce-ext/v1/tax-rates/import', [
    'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ],
    'body' => json_encode([
        'csv_content'     => $csv_content,
        'delete_existing' => true,
    ]),
]);
```

---

## Endpoints Summary

| Method | Endpoint                             | Description                     |
|--------|--------------------------------------|---------------------------------|
| GET    | `/woocommerce-ext/v1/coupons`        | Get coupon status               |
| POST   | `/woocommerce-ext/v1/coupons`        | Enable/disable coupons          |
| GET    | `/woocommerce-ext/v1/taxes`          | Get tax calculation status      |
| POST   | `/woocommerce-ext/v1/taxes`          | Enable/disable tax calculations |
| GET    | `/woocommerce-ext/v1/taxes-options`  | Get all tax options             |
| POST   | `/woocommerce-ext/v1/taxes-options`  | Update tax options              |
| GET    | `/woocommerce-ext/v1/tax-rates`      | List all tax rates              |
| POST   | `/woocommerce-ext/v1/tax-rates`      | Create tax rate                 |
| GET    | `/woocommerce-ext/v1/tax-rates/{id}` | Get single tax rate             |
| PUT    | `/woocommerce-ext/v1/tax-rates/{id}` | Update tax rate                 |
| DELETE | `/woocommerce-ext/v1/tax-rates/{id}` | Delete tax rate                 |
| POST   | `/woocommerce-ext/v1/tax-rates/batch`| Batch create/update/delete      |
| POST   | `/woocommerce-ext/v1/tax-rates/import`| Import from CSV                |
| GET    | `/woocommerce-ext/v1/tax-rates/export`| Export to CSV                  |
| GET    | `/woocommerce-ext/v1/tax-rates/classes`| Get tax classes               |
| DELETE | `/woocommerce-ext/v1/tax-rates/all`  | Delete all rates for a class    |

---

## Changelog

### Version 1.3.0
- Added Tax Rates CRUD endpoints
- Added Tax Rates batch operations
- Added CSV import/export functionality
- Added tax classes endpoint
- Added delete all rates endpoint
- Updated Tax Options with all WooCommerce settings

### Version 1.2.9
- Tax Options endpoint with full settings support

### Version 1.0.0
- Initial release