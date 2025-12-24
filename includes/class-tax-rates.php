<?php
/**
 * WooCommerce Tax Rates Handler
 *
 * Handles WooCommerce tax rates CRUD operations and CSV import/export
 * via REST API endpoints.
 *
 * @package ACF_REST_API
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_WC_Tax_Rates {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * CSV columns mapping
     */
    const CSV_COLUMNS = [
        'country',
        'state',
        'postcode',
        'city',
        'rate',
        'name',
        'priority',
        'compound',
        'shipping',
        'class'
    ];

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialization if needed
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public function is_woocommerce_active() {
        return class_exists('WooCommerce') && class_exists('WC_Tax');
    }

    /**
     * Get all tax rates with optional filtering
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_rates($args = []) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        global $wpdb;

        $defaults = [
            'tax_class'  => '',
            'page'       => 1,
            'per_page'   => 100,
            'orderby'    => 'tax_rate_order',
            'order'      => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);

        // Build query
        $table_name = $wpdb->prefix . 'woocommerce_tax_rates';
        $locations_table = $wpdb->prefix . 'woocommerce_tax_rate_locations';

        $where = [];
        $where_values = [];

        // Filter by tax class
        if ($args['tax_class'] !== null) {
            $where[] = 'tax_rate_class = %s';
            $where_values[] = $args['tax_class'] === 'standard' ? '' : sanitize_title($args['tax_class']);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Allowed orderby columns
        $allowed_orderby = ['tax_rate_id', 'tax_rate_country', 'tax_rate_state', 'tax_rate', 'tax_rate_name', 'tax_rate_priority', 'tax_rate_order'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'tax_rate_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        // Pagination
        $per_page = absint($args['per_page']);
        $page = absint($args['page']);
        $offset = ($page - 1) * $per_page;

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = (int) $wpdb->get_var($count_query);

        // Get tax rates
        $query = "SELECT * FROM {$table_name} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, [$per_page, $offset]);
        $results = $wpdb->get_results($wpdb->prepare($query, $query_values), ARRAY_A);

        // Format results and get locations
        $rates = [];
        foreach ($results as $rate) {
            $rate_id = $rate['tax_rate_id'];

            // Get postcodes for this rate
            $postcodes = $wpdb->get_col($wpdb->prepare(
                "SELECT location_code FROM {$locations_table} WHERE tax_rate_id = %d AND location_type = 'postcode'",
                $rate_id
            ));

            // Get cities for this rate
            $cities = $wpdb->get_col($wpdb->prepare(
                "SELECT location_code FROM {$locations_table} WHERE tax_rate_id = %d AND location_type = 'city'",
                $rate_id
            ));

            $rates[] = [
                'id'        => (int) $rate_id,
                'country'   => $rate['tax_rate_country'],
                'state'     => $rate['tax_rate_state'],
                'postcodes' => $postcodes,
                'cities'    => $cities,
                'postcode'  => implode(';', $postcodes),
                'city'      => implode(';', $cities),
                'rate'      => $rate['tax_rate'],
                'name'      => $rate['tax_rate_name'],
                'priority'  => (int) $rate['tax_rate_priority'],
                'compound'  => (bool) $rate['tax_rate_compound'],
                'shipping'  => (bool) $rate['tax_rate_shipping'],
                'order'     => (int) $rate['tax_rate_order'],
                'class'     => $rate['tax_rate_class'] === '' ? 'standard' : $rate['tax_rate_class'],
            ];
        }

        return [
            'success'    => true,
            'rates'      => $rates,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages' => ceil($total / $per_page),
        ];
    }

    /**
     * Get a single tax rate by ID
     *
     * @param int $rate_id Tax rate ID
     * @return array
     */
    public function get_rate($rate_id) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $rate = WC_Tax::_get_tax_rate($rate_id, ARRAY_A);

        if (!$rate) {
            return [
                'success' => false,
                'message' => __('Tax rate not found', 'acf-rest-api'),
            ];
        }

        global $wpdb;
        $locations_table = $wpdb->prefix . 'woocommerce_tax_rate_locations';

        // Get postcodes
        $postcodes = $wpdb->get_col($wpdb->prepare(
            "SELECT location_code FROM {$locations_table} WHERE tax_rate_id = %d AND location_type = 'postcode'",
            $rate_id
        ));

        // Get cities
        $cities = $wpdb->get_col($wpdb->prepare(
            "SELECT location_code FROM {$locations_table} WHERE tax_rate_id = %d AND location_type = 'city'",
            $rate_id
        ));

        return [
            'success' => true,
            'rate'    => [
                'id'        => (int) $rate_id,
                'country'   => $rate['tax_rate_country'],
                'state'     => $rate['tax_rate_state'],
                'postcodes' => $postcodes,
                'cities'    => $cities,
                'postcode'  => implode(';', $postcodes),
                'city'      => implode(';', $cities),
                'rate'      => $rate['tax_rate'],
                'name'      => $rate['tax_rate_name'],
                'priority'  => (int) $rate['tax_rate_priority'],
                'compound'  => (bool) $rate['tax_rate_compound'],
                'shipping'  => (bool) $rate['tax_rate_shipping'],
                'order'     => (int) $rate['tax_rate_order'],
                'class'     => $rate['tax_rate_class'] === '' ? 'standard' : $rate['tax_rate_class'],
            ],
        ];
    }

    /**
     * Create a new tax rate
     *
     * @param array $data Tax rate data
     * @return array
     */
    public function create_rate($data) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $tax_rate = $this->prepare_tax_rate_data($data);

        // Insert the tax rate
        $rate_id = WC_Tax::_insert_tax_rate($tax_rate);

        if (!$rate_id) {
            return [
                'success' => false,
                'message' => __('Failed to create tax rate', 'acf-rest-api'),
            ];
        }

        // Update postcodes if provided
        if (!empty($data['postcode'])) {
            $postcodes = is_array($data['postcode']) ? $data['postcode'] : array_filter(array_map('trim', explode(';', $data['postcode'])));
            WC_Tax::_update_tax_rate_postcodes($rate_id, implode(';', $postcodes));
        }

        // Update cities if provided
        if (!empty($data['city'])) {
            $cities = is_array($data['city']) ? $data['city'] : array_filter(array_map('trim', explode(';', $data['city'])));
            WC_Tax::_update_tax_rate_cities($rate_id, implode(';', $cities));
        }

        // Clear cache
        WC_Cache_Helper::invalidate_cache_group('taxes');

        return $this->get_rate($rate_id);
    }

    /**
     * Update an existing tax rate
     *
     * @param int   $rate_id Tax rate ID
     * @param array $data    Tax rate data
     * @return array
     */
    public function update_rate($rate_id, $data) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        // Check if rate exists
        $existing = WC_Tax::_get_tax_rate($rate_id, ARRAY_A);
        if (!$existing) {
            return [
                'success' => false,
                'message' => __('Tax rate not found', 'acf-rest-api'),
            ];
        }

        $tax_rate = $this->prepare_tax_rate_data($data, $existing);

        // Update the tax rate
        WC_Tax::_update_tax_rate($rate_id, $tax_rate);

        // Update postcodes if provided
        if (isset($data['postcode'])) {
            $postcodes = is_array($data['postcode']) ? $data['postcode'] : array_filter(array_map('trim', explode(';', $data['postcode'])));
            WC_Tax::_update_tax_rate_postcodes($rate_id, implode(';', $postcodes));
        }

        // Update cities if provided
        if (isset($data['city'])) {
            $cities = is_array($data['city']) ? $data['city'] : array_filter(array_map('trim', explode(';', $data['city'])));
            WC_Tax::_update_tax_rate_cities($rate_id, implode(';', $cities));
        }

        // Clear cache
        WC_Cache_Helper::invalidate_cache_group('taxes');

        return $this->get_rate($rate_id);
    }

    /**
     * Delete a tax rate
     *
     * @param int $rate_id Tax rate ID
     * @return array
     */
    public function delete_rate($rate_id) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        // Check if rate exists
        $existing = WC_Tax::_get_tax_rate($rate_id, ARRAY_A);
        if (!$existing) {
            return [
                'success' => false,
                'message' => __('Tax rate not found', 'acf-rest-api'),
            ];
        }

        WC_Tax::_delete_tax_rate($rate_id);

        // Clear cache
        WC_Cache_Helper::invalidate_cache_group('taxes');

        return [
            'success' => true,
            'message' => __('Tax rate deleted successfully', 'acf-rest-api'),
            'id'      => $rate_id,
        ];
    }

    /**
     * Batch operations for tax rates
     *
     * @param array $data Batch data with create, update, delete arrays
     * @return array
     */
    public function batch($data) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $results = [
            'created' => [],
            'updated' => [],
            'deleted' => [],
            'errors'  => [],
        ];

        // Process creates
        if (!empty($data['create']) && is_array($data['create'])) {
            foreach ($data['create'] as $index => $item) {
                $result = $this->create_rate($item);
                if ($result['success']) {
                    $results['created'][] = $result['rate'];
                } else {
                    $results['errors'][] = [
                        'operation' => 'create',
                        'index'     => $index,
                        'message'   => $result['message'],
                    ];
                }
            }
        }

        // Process updates
        if (!empty($data['update']) && is_array($data['update'])) {
            foreach ($data['update'] as $index => $item) {
                if (empty($item['id'])) {
                    $results['errors'][] = [
                        'operation' => 'update',
                        'index'     => $index,
                        'message'   => __('Missing rate ID', 'acf-rest-api'),
                    ];
                    continue;
                }
                $result = $this->update_rate($item['id'], $item);
                if ($result['success']) {
                    $results['updated'][] = $result['rate'];
                } else {
                    $results['errors'][] = [
                        'operation' => 'update',
                        'index'     => $index,
                        'id'        => $item['id'],
                        'message'   => $result['message'],
                    ];
                }
            }
        }

        // Process deletes
        if (!empty($data['delete']) && is_array($data['delete'])) {
            foreach ($data['delete'] as $index => $rate_id) {
                $result = $this->delete_rate($rate_id);
                if ($result['success']) {
                    $results['deleted'][] = $rate_id;
                } else {
                    $results['errors'][] = [
                        'operation' => 'delete',
                        'index'     => $index,
                        'id'        => $rate_id,
                        'message'   => $result['message'],
                    ];
                }
            }
        }

        return [
            'success' => empty($results['errors']),
            'data'    => $results,
        ];
    }

    /**
     * Import tax rates from CSV data
     *
     * @param string $csv_content CSV content
     * @param array  $options     Import options
     * @return array
     */
    public function import_csv($csv_content, $options = []) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $defaults = [
            'update_existing' => false,
            'delete_existing' => false,
            'tax_class'       => '',
        ];
        $options = wp_parse_args($options, $defaults);

        // Parse CSV
        $lines = $this->parse_csv($csv_content);

        if (empty($lines)) {
            return [
                'success' => false,
                'message' => __('No valid data found in CSV', 'acf-rest-api'),
            ];
        }

        // Delete existing rates if requested
        if ($options['delete_existing']) {
            $this->delete_all_rates($options['tax_class']);
        }

        $results = [
            'imported' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        foreach ($lines as $index => $row) {
            $row_result = $this->import_row($row, $index + 2, $options); // +2 for 1-based index and header row

            if ($row_result['success']) {
                if ($row_result['action'] === 'created') {
                    $results['imported']++;
                } elseif ($row_result['action'] === 'updated') {
                    $results['updated']++;
                } else {
                    $results['skipped']++;
                }
            } else {
                $results['errors'][] = $row_result['error'];
            }
        }

        // Clear cache
        WC_Cache_Helper::invalidate_cache_group('taxes');

        return [
            'success'  => empty($results['errors']),
            'data'     => $results,
            'message'  => sprintf(
                __('Import completed. Imported: %d, Updated: %d, Skipped: %d, Errors: %d', 'acf-rest-api'),
                $results['imported'],
                $results['updated'],
                $results['skipped'],
                count($results['errors'])
            ),
        ];
    }

    /**
     * Export tax rates to CSV format
     *
     * @param string $tax_class Tax class to export (empty for all)
     * @return array
     */
    public function export_csv($tax_class = null) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $args = [
            'per_page' => 10000, // Get all rates
            'page'     => 1,
        ];

        if ($tax_class !== null) {
            $args['tax_class'] = $tax_class;
        }

        $result = $this->get_rates($args);

        if (!$result['success']) {
            return $result;
        }

        // Build CSV content
        $csv_lines = [];

        // Header row
        $csv_lines[] = implode(',', self::CSV_COLUMNS);

        // Data rows
        foreach ($result['rates'] as $rate) {
            $row = [
                $rate['country'],
                $rate['state'],
                $rate['postcode'],
                $rate['city'],
                $rate['rate'],
                $this->escape_csv_value($rate['name']),
                $rate['priority'],
                $rate['compound'] ? '1' : '0',
                $rate['shipping'] ? '1' : '0',
                $rate['class'] === 'standard' ? '' : $rate['class'],
            ];
            $csv_lines[] = implode(',', $row);
        }

        $csv_content = implode("\n", $csv_lines);

        return [
            'success'     => true,
            'csv_content' => $csv_content,
            'total'       => $result['total'],
            'filename'    => 'tax_rates_' . ($tax_class ?: 'all') . '_' . date('Y-m-d') . '.csv',
        ];
    }

    /**
     * Get available tax classes
     *
     * @return array
     */
    public function get_tax_classes() {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $classes = WC_Tax::get_tax_classes();
        $result = [
            [
                'slug' => 'standard',
                'name' => __('Standard', 'woocommerce'),
            ],
        ];

        foreach ($classes as $class) {
            $result[] = [
                'slug' => sanitize_title($class),
                'name' => $class,
            ];
        }

        return [
            'success' => true,
            'classes' => $result,
        ];
    }

    /**
     * Delete all tax rates for a specific class
     *
     * @param string $tax_class Tax class (empty for standard)
     * @return int Number of deleted rates
     */
    public function delete_all_rates($tax_class = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'woocommerce_tax_rates';
        $locations_table = $wpdb->prefix . 'woocommerce_tax_rate_locations';

        // Get all rate IDs for this class
        $rate_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT tax_rate_id FROM {$table_name} WHERE tax_rate_class = %s",
            $tax_class === 'standard' ? '' : $tax_class
        ));

        if (empty($rate_ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($rate_ids), '%d'));

        // Delete locations
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$locations_table} WHERE tax_rate_id IN ({$placeholders})",
            $rate_ids
        ));

        // Delete rates
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE tax_rate_id IN ({$placeholders})",
            $rate_ids
        ));

        // Clear cache
        WC_Cache_Helper::invalidate_cache_group('taxes');

        return $deleted;
    }

    /**
     * Parse CSV content into array of rows
     *
     * @param string $csv_content CSV content
     * @return array
     */
    private function parse_csv($csv_content) {
        $lines = [];
        $rows = array_map('trim', explode("\n", $csv_content));

        // Remove empty rows and BOM
        $rows = array_filter($rows);
        if (empty($rows)) {
            return [];
        }

        // Remove BOM if present
        $rows[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0]);

        // Check if first row is header
        $first_row = str_getcsv($rows[0]);
        $is_header = in_array(strtolower(trim($first_row[0])), ['country', 'country code', 'country_code']);

        $start_index = $is_header ? 1 : 0;

        for ($i = $start_index; $i < count($rows); $i++) {
            $row = str_getcsv($rows[$i]);

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Ensure minimum columns
            while (count($row) < 10) {
                $row[] = '';
            }

            $lines[] = [
                'country'   => isset($row[0]) ? strtoupper(trim($row[0])) : '',
                'state'     => isset($row[1]) ? strtoupper(trim($row[1])) : '',
                'postcode'  => isset($row[2]) ? trim($row[2]) : '',
                'city'      => isset($row[3]) ? trim($row[3]) : '',
                'rate'      => isset($row[4]) ? trim($row[4]) : '0',
                'name'      => isset($row[5]) ? trim($row[5]) : '',
                'priority'  => isset($row[6]) ? absint($row[6]) : 1,
                'compound'  => isset($row[7]) ? (bool) absint($row[7]) : false,
                'shipping'  => isset($row[8]) ? (bool) absint($row[8]) : true,
                'class'     => isset($row[9]) ? sanitize_title(trim($row[9])) : '',
            ];
        }

        return $lines;
    }

    /**
     * Import a single row from CSV
     *
     * @param array $row     Row data
     * @param int   $line_no Line number for error reporting
     * @param array $options Import options
     * @return array
     */
    private function import_row($row, $line_no, $options) {
        // Validate required fields
        if (empty($row['rate']) || !is_numeric($row['rate'])) {
            return [
                'success' => false,
                'error'   => sprintf(__('Line %d: Invalid or missing rate', 'acf-rest-api'), $line_no),
            ];
        }

        // Override tax class if specified in options
        if (!empty($options['tax_class'])) {
            $row['class'] = $options['tax_class'] === 'standard' ? '' : $options['tax_class'];
        }

        // Check for existing rate if update_existing is true
        if ($options['update_existing']) {
            $existing_id = $this->find_existing_rate($row);
            if ($existing_id) {
                $result = $this->update_rate($existing_id, $row);
                return [
                    'success' => $result['success'],
                    'action'  => 'updated',
                    'rate_id' => $existing_id,
                    'error'   => $result['success'] ? null : sprintf(__('Line %d: %s', 'acf-rest-api'), $line_no, $result['message']),
                ];
            }
        }

        // Create new rate
        $result = $this->create_rate($row);

        return [
            'success' => $result['success'],
            'action'  => 'created',
            'rate_id' => $result['success'] ? $result['rate']['id'] : null,
            'error'   => $result['success'] ? null : sprintf(__('Line %d: %s', 'acf-rest-api'), $line_no, $result['message']),
        ];
    }

    /**
     * Find existing tax rate by country, state, postcode, city, and class
     *
     * @param array $data Rate data to search for
     * @return int|null Rate ID or null if not found
     */
    private function find_existing_rate($data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'woocommerce_tax_rates';

        $rate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT tax_rate_id FROM {$table_name} 
             WHERE tax_rate_country = %s 
             AND tax_rate_state = %s 
             AND tax_rate_name = %s 
             AND tax_rate_priority = %d 
             AND tax_rate_class = %s 
             LIMIT 1",
            $data['country'] ?: '',
            $data['state'] ?: '',
            $data['name'] ?: '',
            $data['priority'] ?: 1,
            $data['class'] ?: ''
        ));

        return $rate_id ? (int) $rate_id : null;
    }

    /**
     * Prepare tax rate data for database insertion/update
     *
     * @param array $data     Input data
     * @param array $existing Existing data for update (optional)
     * @return array
     */
    private function prepare_tax_rate_data($data, $existing = []) {
        $tax_rate = [];

        // Country
        if (isset($data['country'])) {
            $tax_rate['tax_rate_country'] = strtoupper(sanitize_text_field($data['country']));
        } elseif (isset($existing['tax_rate_country'])) {
            $tax_rate['tax_rate_country'] = $existing['tax_rate_country'];
        } else {
            $tax_rate['tax_rate_country'] = '';
        }

        // State
        if (isset($data['state'])) {
            $tax_rate['tax_rate_state'] = strtoupper(sanitize_text_field($data['state']));
        } elseif (isset($existing['tax_rate_state'])) {
            $tax_rate['tax_rate_state'] = $existing['tax_rate_state'];
        } else {
            $tax_rate['tax_rate_state'] = '';
        }

        // Rate
        if (isset($data['rate'])) {
            $tax_rate['tax_rate'] = wc_format_decimal($data['rate']);
        } elseif (isset($existing['tax_rate'])) {
            $tax_rate['tax_rate'] = $existing['tax_rate'];
        } else {
            $tax_rate['tax_rate'] = '0';
        }

        // Name
        if (isset($data['name'])) {
            $tax_rate['tax_rate_name'] = sanitize_text_field($data['name']);
        } elseif (isset($existing['tax_rate_name'])) {
            $tax_rate['tax_rate_name'] = $existing['tax_rate_name'];
        } else {
            $tax_rate['tax_rate_name'] = '';
        }

        // Priority
        if (isset($data['priority'])) {
            $tax_rate['tax_rate_priority'] = absint($data['priority']);
        } elseif (isset($existing['tax_rate_priority'])) {
            $tax_rate['tax_rate_priority'] = $existing['tax_rate_priority'];
        } else {
            $tax_rate['tax_rate_priority'] = 1;
        }

        // Compound
        if (isset($data['compound'])) {
            $tax_rate['tax_rate_compound'] = $data['compound'] ? 1 : 0;
        } elseif (isset($existing['tax_rate_compound'])) {
            $tax_rate['tax_rate_compound'] = $existing['tax_rate_compound'];
        } else {
            $tax_rate['tax_rate_compound'] = 0;
        }

        // Shipping
        if (isset($data['shipping'])) {
            $tax_rate['tax_rate_shipping'] = $data['shipping'] ? 1 : 0;
        } elseif (isset($existing['tax_rate_shipping'])) {
            $tax_rate['tax_rate_shipping'] = $existing['tax_rate_shipping'];
        } else {
            $tax_rate['tax_rate_shipping'] = 1;
        }

        // Tax class
        if (isset($data['class'])) {
            $tax_rate['tax_rate_class'] = $data['class'] === 'standard' ? '' : sanitize_title($data['class']);
        } elseif (isset($existing['tax_rate_class'])) {
            $tax_rate['tax_rate_class'] = $existing['tax_rate_class'];
        } else {
            $tax_rate['tax_rate_class'] = '';
        }

        // Order
        if (isset($data['order'])) {
            $tax_rate['tax_rate_order'] = absint($data['order']);
        } elseif (isset($existing['tax_rate_order'])) {
            $tax_rate['tax_rate_order'] = $existing['tax_rate_order'];
        }

        return $tax_rate;
    }

    /**
     * Escape value for CSV
     *
     * @param string $value Value to escape
     * @return string
     */
    private function escape_csv_value($value) {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Check read permission
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_read_permission($request) {
        if (!current_user_can('manage_woocommerce')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to view tax rates.', 'acf-rest-api'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * Check write permission
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_write_permission($request) {
        if (!current_user_can('manage_woocommerce')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to manage tax rates.', 'acf-rest-api'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * REST API - Get all tax rates
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_rates_handler($request) {
        $args = [
            'tax_class' => $request->get_param('class'),
            'page'      => $request->get_param('page') ?: 1,
            'per_page'  => $request->get_param('per_page') ?: 100,
            'orderby'   => $request->get_param('orderby') ?: 'tax_rate_order',
            'order'     => $request->get_param('order') ?: 'ASC',
        ];

        $result = $this->get_rates($args);

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'woocommerce_not_active',
            ], 400);
        }

        $response = new WP_REST_Response([
            'success' => true,
            'data'    => $result['rates'],
            'meta'    => [
                'total'       => $result['total'],
                'page'        => $result['page'],
                'per_page'    => $result['per_page'],
                'total_pages' => $result['total_pages'],
            ],
        ], 200);

        // Add pagination headers
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['total_pages']);

        return $response;
    }

    /**
     * REST API - Get single tax rate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_rate_handler($request) {
        $rate_id = $request->get_param('id');
        $result = $this->get_rate($rate_id);

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'not_found',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result['rate'],
        ], 200);
    }

    /**
     * REST API - Create tax rate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_create_rate_handler($request) {
        $data = $request->get_json_params();

        if (empty($data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No data provided', 'acf-rest-api'),
                'code'    => 'missing_data',
            ], 400);
        }

        $result = $this->create_rate($data);

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'create_failed',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result['rate'],
            'message' => __('Tax rate created successfully', 'acf-rest-api'),
        ], 201);
    }

    /**
     * REST API - Update tax rate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_update_rate_handler($request) {
        $rate_id = $request->get_param('id');
        $data = $request->get_json_params();

        if (empty($data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No data provided', 'acf-rest-api'),
                'code'    => 'missing_data',
            ], 400);
        }

        $result = $this->update_rate($rate_id, $data);

        if (!$result['success']) {
            $status = strpos($result['message'], 'not found') !== false ? 404 : 400;
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'update_failed',
            ], $status);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result['rate'],
            'message' => __('Tax rate updated successfully', 'acf-rest-api'),
        ], 200);
    }

    /**
     * REST API - Delete tax rate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_delete_rate_handler($request) {
        $rate_id = $request->get_param('id');
        $result = $this->delete_rate($rate_id);

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'delete_failed',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => $result['message'],
            'id'      => $rate_id,
        ], 200);
    }

    /**
     * REST API - Batch operations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_batch_handler($request) {
        $data = $request->get_json_params();

        if (empty($data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No data provided', 'acf-rest-api'),
                'code'    => 'missing_data',
            ], 400);
        }

        $result = $this->batch($data);
        $status = $result['success'] ? 200 : 207;

        return new WP_REST_Response([
            'success' => $result['success'],
            'data'    => $result['data'],
        ], $status);
    }

    /**
     * REST API - Import CSV
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_import_handler($request) {
        $csv_content = $request->get_param('csv_content');
        $files = $request->get_file_params();

        // Check for file upload
        if (!empty($files['file'])) {
            $file = $files['file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('File upload error', 'acf-rest-api'),
                    'code'    => 'upload_error',
                ], 400);
            }

            $csv_content = file_get_contents($file['tmp_name']);
        }

        if (empty($csv_content)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No CSV content provided', 'acf-rest-api'),
                'code'    => 'missing_data',
            ], 400);
        }

        $options = [
            'update_existing' => (bool) $request->get_param('update_existing'),
            'delete_existing' => (bool) $request->get_param('delete_existing'),
            'tax_class'       => $request->get_param('class') ?: '',
        ];

        $result = $this->import_csv($csv_content, $options);
        $status = $result['success'] ? 200 : 207;

        return new WP_REST_Response([
            'success' => $result['success'],
            'data'    => $result['data'],
            'message' => $result['message'],
        ], $status);
    }

    /**
     * REST API - Export CSV
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_export_handler($request) {
        $tax_class = $request->get_param('class');
        $result = $this->export_csv($tax_class);

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'export_failed',
            ], 400);
        }

        // Check if direct download is requested
        if ($request->get_param('download')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            echo $result['csv_content'];
            exit;
        }

        return new WP_REST_Response([
            'success'     => true,
            'csv_content' => $result['csv_content'],
            'filename'    => $result['filename'],
            'total'       => $result['total'],
        ], 200);
    }

    /**
     * REST API - Get tax classes
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_classes_handler($request) {
        $result = $this->get_tax_classes();

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'woocommerce_not_active',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result['classes'],
        ], 200);
    }

    /**
     * REST API - Delete all rates for a class
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_delete_all_handler($request) {
        $tax_class = $request->get_param('class') ?: '';
        $deleted = $this->delete_all_rates($tax_class);

        return new WP_REST_Response([
            'success' => true,
            'deleted' => $deleted,
            'message' => sprintf(__('%d tax rates deleted', 'acf-rest-api'), $deleted),
        ], 200);
    }
}