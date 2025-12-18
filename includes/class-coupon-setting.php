<?php
/**
 * WooCommerce Coupon Settings Handler
 *
 * Handles WooCommerce coupon enable/disable functionality
 * via REST API endpoints.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_WC_Coupon_Settings {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * WooCommerce option name
     */
    const OPTION_NAME = 'woocommerce_enable_coupons';

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
     * Get current coupon status
     *
     * @return array
     */
    public function get_status() {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $enabled = get_option(self::OPTION_NAME, 'yes');

        return [
            'success'     => true,
            'enabled'     => $enabled === 'yes',
            'raw_value'   => $enabled,
            'option_name' => self::OPTION_NAME,
        ];
    }

    /**
     * Update coupon status
     *
     * @param bool $enabled Enable or disable coupons
     * @return array
     */
    public function update_status($enabled) {
        if (!$this->is_woocommerce_active()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not active', 'acf-rest-api'),
            ];
        }

        $new_value = $enabled ? 'yes' : 'no';
        $updated = update_option(self::OPTION_NAME, $new_value);
        $current_value = get_option(self::OPTION_NAME);

        return [
            'success'     => true,
            'enabled'     => $current_value === 'yes',
            'raw_value'   => $current_value,
            'was_updated' => $updated,
            'message'     => $enabled
                ? __('Coupons have been enabled.', 'acf-rest-api')
                : __('Coupons have been disabled.', 'acf-rest-api'),
        ];
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
     * REST API GET handler - Get coupon status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_handler($request) {
        $result = $this->get_status();

        if (!$result['success']) {
            return new WP_REST_Response([
                'message' => $result['message'],
                'code'    => 'woocommerce_not_active',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'enabled'     => $result['enabled'],
                'raw_value'   => $result['raw_value'],
                'option_name' => $result['option_name'],
            ],
        ], 200);
    }

    /**
     * REST API POST handler - Update coupon status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_update_handler($request) {
        $enabled = $request->get_param('enabled');

        if ($enabled === null) {
            return new WP_REST_Response([
                'message' => __('Missing required parameter: enabled', 'acf-rest-api'),
                'code'    => 'missing_parameter',
            ], 400);
        }

        $result = $this->update_status($enabled);

        if (!$result['success']) {
            return new WP_REST_Response([
                'message' => $result['message'],
                'code'    => 'woocommerce_not_active',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'enabled'     => $result['enabled'],
                'raw_value'   => $result['raw_value'],
                'was_updated' => $result['was_updated'],
                'message'     => $result['message'],
            ],
        ], 200);
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