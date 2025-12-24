# อธิบายหน้าที่ของแต่ละไฟล์ใน Plugin

## โครงสร้างไฟล์ทั้งหมด

```
acf-rest-api/
├── acf-rest-api.php                 # ไฟล์หลัก (Entry Point)
├── includes/
│   ├── class-gtm-tracking.php       # จัดการ GTM Tracking
│   ├── class-options-api.php        # จัดการ ACF Options
│   ├── class-rest-endpoints.php     # ลงทะเบียน REST API
│   ├── class-plugin-updater.php     # Auto-update จาก GCS
│   ├── class-coupon-setting.php     # WooCommerce Coupon
│   ├── class-tax-setting.php        # WooCommerce Tax เปิด/ปิด
│   ├── class-tax-options-setting.php # WooCommerce Tax Options
│   └── class-tax-rates.php          # WooCommerce Tax Rates CRUD
├── cloudbuild.yaml                  # Config สำหรับ Cloud Build
├── plugin-info.json                 # ข้อมูลสำหรับ Auto-update
└── readme.txt                       # คำอธิบาย plugin
```

---

## 1. acf-rest-api.php (ไฟล์หลัก)

### หน้าที่
- **Entry Point** ของ plugin (WordPress โหลดไฟล์นี้ก่อน)
- กำหนด **ข้อมูล plugin** (ชื่อ, version, ผู้สร้าง)
- **โหลดไฟล์อื่นๆ** ทั้งหมด
- **เริ่มต้น components** ต่างๆ

### โครงสร้างไฟล์

```php
<?php
/**
 * Plugin Name: REST API Extended      ← ชื่อที่แสดงใน WordPress
 * Version: 1.3.3                      ← เลข version (สำคัญมาก!)
 * Description: ...                    ← คำอธิบาย
 */

// ค่าคงที่
define('ACF_REST_API_VERSION', '1.3.3');        // version (ต้องตรงกับ header)
define('ACF_REST_API_PLUGIN_DIR', ...);         // path ของ plugin
define('ACF_REST_API_UPDATE_URL', ...);         // URL สำหรับ check update

// Class หลัก
class ACF_REST_API_Plugin {
    
    // โหลดไฟล์ทั้งหมด
    public function load_dependencies() {
        require_once 'includes/class-gtm-tracking.php';
        require_once 'includes/class-options-api.php';
        // ... ไฟล์อื่นๆ
    }
    
    // เริ่มต้น GTM Tracking (ต้องทำก่อน acf/init)
    public function init_gtm_tracking() {
        ACF_REST_GTM_Tracking::get_instance();
    }
    
    // เริ่มต้น components อื่นๆ
    public function init_components() {
        ACF_REST_Options_API::get_instance();
        ACF_REST_Plugin_Updater::get_instance();
        // ... components อื่นๆ
    }
    
    // ลงทะเบียน REST API
    public function register_rest_routes() {
        ACF_REST_Endpoints::get_instance()->register_routes();
    }
}
```

### Hooks ที่ใช้

| Hook | Priority | ทำอะไร |
|------|----------|--------|
| `plugins_loaded` | 1 | สร้าง instance ของ plugin |
| `plugins_loaded` | 5 | โหลดไฟล์ dependencies |
| `plugins_loaded` | 10 | เริ่มต้น GTM Tracking |
| `init` | 10 | เริ่มต้น components อื่นๆ |
| `rest_api_init` | 10 | ลงทะเบียน REST routes |

---

## 2. class-gtm-tracking.php (GTM Tracking)

### หน้าที่
- **สร้าง Options Page** "GTM Tracking" ใน WordPress Admin
- **สร้าง ACF Fields** สำหรับใส่ tracking code
- **แสดง tracking code** ใน `<head>` และ `<body>`
- **REST API** สำหรับ GET/POST tracking settings

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_GTM_Tracking {

    const OPTIONS_POST_ID = 'gtm_tracking';  // ID สำหรับเก็บข้อมูล
    
    // สร้าง Options Page ใน Admin Menu
    public function create_options_page() {
        acf_add_options_page([
            'page_title' => 'GTM Tracking',
            'menu_slug'  => 'gtm_tracking',
            'post_id'    => 'gtm_tracking',  // เก็บแยกจาก options อื่น
        ]);
    }
    
    // สร้าง ACF Fields
    public function register_acf_fields() {
        acf_add_local_field_group([
            'fields' => [
                ['name' => 'gtm_tracking_header', ...],     // Code ใส่ <head>
                ['name' => 'gtm_tracking_body', ...],       // Code ใส่ <body>
                ['name' => 'gtm_additional_scripts', ...],  // Scripts เพิ่มเติม
            ],
            'location' => [['options_page' => 'gtm_tracking']],
        ]);
    }
    
    // แสดง code ใน <head>
    public function inject_header_tracking() {
        $code = get_field('gtm_tracking_header', 'gtm_tracking');
        echo $code;
    }
    
    // แสดง code หลัง <body>
    public function inject_body_tracking() {
        $code = get_field('gtm_tracking_body', 'gtm_tracking');
        echo $code;
    }
    
    // REST API: GET /options/track
    public function rest_get_handler($request) {
        return get_fields('gtm_tracking');
    }
    
    // REST API: POST /options/track
    public function rest_update_handler($request) {
        $data = $request->get_json_params();
        foreach ($data as $key => $value) {
            update_field($key, $value, 'gtm_tracking');
        }
    }
}
```

### Hooks ที่ใช้

| Hook | ทำอะไร |
|------|--------|
| `acf/init` (priority 5) | สร้าง Options Page |
| `acf/init` (priority 10) | สร้าง ACF Fields |
| `wp_head` (priority 1) | แสดง header tracking code |
| `wp_body_open` (priority 1) | แสดง body tracking code |

### REST Endpoints

| Method | Endpoint | หน้าที่ |
|--------|----------|--------|
| GET | `/wp-json/options/track` | ดึง GTM settings |
| POST | `/wp-json/options/track` | อัปเดต GTM settings |

---

## 3. class-options-api.php (ACF Options)

### หน้าที่
- **GET** ดึงค่า ACF options ทั้งหมด
- **POST** อัปเดตค่า ACF options
- **Sanitize** ข้อมูลตาม field type

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_Options_API {

    // GET /options/all - ดึงค่าทั้งหมด
    public function get_all_options($request) {
        $fields = get_fields('options');  // ดึงจาก ACF options
        return $fields;
    }
    
    // POST /options/all - อัปเดตค่า
    public function update_options($request) {
        $params = $request->get_params();
        
        foreach ($params as $field_name => $value) {
            // ตรวจสอบว่า field มีอยู่จริง
            $field = get_field_object($field_name, 'option');
            if ($field) {
                // Sanitize ตาม field type
                $clean_value = $this->sanitize_field_value($value, $field);
                update_field($field_name, $clean_value, 'option');
            }
        }
    }
    
    // Sanitize ข้อมูลตาม type
    private function sanitize_field_value($value, $field) {
        switch ($field['type']) {
            case 'text':
                return sanitize_text_field($value);
            case 'textarea':
                return wp_kses_post($value);
            case 'number':
                return floatval($value);
            case 'true_false':
                return (bool) $value;
            // ... types อื่นๆ
        }
    }
    
    // ตรวจสอบสิทธิ์
    public function check_write_permission($request) {
        return current_user_can('manage_options');
    }
}
```

### REST Endpoints

| Method | Endpoint | สิทธิ์ | หน้าที่ |
|--------|----------|-------|--------|
| GET | `/wp-json/options/all` | Public | ดึง ACF options ทั้งหมด |
| POST | `/wp-json/options/all` | Admin | อัปเดต ACF options |

---

## 4. class-rest-endpoints.php (REST Routes)

### หน้าที่
- **ลงทะเบียน REST API routes** ทั้งหมด
- **กำหนด arguments** สำหรับแต่ละ endpoint
- **เชื่อมต่อ** routes กับ handler functions

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_Endpoints {

    const REST_NAMESPACE = 'options';              // /wp-json/options/...
    const WC_NAMESPACE = 'woocommerce-ext/v1';     // /wp-json/woocommerce-ext/v1/...

    // ลงทะเบียน routes ทั้งหมด
    public function register_routes() {
        $this->register_options_routes();      // /options/all
        $this->register_gtm_routes();          // /options/track
        $this->register_wc_coupon_routes();    // /woocommerce-ext/v1/coupons
        $this->register_wc_tax_routes();       // /woocommerce-ext/v1/taxes
        $this->register_wc_tax_options_routes(); // /woocommerce-ext/v1/taxes-options
        $this->register_wc_tax_rates_routes(); // /woocommerce-ext/v1/tax-rates
    }
    
    // ตัวอย่าง: ลงทะเบียน GTM routes
    private function register_gtm_routes() {
        $gtm = ACF_REST_GTM_Tracking::get_instance();
        
        // GET /options/track
        register_rest_route('options', '/track', [
            'methods'  => 'GET',
            'callback' => [$gtm, 'rest_get_handler'],
            'permission_callback' => '__return_true',  // Public
        ]);
        
        // POST /options/track
        register_rest_route('options', '/track', [
            'methods'  => 'POST',
            'callback' => [$gtm, 'rest_update_handler'],
            'permission_callback' => [$this, 'check_track_write_permission'],
        ]);
    }
    
    // ตัวอย่าง: ลงทะเบียน Tax Rates routes (CRUD)
    private function register_wc_tax_rates_routes() {
        $tax_rates = ACF_REST_WC_Tax_Rates::get_instance();
        
        // GET /tax-rates (list all)
        register_rest_route(self::WC_NAMESPACE, '/tax-rates', [
            'methods'  => 'GET',
            'callback' => [$tax_rates, 'rest_get_rates_handler'],
        ]);
        
        // POST /tax-rates (create)
        register_rest_route(self::WC_NAMESPACE, '/tax-rates', [
            'methods'  => 'POST',
            'callback' => [$tax_rates, 'rest_create_rate_handler'],
        ]);
        
        // GET /tax-rates/{id} (read one)
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$tax_rates, 'rest_get_rate_handler'],
        ]);
        
        // PUT /tax-rates/{id} (update)
        // DELETE /tax-rates/{id} (delete)
        // ... etc
    }
}
```

### Routes ทั้งหมด

| Namespace | Route | Methods |
|-----------|-------|---------|
| `options` | `/all` | GET, POST |
| `options` | `/track` | GET, POST |
| `woocommerce-ext/v1` | `/coupons` | GET, POST |
| `woocommerce-ext/v1` | `/taxes` | GET, POST |
| `woocommerce-ext/v1` | `/taxes-options` | GET, POST |
| `woocommerce-ext/v1` | `/tax-rates` | GET, POST |
| `woocommerce-ext/v1` | `/tax-rates/{id}` | GET, PUT, DELETE |
| `woocommerce-ext/v1` | `/tax-rates/batch` | POST |
| `woocommerce-ext/v1` | `/tax-rates/import` | POST |
| `woocommerce-ext/v1` | `/tax-rates/export` | GET |

---

## 5. class-plugin-updater.php (Auto-Update)

### หน้าที่
- **ตรวจสอบ version** ใหม่จาก GCS
- **แสดงข้อมูล plugin** ในหน้า details
- **แก้ไขชื่อ folder** เมื่อ update (สำคัญมาก!)
- **ดาวน์โหลดและติดตั้ง** update

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_Plugin_Updater {

    private $plugin_slug = 'acf-rest-api';
    private $plugin_basename = 'acf-rest-api/acf-rest-api.php';
    private $update_url = 'https://storage.googleapis.com/.../plugin-info.json';
    
    public function __construct() {
        // Hook เข้า WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
    }
    
    // ตรวจสอบ update
    public function check_for_update($transient) {
        // 1. ดึงข้อมูลจาก GCS
        $remote = $this->get_remote_info();
        
        // 2. เปรียบเทียบ version
        $current = $this->get_current_version();  // เช่น "1.3.3"
        $remote_ver = $remote->version;           // เช่น "1.3.4"
        
        // 3. ถ้า remote > current = มี update
        if (version_compare($current, $remote_ver, '<')) {
            $transient->response[$this->plugin_basename] = (object) [
                'slug'        => $this->plugin_slug,
                'new_version' => $remote_ver,
                'package'     => $remote->download_url,  // URL ของ ZIP
            ];
        }
        
        return $transient;
    }
    
    // แก้ไขชื่อ folder (สำคัญมาก!)
    public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra) {
        // ZIP อาจแตกเป็น "acf-rest-api-main" แต่ต้องเป็น "acf-rest-api"
        
        $expected = 'acf-rest-api';
        $corrected = trailingslashit($remote_source) . $expected . '/';
        
        // Rename folder
        $wp_filesystem->move($source, $corrected);
        
        return $corrected;
    }
    
    // ดึงข้อมูลจาก GCS
    private function get_remote_info() {
        $response = wp_remote_get($this->update_url);
        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }
    
    // เพิ่มลิงก์ "Check for updates"
    public function add_action_links($links) {
        $check_link = '<a href="...">Check for updates</a>';
        array_unshift($links, $check_link);
        return $links;
    }
}
```

### Flow การ Update

```
┌─────────────────────────────────────────────────────────┐
│  1. WordPress เรียก check_for_update()                  │
│     └── ดึง plugin-info.json จาก GCS                    │
│     └── เปรียบเทียบ: local 1.3.3 vs remote 1.3.4       │
│     └── ถ้า remote > local → แสดง "Update available"   │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  2. User คลิก "Update Now"                              │
│     └── WordPress ดาวน์โหลด ZIP จาก GCS                 │
│     └── แตกไฟล์ไปที่ /tmp/                              │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  3. fix_source_dir() ทำงาน                              │
│     └── ตรวจสอบชื่อ folder ที่แตกออกมา                   │
│     └── Rename เป็น "acf-rest-api" (ถ้าจำเป็น)          │
└─────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  4. WordPress ติดตั้ง plugin                            │
│     └── ลบ folder เก่า                                  │
│     └── Copy folder ใหม่ไปที่ /wp-content/plugins/      │
│     └── Activate plugin                                │
└─────────────────────────────────────────────────────────┘
```

---

## 6. class-coupon-setting.php (WooCommerce Coupon)

### หน้าที่
- **GET** ดึงสถานะ coupon (เปิด/ปิด)
- **POST** เปลี่ยนสถานะ coupon

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_WC_Coupon_Settings {

    const OPTION_NAME = 'woocommerce_enable_coupons';
    
    // ดึงสถานะ
    public function get_status() {
        $enabled = get_option(self::OPTION_NAME, 'yes');
        return [
            'enabled'   => $enabled === 'yes',
            'raw_value' => $enabled,
        ];
    }
    
    // เปลี่ยนสถานะ
    public function update_status($enabled) {
        $value = $enabled ? 'yes' : 'no';
        update_option(self::OPTION_NAME, $value);
        return $this->get_status();
    }
    
    // REST API handlers
    public function rest_get_handler($request) {
        return new WP_REST_Response($this->get_status());
    }
    
    public function rest_update_handler($request) {
        $enabled = $request->get_param('enabled');
        return new WP_REST_Response($this->update_status($enabled));
    }
}
```

### REST Endpoints

| Method | Endpoint | Body | หน้าที่ |
|--------|----------|------|--------|
| GET | `/woocommerce-ext/v1/coupons` | - | ดึงสถานะ |
| POST | `/woocommerce-ext/v1/coupons` | `{"enabled": true}` | เปิด/ปิด coupon |

---

## 7. class-tax-setting.php (WooCommerce Tax เปิด/ปิด)

### หน้าที่
- **GET** ดึงสถานะ tax calculation (เปิด/ปิด)
- **POST** เปลี่ยนสถานะ tax calculation

### โครงสร้าง (คล้ายกับ coupon-setting)

```php
<?php
class ACF_REST_WC_Tax_Settings {

    const OPTION_NAME = 'woocommerce_calc_taxes';
    
    public function get_status() {
        $enabled = get_option(self::OPTION_NAME, 'no');
        return ['enabled' => $enabled === 'yes'];
    }
    
    public function update_status($enabled) {
        update_option(self::OPTION_NAME, $enabled ? 'yes' : 'no');
        return $this->get_status();
    }
}
```

### REST Endpoints

| Method | Endpoint | หน้าที่ |
|--------|----------|--------|
| GET | `/woocommerce-ext/v1/taxes` | ดึงสถานะ tax |
| POST | `/woocommerce-ext/v1/taxes` | เปิด/ปิด tax |

---

## 8. class-tax-options-setting.php (WooCommerce Tax Options)

### หน้าที่
- **GET** ดึง tax options ทั้งหมด
- **POST** อัปเดต tax options

### Tax Options ที่จัดการ

| Option | คำอธิบาย | ค่าที่เป็นไปได้ |
|--------|----------|----------------|
| `prices_include_tax` | ราคารวม VAT หรือไม่ | yes, no |
| `tax_based_on` | คำนวณ tax จากที่อยู่ไหน | shipping, billing, base |
| `shipping_tax_class` | Tax class สำหรับค่าส่ง | standard, reduced-rate, etc. |
| `tax_round_at_subtotal` | ปัดเศษที่ subtotal | yes, no |
| `tax_display_shop` | แสดงราคาในร้านค้า | incl, excl |
| `tax_display_cart` | แสดงราคาในตะกร้า | incl, excl |
| `tax_total_display` | แสดง tax total | single, itemized |
| `price_display_suffix` | ข้อความต่อท้ายราคา | เช่น "inc. VAT" |

### REST Endpoints

| Method | Endpoint | หน้าที่ |
|--------|----------|--------|
| GET | `/woocommerce-ext/v1/taxes-options` | ดึง options ทั้งหมด |
| POST | `/woocommerce-ext/v1/taxes-options` | อัปเดต options |

---

## 9. class-tax-rates.php (WooCommerce Tax Rates)

### หน้าที่
- **CRUD** สำหรับ tax rates
- **Batch operations** (สร้าง/แก้ไข/ลบ หลายรายการพร้อมกัน)
- **Import** จาก CSV
- **Export** เป็น CSV

### โครงสร้างไฟล์

```php
<?php
class ACF_REST_WC_Tax_Rates {

    // ดึง tax rates ทั้งหมด
    public function get_rates($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_tax_rates';
        
        $results = $wpdb->get_results("SELECT * FROM $table ...");
        
        return [
            'rates' => $results,
            'total' => count($results),
        ];
    }
    
    // สร้าง tax rate ใหม่
    public function create_rate($data) {
        $rate_id = WC_Tax::_insert_tax_rate([
            'tax_rate_country'  => $data['country'],
            'tax_rate_state'    => $data['state'],
            'tax_rate'          => $data['rate'],
            'tax_rate_name'     => $data['name'],
            'tax_rate_priority' => $data['priority'],
            'tax_rate_compound' => $data['compound'],
            'tax_rate_shipping' => $data['shipping'],
            'tax_rate_class'    => $data['class'],
        ]);
        
        return $this->get_rate($rate_id);
    }
    
    // อัปเดต tax rate
    public function update_rate($rate_id, $data) {
        WC_Tax::_update_tax_rate($rate_id, $data);
        return $this->get_rate($rate_id);
    }
    
    // ลบ tax rate
    public function delete_rate($rate_id) {
        WC_Tax::_delete_tax_rate($rate_id);
        return ['success' => true];
    }
    
    // Import จาก CSV
    public function import_csv($csv_content, $options = []) {
        $lines = $this->parse_csv($csv_content);
        
        foreach ($lines as $row) {
            $this->create_rate($row);
        }
        
        return ['imported' => count($lines)];
    }
    
    // Export เป็น CSV
    public function export_csv($tax_class = null) {
        $rates = $this->get_rates(['class' => $tax_class]);
        
        $csv = "country,state,postcode,city,rate,name,...\n";
        foreach ($rates['rates'] as $rate) {
            $csv .= "{$rate['country']},{$rate['state']},...\n";
        }
        
        return ['csv_content' => $csv];
    }
}
```

### REST Endpoints

| Method | Endpoint | หน้าที่ |
|--------|----------|--------|
| GET | `/tax-rates` | ดึง rates ทั้งหมด |
| POST | `/tax-rates` | สร้าง rate ใหม่ |
| GET | `/tax-rates/{id}` | ดึง rate เดียว |
| PUT | `/tax-rates/{id}` | อัปเดต rate |
| DELETE | `/tax-rates/{id}` | ลบ rate |
| POST | `/tax-rates/batch` | Batch operations |
| POST | `/tax-rates/import` | Import CSV |
| GET | `/tax-rates/export` | Export CSV |
| GET | `/tax-rates/classes` | ดึง tax classes |
| DELETE | `/tax-rates/all` | ลบทั้งหมด |

---

## 10. plugin-info.json (Update Metadata)

### หน้าที่
- **ข้อมูลสำหรับ auto-update** (version, download URL)
- **ข้อมูลแสดงในหน้า plugin details** (description, changelog)

### โครงสร้างไฟล์

```json
{
    "name": "ACF REST API Extended",
    "slug": "acf-rest-api",
    "version": "1.3.3",                    // ← version ปัจจุบัน
    "download_url": "https://storage.googleapis.com/.../acf-rest-api.zip",
    "last_updated": "2025-12-23 00:00:00",
    "requires": "5.8",                     // WordPress version ขั้นต่ำ
    "tested": "6.7",                       // WordPress version ที่ทดสอบแล้ว
    "requires_php": "7.4",                 // PHP version ขั้นต่ำ
    "sections": {
        "description": "<p>คำอธิบาย plugin</p>",
        "changelog": "<h4>1.3.3</h4><ul><li>Changes...</li></ul>",
        "installation": "<ol><li>Upload...</li></ol>"
    },
    "banners": {
        "low": "https://.../banner-772x250.png",
        "high": "https://.../banner-1544x500.png"
    }
}
```

### Fields สำคัญ

| Field | ใช้ทำอะไร |
|-------|----------|
| `version` | เปรียบเทียบกับ version ที่ติดตั้ง |
| `download_url` | URL สำหรับดาวน์โหลด ZIP |
| `sections.changelog` | แสดงใน plugin details |
| `requires` | ตรวจสอบ compatibility |

---

## 11. cloudbuild.yaml (Cloud Build Config)

### หน้าที่
- **บอก Cloud Build** ว่าต้องทำอะไรเมื่อ push code
- **สร้าง ZIP** และ **upload ไป GCS**

(ดูรายละเอียดใน CLOUDBUILD-EXPLAINED-TH.md)

---

## สรุป: ไฟล์ไหนทำอะไร

| ไฟล์ | หมวด | หน้าที่หลัก |
|------|------|------------|
| `acf-rest-api.php` | Core | Entry point, โหลดไฟล์, เริ่มต้น components |
| `class-gtm-tracking.php` | GTM | Options page, ACF fields, แสดง tracking code |
| `class-options-api.php` | ACF | REST API สำหรับ ACF options |
| `class-rest-endpoints.php` | API | ลงทะเบียน REST routes ทั้งหมด |
| `class-plugin-updater.php` | Update | Auto-update จาก GCS |
| `class-coupon-setting.php` | WooCommerce | เปิด/ปิด coupon |
| `class-tax-setting.php` | WooCommerce | เปิด/ปิด tax |
| `class-tax-options-setting.php` | WooCommerce | Tax options settings |
| `class-tax-rates.php` | WooCommerce | Tax rates CRUD + CSV |
| `plugin-info.json` | Update | Metadata สำหรับ auto-update |
| `cloudbuild.yaml` | Deploy | Config สำหรับ Cloud Build |

---

## Dependency Graph

```
acf-rest-api.php (Entry Point)
    │
    ├── โหลด ──► class-gtm-tracking.php
    │               └── ใช้ ACF functions
    │
    ├── โหลด ──► class-options-api.php
    │               └── ใช้ ACF functions
    │
    ├── โหลด ──► class-rest-endpoints.php
    │               ├── เรียก class-gtm-tracking.php
    │               ├── เรียก class-options-api.php
    │               ├── เรียก class-coupon-setting.php
    │               ├── เรียก class-tax-setting.php
    │               ├── เรียก class-tax-options-setting.php
    │               └── เรียก class-tax-rates.php
    │
    ├── โหลด ──► class-plugin-updater.php
    │               └── ดึงข้อมูลจาก plugin-info.json (บน GCS)
    │
    └── โหลด ──► WooCommerce classes
                    ├── class-coupon-setting.php
                    ├── class-tax-setting.php
                    ├── class-tax-options-setting.php
                    └── class-tax-rates.php
                            └── ใช้ WC_Tax class
```
