<?php
/**
 * WooCommerce Tax Options Settings Handler
 *
 * Handles WooCommerce tax options configuration
 * via REST API endpoints.
 *
 * Options managed:
 * - woocommerce_prices_include_tax (yes/no)
 * - woocommerce_tax_based_on (shipping/billing/base)
 * - woocommerce_shipping_tax_class (inherit/standard/reduced-rate/zero-rate)
 * - woocommerce_tax_round_at_subtotal (yes/no)
 * - woocommerce_tax_classes (text area with tax classes)
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

    /**
     * Valid values for select options
     */
    private $valid_tax_based_on = ['shipping', 'billing', 'base'];
    private $valid_shipping_tax_class = ['inherit', '', 'standard', 'reduced-rate', 'zero-rate'];

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
     * Get all tax options
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

        return [
            'success' => true,
            'options' => [
                'prices_include_tax' => [
                    'value'       => get_option(self::OPTION_PRICES_INCLUDE_TAX, 'no'),
                    'enabled'     => get_option(self::OPTION_PRICES_INCLUDE_TAX, 'no') === 'yes',
                    'option_name' => self::OPTION_PRICES_INCLUDE_TAX,
                    'description' => __('Prices entered with tax', 'acf-rest-api'),
                    'choices'     => [
                        'yes' => __('Yes, I will enter prices inclusive of tax', 'acf-rest-api'),
                        'no'  => __('No, I will enter prices exclusive of tax', 'acf-rest-api'),
                    ],
                ],
                'tax_based_on' => [
                    'value'       => get_option(self::OPTION_TAX_BASED_ON, 'shipping'),
                    'option_name' => self::OPTION_TAX_BASED_ON,
                    'description' => __('Calculate tax based on', 'acf-rest-api'),
                    'choices'     => [
                        'shipping' => __('Customer shipping address', 'acf-rest-api'),
                        'billing'  => __('Customer billing address', 'acf-rest-api'),
                        'base'     => __('Shop base address', 'acf-rest-api'),
                    ],
                ],
                'shipping_tax_class' => [
                    'value'       => get_option(self::OPTION_SHIPPING_TAX_CLASS, 'inherit'),
                    'option_name' => self::OPTION_SHIPPING_TAX_CLASS,
                    'description' => __('Shipping tax class', 'acf-rest-api'),
                    'choices'     => $this->get_shipping_tax_class_choices(),
                ],
                'tax_round_at_subtotal' => [
                    'value'       => get_option(self::OPTION_TAX_ROUND_AT_SUBTOTAL, 'no'),
                    'enabled'     => get_option(self::OPTION_TAX_ROUND_AT_SUBTOTAL, 'no') === 'yes',
                    'option_name' => self::OPTION_TAX_ROUND_AT_SUBTOTAL,
                    'description' => __('Rounding', 'acf-rest-api'),
                ],
                'tax_classes' => [
                    'value'       => get_option(self::OPTION_TAX_CLASSES, "Reduced rate\nZero rate"),
                    'option_name' => self::OPTION_TAX_CLASSES,
                    'description' => __('Additional tax classes', 'acf-rest-api'),
                    'parsed'      => $this->parse_tax_classes(get_option(self::OPTION_TAX_CLASSES, "Reduced rate\nZero rate")),
                ],
            ],
        ];
    }

    /**
     * Get shipping tax class choices including custom tax classes
     *
     * @return array
     */
    private function get_shipping_tax_class_choices() {
        $choices = [
            'inherit'  => __('Shipping tax class based on cart items', 'acf-rest-api'),
            'standard' => __('Standard', 'acf-rest-api'),
        ];

        // Add custom tax classes
        $tax_classes = $this->parse_tax_classes(get_option(self::OPTION_TAX_CLASSES, ''));
        foreach ($tax_classes as $class) {
            $slug = sanitize_title($class);
            $choices[$slug] = $class;
        }

        return $choices;
    }

    /**
     * Parse tax classes from textarea value
     *
     * @param string $value Tax classes string (newline separated)
     * @return array
     */
    private function parse_tax_classes($value) {
        if (empty($value)) {
            return [];
        }

        $classes = array_filter(array_map('trim', explode("\n", $value)));
        return array_values($classes);
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
            if (in_array($value, $this->valid_tax_based_on, true)) {
                if (update_option(self::OPTION_TAX_BASED_ON, $value)) {
                    $updated['tax_based_on'] = $value;
                }
            } else {
                $errors['tax_based_on'] = sprintf(
                    __('Invalid value. Must be one of: %s', 'acf-rest-api'),
                    implode(', ', $this->valid_tax_based_on)
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

        // Update tax_classes
        if (isset($data['tax_classes'])) {
            $value = $data['tax_classes'];
            
            // Handle array input (convert to newline-separated string)
            if (is_array($value)) {
                $value = implode("\n", array_map('sanitize_text_field', $value));
            } else {
                $value = sanitize_textarea_field($value);
            }
            
            if (update_option(self::OPTION_TAX_CLASSES, $value)) {
                $updated['tax_classes'] = $value;
            }
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
