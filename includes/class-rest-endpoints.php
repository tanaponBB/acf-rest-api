<?php
/**
 * REST API Endpoints Handler
 *
 * Registers and manages all REST API endpoints for the plugin.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_Endpoints {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * REST namespace for ACF options
     */
    const REST_NAMESPACE = 'options';

    /**
     * REST namespace for WooCommerce settings
     */
    const WC_NAMESPACE = 'woocommerce-ext/v1';

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
     * Register all REST routes
     */
    public function register_routes() {
        // Options routes - /options/all
        $this->register_options_routes();
        
        // GTM Tracking routes - /options/track
        $this->register_gtm_routes();

        // WooCommerce Coupon routes - /woocommerce-ext/v1/coupons
        $this->register_wc_coupon_routes();

        // WooCommerce Tax routes - /woocommerce-ext/v1/taxes
        $this->register_wc_tax_routes();

        // WooCommerce Tax Options routes - /woocommerce-ext/v1/taxes-options
        $this->register_wc_tax_options_routes();
    }

    /**
     * Register options routes
     */
    private function register_options_routes() {
        $options_api = ACF_REST_Options_API::get_instance();

        // GET /options/all - Get all ACF option fields
        register_rest_route(self::REST_NAMESPACE, '/all', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$options_api, 'get_all_options'],
            'permission_callback' => [$options_api, 'check_read_permission'],
            'args'                => $this->get_options_get_args(),
        ]);

        // POST /options/all - Update ACF option fields
        register_rest_route(self::REST_NAMESPACE, '/all', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$options_api, 'update_options'],
            'permission_callback' => [$options_api, 'check_write_permission'],
            'args'                => $this->get_options_post_args(),
        ]);
    }

    /**
     * Register GTM tracking routes
     */
    private function register_gtm_routes() {
        $gtm_tracking = ACF_REST_GTM_Tracking::get_instance();

        // GET /options/track - Get GTM tracking fields
        register_rest_route(self::REST_NAMESPACE, '/track', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$gtm_tracking, 'rest_get_handler'],
            'permission_callback' => '__return_true',
            'args'                => $this->get_track_get_args(),
        ]);

        // POST /options/track - Update GTM tracking fields
        register_rest_route(self::REST_NAMESPACE, '/track', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$gtm_tracking, 'rest_update_handler'],
            'permission_callback' => [$this, 'check_track_write_permission'],
            'args'                => $this->get_track_post_args(),
        ]);
    }

    /**
     * Register WooCommerce coupon routes
     */
    private function register_wc_coupon_routes() {
        if (!class_exists('ACF_REST_WC_Coupon_Settings')) {
            return;
        }

        $wc_coupon = ACF_REST_WC_Coupon_Settings::get_instance();

        // GET /woocommerce-ext/v1/coupons - Get coupon status
        register_rest_route(self::WC_NAMESPACE, '/coupons', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_coupon, 'rest_get_handler'],
            'permission_callback' => [$wc_coupon, 'check_read_permission'],
            'args'                => $this->get_wc_coupon_get_args(),
        ]);

        // POST /woocommerce-ext/v1/coupons - Update coupon status
        register_rest_route(self::WC_NAMESPACE, '/coupons', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_coupon, 'rest_update_handler'],
            'permission_callback' => [$wc_coupon, 'check_write_permission'],
            'args'                => $this->get_wc_coupon_post_args(),
        ]);
    }

    /**
     * Register WooCommerce tax routes
     */
    private function register_wc_tax_routes() {
        if (!class_exists('ACF_REST_WC_Tax_Settings')) {
            return;
        }

        $wc_tax = ACF_REST_WC_Tax_Settings::get_instance();

        // GET /woocommerce-ext/v1/taxes - Get tax status
        register_rest_route(self::WC_NAMESPACE, '/taxes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax, 'rest_get_handler'],
            'permission_callback' => [$wc_tax, 'check_read_permission'],
            'args'                => $this->get_wc_tax_get_args(),
        ]);

        // POST /woocommerce-ext/v1/taxes - Update tax status
        register_rest_route(self::WC_NAMESPACE, '/taxes', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_tax, 'rest_update_handler'],
            'permission_callback' => [$wc_tax, 'check_write_permission'],
            'args'                => $this->get_wc_tax_post_args(),
        ]);
    }

    /**
     * Register WooCommerce tax options routes
     */
    private function register_wc_tax_options_routes() {
        if (!class_exists('ACF_REST_WC_Tax_Options_Settings')) {
            return;
        }

        $wc_tax_options = ACF_REST_WC_Tax_Options_Settings::get_instance();

        // GET /woocommerce-ext/v1/taxes-options - Get tax options
        register_rest_route(self::WC_NAMESPACE, '/taxes-options', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax_options, 'rest_get_handler'],
            'permission_callback' => [$wc_tax_options, 'check_read_permission'],
            'args'                => $this->get_wc_tax_options_get_args(),
        ]);

        // POST /woocommerce-ext/v1/taxes-options - Update tax options
        register_rest_route(self::WC_NAMESPACE, '/taxes-options', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_tax_options, 'rest_update_handler'],
            'permission_callback' => [$wc_tax_options, 'check_write_permission'],
            'args'                => $this->get_wc_tax_options_post_args(),
        ]);
    }

    /**
     * Get arguments for OPTIONS GET request
     *
     * @return array
     */
    private function get_options_get_args() {
        return [
            'include_choices' => [
                'description' => __('Include field choices in response', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => true,
            ],
        ];
    }

    /**
     * Get arguments for OPTIONS POST request
     *
     * @return array
     */
    private function get_options_post_args() {
        return [
            // Dynamic - any field name can be passed
        ];
    }

    /**
     * Get arguments for TRACK GET request
     *
     * @return array
     */
    private function get_track_get_args() {
        return [];
    }

    /**
     * Get arguments for TRACK POST request
     *
     * @return array
     */
    private function get_track_post_args() {
        return [];
    }

    /**
     * Get arguments for WC Coupon GET request
     *
     * @return array
     */
    private function get_wc_coupon_get_args() {
        return [];
    }

    /**
     * Get arguments for WC Coupon POST request
     *
     * @return array
     */
    private function get_wc_coupon_post_args() {
        return [
            'enabled' => [
                'description'       => __('Enable or disable WooCommerce coupons', 'acf-rest-api'),
                'type'              => 'boolean',
                'required'          => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
        ];
    }

    /**
     * Get arguments for WC Tax GET request
     *
     * @return array
     */
    private function get_wc_tax_get_args() {
        return [];
    }

    /**
     * Get arguments for WC Tax POST request
     *
     * @return array
     */
    private function get_wc_tax_post_args() {
        return [
            'enabled' => [
                'description'       => __('Enable or disable WooCommerce tax rates and calculations', 'acf-rest-api'),
                'type'              => 'boolean',
                'required'          => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Options GET request
     *
     * @return array
     */
    private function get_wc_tax_options_get_args() {
        return [];
    }

    /**
     * Get arguments for WC Tax Options POST request
     *
     * @return array
     */
    private function get_wc_tax_options_post_args() {
        return [
            'prices_include_tax' => [
                'description'       => __('Whether prices are entered with tax included (yes/no or boolean)', 'acf-rest-api'),
                'type'              => ['boolean', 'string'],
                'required'          => false,
            ],
            'tax_based_on' => [
                'description'       => __('Calculate tax based on: shipping, billing, or base', 'acf-rest-api'),
                'type'              => 'string',
                'required'          => false,
                'enum'              => ['shipping', 'billing', 'base'],
            ],
            'shipping_tax_class' => [
                'description'       => __('Shipping tax class: inherit, standard, reduced-rate, zero-rate, or custom', 'acf-rest-api'),
                'type'              => 'string',
                'required'          => false,
            ],
            'tax_round_at_subtotal' => [
                'description'       => __('Round tax at subtotal level (yes/no or boolean)', 'acf-rest-api'),
                'type'              => ['boolean', 'string'],
                'required'          => false,
            ],
            'tax_classes' => [
                'description'       => __('Additional tax classes (newline-separated string or array)', 'acf-rest-api'),
                'type'              => ['string', 'array'],
                'required'          => false,
            ],
        ];
    }

    /**
     * Check permission for updating track settings
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_track_write_permission($request) {
        // Option 1: Require authentication
        // if (!current_user_can('manage_options')) {
        //     return new WP_Error(
        //         'rest_forbidden',
        //         __('You do not have permission to update tracking settings.', 'acf-rest-api'),
        //         ['status' => 403]
        //     );
        // }
        
        // Option 2: Allow public access (current behavior)
        return true;
    }

    /**
     * Get REST API schema
     *
     * @return array
     */
    public function get_schema() {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'acf-options',
            'type'       => 'object',
            'properties' => [
                'message' => [
                    'description' => __('Response message', 'acf-rest-api'),
                    'type'        => 'string',
                    'readonly'    => true,
                ],
                'updated' => [
                    'description' => __('Updated fields', 'acf-rest-api'),
                    'type'        => 'object',
                    'readonly'    => true,
                ],
                'errors' => [
                    'description' => __('Error messages', 'acf-rest-api'),
                    'type'        => 'object',
                    'readonly'    => true,
                ],
            ],
        ];
    }
}