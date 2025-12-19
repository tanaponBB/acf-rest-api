<?php
/**
 * WooCommerce Tax Options Settings Handler
 *
 * Dynamically handles WooCommerce tax options configuration
 * via REST API endpoints by fetching live data from WooCommerce.
 *
 * @package ACF_REST_API
 * @since 1.2.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_WC_Tax_Options_Settings {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * WooCommerce option names
     */
    const OPTION_PRICES_INCLUDE_TAX = 'woocommerce_prices_include_tax';
    const OPTION_TAX_BASED_ON = 'woocommerce_tax_based_on';
    const OPTION_SHIPPING_TAX_CLASS = 'woocommerce_shipping_tax_class';
    const OPTION_TAX_ROUND_AT_SUBTOTAL = 'woocommerce_tax_round_at_subtotal';
    const OPTION_TAX_CLASSES = 'woocommerce_tax_classes';
    const OPTION_TAX_DISPLAY_SHOP = 'woocommerce_tax_display_shop';
    const OPTION_TAX_DISPLAY_CART = 'woocommerce_tax_display_cart';
    const OPTION_TAX_TOTAL_DISPLAY = 'woocommerce_tax_total_display';

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
        return class_exists('WooCommerce');
    }

    /**
     * Get all available tax classes from WooCommerce
     *
     * @return array
     */
    private function get_wc_tax_classes() {
        if (!$this->is_woocommerce_active()) {
            return [];
        }

        // Get tax classes using WooCommerce helper function
        $tax_classes = WC_Tax::get_tax_classes();
        
        $classes = [
            '' => __('Standard', 'woocommerce')
        ];
        
        if (!empty($tax_classes)) {
            foreach ($tax_classes as $class) {
                $classes[sanitize_title($class)] = $class;
            }
        }
        
        return $classes;
    }

    /**
     * Get valid values for tax_based_on from WooCommerce
     *
     * @return array
     */
    private function get_tax_based_on_options() {
        return [
            'shipping' => __('Customer shipping address', 'woocommerce'),
            'billing'  => __('Customer billing address', 'woocommerce'),
            'base'     => __('Shop base address', 'woocommerce'),
        ];
    }

    /**
     * Get shipping tax class choices dynamically from WooCommerce
     *
     * @return array
     */
    private function get_shipping_tax_class_choices() {
        $choices = [
            '' => __('Shipping tax class based on cart items', 'woocommerce'),
        ];

        // Get all tax classes from WooCommerce
        $tax_classes = $this->get_wc_tax_classes();
        
        foreach ($tax_classes as $slug => $label) {
            $choices[$slug] = $label;
        }

        return $choices;
    }

    /**
     * Get display options from WooCommerce
     *
     * @return array
     */
    private function get_tax_display_options() {
        return [
            'incl' => __('Including tax', 'woocommerce'),
            'excl' => __('Excluding tax', 'woocommerce'),
        ];
    }

    /**
     * Get tax total display options
     *
     * @return array
     */
    private function get_tax_total_display_options() {
        return [
            'single'   => __('As a single total', 'woocommerce'),
            'itemized' => __('Itemized', 'woocommerce'),
        ];
    }

    /**
     * Parse tax classes from WooCommerce
     *
     * @return array
     */
    private function parse_tax_classes() {
        if (!$this->is_woocommerce_active()) {
            return [];
        }

        $tax_classes = WC_Tax::get_tax_classes();
        return !empty($tax_classes) ? $tax_classes : [];
    }

    /**
     * Get all tax options dynamically
     *
     * @return array
     */
    public function get_options() {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        // Get current values from WooCommerce
        $prices_include_tax = get_option(self::OPTION_PRICES_INCLUDE_TAX, 'no');
        $tax_based_on = get_option(self::OPTION_TAX_BASED_ON, 'shipping');
        $shipping_tax_class = get_option(self::OPTION_SHIPPING_TAX_CLASS, '');
        $tax_round_at_subtotal = get_option(self::OPTION_TAX_ROUND_AT_SUBTOTAL, 'no');
        $tax_display_shop = get_option(self::OPTION_TAX_DISPLAY_SHOP, 'excl');
        $tax_display_cart = get_option(self::OPTION_TAX_DISPLAY_CART, 'excl');
        $tax_total_display = get_option(self::OPTION_TAX_TOTAL_DISPLAY, 'itemized');

        return [
            'success' => true,
            'options' => [
                'prices_include_tax' => [
                    'value'       => $prices_include_tax,
                    'enabled'     => $prices_include_tax === 'yes',
                    'option_name' => self::OPTION_PRICES_INCLUDE_TAX,
                    'description' => __('Prices entered with tax', 'woocommerce'),
                    'choices'     => [
                        'yes' => __('Yes, I will enter prices inclusive of tax', 'woocommerce'),
                        'no'  => __('No, I will enter prices exclusive of tax', 'woocommerce'),
                    ],
                ],
                'tax_based_on' => [
                    'value'       => $tax_based_on,
                    'option_name' => self::OPTION_TAX_BASED_ON,
                    'description' => __('Calculate tax based on', 'woocommerce'),
                    'choices'     => $this->get_tax_based_on_options(),
                ],
                'shipping_tax_class' => [
                    'value'       => $shipping_tax_class,
                    'option_name' => self::OPTION_SHIPPING_TAX_CLASS,
                    'description' => __('Shipping tax class', 'woocommerce'),
                    'choices'     => $this->get_shipping_tax_class_choices(),
                ],
                'tax_round_at_subtotal' => [
                    'value'       => $tax_round_at_subtotal,
                    'enabled'     => $tax_round_at_subtotal === 'yes',
                    'option_name' => self::OPTION_TAX_ROUND_AT_SUBTOTAL,
                    'description' => __('Round tax at subtotal level, instead of rounding per line', 'woocommerce'),
                    'choices'     => [
                        'yes' => __('Yes', 'woocommerce'),
                        'no'  => __('No', 'woocommerce'),
                    ],
                ],
                'tax_display_shop' => [
                    'value'       => $tax_display_shop,
                    'option_name' => self::OPTION_TAX_DISPLAY_SHOP,
                    'description' => __('Display prices in the shop', 'woocommerce'),
                    'choices'     => $this->get_tax_display_options(),
                ],
                'tax_display_cart' => [
                    'value'       => $tax_display_cart,
                    'option_name' => self::OPTION_TAX_DISPLAY_CART,
                    'description' => __('Display prices during cart and checkout', 'woocommerce'),
                    'choices'     => $this->get_tax_display_options(),
                ],
                'tax_total_display' => [
                    'value'       => $tax_total_display,
                    'option_name' => self::OPTION_TAX_TOTAL_DISPLAY,
                    'description' => __('Display tax totals', 'woocommerce'),
                    'choices'     => $this->get_tax_total_display_options(),
                ],
                'tax_classes' => [
                    'value'       => $this->parse_tax_classes(),
                    'option_name' => self::OPTION_TAX_CLASSES,
                    'description' => __('Additional tax classes', 'woocommerce'),
                    'available_classes' => $this->get_wc_tax_classes(),
                ],
            ],
            'meta' => [
                'wc_version' => defined('WC_VERSION') ? WC_VERSION : null,
                'tax_enabled' => wc_tax_enabled(),
                'store_address' => [
                    'country'  => WC()->countries->get_base_country(),
                    'state'    => WC()->countries->get_base_state(),
                    'postcode' => WC()->countries->get_base_postcode(),
                    'city'     => WC()->countries->get_base_city(),
                ],
            ],
        ];
    }

    /**
     * Update tax options
     *
     * @param array $data Options to update
     * @return array
     */
    public function update_options($data) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $updated = [];
        $errors = [];

        // Update prices_include_tax
        if (isset($data['prices_include_tax'])) {
            $value = $this->sanitize_yes_no($data['prices_include_tax']);
            if (update_option(self::OPTION_PRICES_INCLUDE_TAX, $value)) {
                $updated['prices_include_tax'] = $value;
            }
        }

        // Update tax_based_on
        if (isset($data['tax_based_on'])) {
            $value = sanitize_text_field($data['tax_based_on']);
            $valid_options = array_keys($this->get_tax_based_on_options());
            
            if (in_array($value, $valid_options, true)) {
                if (update_option(self::OPTION_TAX_BASED_ON, $value)) {
                    $updated['tax_based_on'] = $value;
                }
            } else {
                $errors['tax_based_on'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $valid_options)
                );
            }
        }

        // Update shipping_tax_class
        if (isset($data['shipping_tax_class'])) {
            $value = sanitize_text_field($data['shipping_tax_class']);
            $valid_classes = array_keys($this->get_shipping_tax_class_choices());
            
            if (in_array($value, $valid_classes, true)) {
                if (update_option(self::OPTION_SHIPPING_TAX_CLASS, $value)) {
                    $updated['shipping_tax_class'] = $value;
                }
            } else {
                $errors['shipping_tax_class'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $valid_classes)
                );
            }
        }

        // Update tax_round_at_subtotal
        if (isset($data['tax_round_at_subtotal'])) {
            $value = $this->sanitize_yes_no($data['tax_round_at_subtotal']);
            if (update_option(self::OPTION_TAX_ROUND_AT_SUBTOTAL, $value)) {
                $updated['tax_round_at_subtotal'] = $value;
            }
        }

        // Update tax_display_shop
        if (isset($data['tax_display_shop'])) {
            $value = sanitize_text_field($data['tax_display_shop']);
            $valid_options = array_keys($this->get_tax_display_options());
            
            if (in_array($value, $valid_options, true)) {
                if (update_option(self::OPTION_TAX_DISPLAY_SHOP, $value)) {
                    $updated['tax_display_shop'] = $value;
                }
            } else {
                $errors['tax_display_shop'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $valid_options)
                );
            }
        }

        // Update tax_display_cart
        if (isset($data['tax_display_cart'])) {
            $value = sanitize_text_field($data['tax_display_cart']);
            $valid_options = array_keys($this->get_tax_display_options());
            
            if (in_array($value, $valid_options, true)) {
                if (update_option(self::OPTION_TAX_DISPLAY_CART, $value)) {
                    $updated['tax_display_cart'] = $value;
                }
            } else {
                $errors['tax_display_cart'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $valid_options)
                );
            }
        }

        // Update tax_total_display
        if (isset($data['tax_total_display'])) {
            $value = sanitize_text_field($data['tax_total_display']);
            $valid_options = array_keys($this->get_tax_total_display_options());
            
            if (in_array($value, $valid_options, true)) {
                if (update_option(self::OPTION_TAX_TOTAL_DISPLAY, $value)) {
                    $updated['tax_total_display'] = $value;
                }
            } else {
                $errors['tax_total_display'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $valid_options)
                );
            }
        }

        // Update tax_classes - Note: This is complex as it requires updating tax rates
        if (isset($data['tax_classes'])) {
            // WooCommerce stores additional tax classes as newline-separated string
            if (is_array($data['tax_classes'])) {
                $classes = array_map('sanitize_text_field', $data['tax_classes']);
                $value = implode("\n", $classes);
            } else {
                $value = sanitize_textarea_field($data['tax_classes']);
            }
            
            if (update_option(self::OPTION_TAX_CLASSES, $value)) {
                $updated['tax_classes'] = $value;
            }
        }

        // Clear WooCommerce cache after updating
        if (!empty($updated)) {
            WC_Cache_Helper::invalidate_cache_group('taxes');
            delete_transient('wc_tax_rates');
        }

        return [
            'success'     => empty($errors),
            'updated'     => $updated,
            'errors'      => $errors,
            'message'     => empty($errors)
                ? __('Tax options have been updated.', 'acf-rest-api')
                : __('Some options could not be updated.', 'acf-rest-api'),
        ];
    }

    /**
     * Sanitize yes/no value
     *
     * @param mixed $value Input value
     * @return string 'yes' or 'no'
     */
    private function sanitize_yes_no($value) {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            if (in_array($value, ['yes', 'true', '1'], true)) {
                return 'yes';
            }
        }
        
        if (is_numeric($value) && (int) $value === 1) {
            return 'yes';
        }
        
        return 'no';
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
                __('You do not have permission to view WooCommerce settings.', 'acf-rest-api'),
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
                __('You do not have permission to update WooCommerce settings.', 'acf-rest-api'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * REST API GET handler - Get tax options
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_handler($request) {
        $result = $this->get_options();

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'code'    => 'woocommerce_not_active',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result['options'],
            'meta'    => $result['meta'],
        ], 200);
    }

    /**
     * REST API POST handler - Update tax options
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_update_handler($request) {
        $data = $request->get_json_params();

        if (empty($data)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No data provided', 'acf-rest-api'),
                'code'    => 'missing_data',
            ], 400);
        }

        $result = $this->update_options($data);

        if (!$result['success'] && empty($result['updated'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result['message'],
                'errors'  => $result['errors'],
                'code'    => isset($result['errors']) ? 'validation_error' : 'woocommerce_not_active',
            ], 400);
        }

        $status_code = empty($result['errors']) ? 200 : 207;

        return new WP_REST_Response([
            'success' => $result['success'],
            'data'    => [
                'updated' => $result['updated'],
                'errors'  => $result['errors'],
                'message' => $result['message'],
            ],
        ], $status_code);
    }

    /**
     * Get REST API endpoint handlers
     *
     * @return array
     */
    public function get_rest_handlers() {
        return [
            'get'  => [$this, 'rest_get_handler'],
            'post' => [$this, 'rest_update_handler'],
        ];
    }
}