# WooCommerce Settings REST API Documentation

REST API endpoints for managing WooCommerce Coupon and Tax settings.

**Base URL:** `https://your-site.com/wp-json/woocommerce-ext/v1`

**Authentication:** All endpoints require `manage_woocommerce` capability.

---

## Table of Contents

1. [Coupons](#coupons)
2. [Taxes (Enable/Disable)](#taxes-enabledisable)
3. [Tax Options](#tax-options)

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
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `enabled` | boolean | Yes | `true` to enable, `false` to disable |

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
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `enabled` | boolean | Yes | `true` to enable, `false` to disable |

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
            "value": "inherit",
            "option_name": "woocommerce_shipping_tax_class",
            "description": "Shipping tax class",
            "choices": {
                "inherit": "Shipping tax class based on cart items",
                "standard": "Standard",
                "reduced-rate": "Reduced rate",
                "zero-rate": "Zero rate"
            }
        },
        "tax_round_at_subtotal": {
            "value": "no",
            "enabled": false,
            "option_name": "woocommerce_tax_round_at_subtotal",
            "description": "Rounding"
        },
        "tax_classes": {
            "value": "Reduced rate\nZero rate",
            "option_name": "woocommerce_tax_classes",
            "description": "Additional tax classes",
            "parsed": [
                "Reduced rate",
                "Zero rate"
            ]
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
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `prices_include_tax` | boolean/string | No | `true`/`"yes"` for inclusive, `false`/`"no"` for exclusive |
| `tax_based_on` | string | No | `"shipping"`, `"billing"`, or `"base"` |
| `shipping_tax_class` | string | No | `"inherit"`, `"standard"`, `"reduced-rate"`, `"zero-rate"`, or custom class slug |
| `tax_round_at_subtotal` | boolean/string | No | `true`/`"yes"` to enable rounding at subtotal |
| `tax_classes` | string/array | No | Newline-separated string or array of tax class names |

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
    "tax_based_on": "billing",
    "shipping_tax_class": "standard",
    "tax_round_at_subtotal": true
}
```

**Update tax classes (as array):**
```json
{
    "tax_classes": ["Reduced rate", "Zero rate", "Special rate"]
}
```

**Update tax classes (as string):**
```json
{
    "tax_classes": "Reduced rate\nZero rate\nSpecial rate"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "updated": {
            "prices_include_tax": "yes",
            "tax_based_on": "billing",
            "shipping_tax_class": "standard",
            "tax_round_at_subtotal": "yes"
        },
        "errors": {},
        "message": "Tax options have been updated."
    }
}
```

**Response (207 Multi-Status - Partial Success):**
```json
{
    "success": false,
    "data": {
        "updated": {
            "prices_include_tax": "yes"
        },
        "errors": {
            "tax_based_on": "Invalid value. Must be one of: shipping, billing, base"
        },
        "message": "Some options could not be updated."
    }
}
```

**cURL Examples:**

```bash
# Update prices include tax setting
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"prices_include_tax": true}'

# Update tax calculation basis
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"tax_based_on": "billing"}'

# Update multiple options at once
curl -X POST "https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "prices_include_tax": false,
    "tax_based_on": "shipping",
    "shipping_tax_class": "inherit",
    "tax_round_at_subtotal": false,
    "tax_classes": ["Reduced rate", "Zero rate"]
  }'
```

---

## Error Responses

### 400 Bad Request

**WooCommerce not active:**
```json
{
    "success": false,
    "message": "WooCommerce is not active",
    "code": "woocommerce_not_active"
}
```

**Missing required parameter:**
```json
{
    "message": "Missing required parameter: enabled",
    "code": "missing_parameter"
}
```

**No data provided:**
```json
{
    "success": false,
    "message": "No data provided",
    "code": "missing_data"
}
```

### 403 Forbidden

**Permission denied:**
```json
{
    "code": "rest_forbidden",
    "message": "You do not have permission to view WooCommerce settings.",
    "data": {
        "status": 403
    }
}
```

---

## Option Values Reference

### prices_include_tax
| Value | Description |
|-------|-------------|
| `yes` / `true` | Prices entered inclusive of tax |
| `no` / `false` | Prices entered exclusive of tax |

### tax_based_on
| Value | Description |
|-------|-------------|
| `shipping` | Customer shipping address |
| `billing` | Customer billing address |
| `base` | Shop base address |

### shipping_tax_class
| Value | Description |
|-------|-------------|
| `inherit` | Shipping tax class based on cart items |
| `standard` | Standard tax rate |
| `reduced-rate` | Reduced rate (if configured) |
| `zero-rate` | Zero rate (if configured) |
| `{custom-slug}` | Any custom tax class slug |

### tax_round_at_subtotal
| Value | Description |
|-------|-------------|
| `yes` / `true` | Round tax at subtotal level |
| `no` / `false` | Round tax per line item |

### tax_classes
Default value:
```
Reduced rate
Zero rate
```

Can be provided as:
- Newline-separated string: `"Reduced rate\nZero rate"`
- Array: `["Reduced rate", "Zero rate"]`

---

## JavaScript/Fetch Examples

### Get Coupon Status
```javascript
const response = await fetch('https://your-site.com/wp-json/woocommerce-ext/v1/coupons', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN'
    }
});
const data = await response.json();
console.log('Coupons enabled:', data.data.enabled);
```

### Toggle Tax Calculations
```javascript
const enableTaxes = async (enabled) => {
    const response = await fetch('https://your-site.com/wp-json/woocommerce-ext/v1/taxes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer YOUR_TOKEN'
        },
        body: JSON.stringify({ enabled })
    });
    return response.json();
};

// Enable taxes
await enableTaxes(true);

// Disable taxes
await enableTaxes(false);
```

### Update Tax Options
```javascript
const updateTaxOptions = async (options) => {
    const response = await fetch('https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer YOUR_TOKEN'
        },
        body: JSON.stringify(options)
    });
    return response.json();
};

// Update multiple options
await updateTaxOptions({
    prices_include_tax: true,
    tax_based_on: 'billing',
    tax_round_at_subtotal: true
});
```

---

## PHP/WordPress Examples

### Using wp_remote_get/post
```php
// Get tax options
$response = wp_remote_get('https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
    ],
]);

$body = json_decode(wp_remote_retrieve_body($response), true);

// Update tax options
$response = wp_remote_post('https://your-site.com/wp-json/woocommerce-ext/v1/taxes-options', [
    'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ],
    'body' => json_encode([
        'prices_include_tax' => true,
        'tax_based_on'       => 'shipping',
    ]),
]);
```

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/woocommerce-ext/v1/coupons` | Get coupon status |
| POST | `/woocommerce-ext/v1/coupons` | Enable/disable coupons |
| GET | `/woocommerce-ext/v1/taxes` | Get tax calculation status |
| POST | `/woocommerce-ext/v1/taxes` | Enable/disable tax calculations |
| GET | `/woocommerce-ext/v1/taxes-options` | Get all tax options |
| POST | `/woocommerce-ext/v1/taxes-options` | Update tax options |
