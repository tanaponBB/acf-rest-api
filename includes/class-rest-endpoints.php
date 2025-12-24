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

        // WooCommerce Tax Rates routes - /woocommerce-ext/v1/tax-rates
        $this->register_wc_tax_rates_routes();
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
     * Register WooCommerce tax rates routes
     */
    private function register_wc_tax_rates_routes() {
        if (!class_exists('ACF_REST_WC_Tax_Rates')) {
            return;
        }

        $wc_tax_rates = ACF_REST_WC_Tax_Rates::get_instance();

        // GET /woocommerce-ext/v1/tax-rates - Get all tax rates
        register_rest_route(self::WC_NAMESPACE, '/tax-rates', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax_rates, 'rest_get_rates_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_read_permission'],
            'args'                => $this->get_wc_tax_rates_list_args(),
        ]);

        // POST /woocommerce-ext/v1/tax-rates - Create tax rate
        register_rest_route(self::WC_NAMESPACE, '/tax-rates', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_tax_rates, 'rest_create_rate_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => $this->get_wc_tax_rates_create_args(),
        ]);

        // GET /woocommerce-ext/v1/tax-rates/{id} - Get single tax rate
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax_rates, 'rest_get_rate_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_read_permission'],
            'args'                => [
                'id' => [
                    'description' => __('Tax rate ID', 'acf-rest-api'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
            ],
        ]);

        // PUT/PATCH /woocommerce-ext/v1/tax-rates/{id} - Update tax rate
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [$wc_tax_rates, 'rest_update_rate_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => $this->get_wc_tax_rates_update_args(),
        ]);

        // DELETE /woocommerce-ext/v1/tax-rates/{id} - Delete tax rate
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$wc_tax_rates, 'rest_delete_rate_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => [
                'id' => [
                    'description' => __('Tax rate ID', 'acf-rest-api'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
            ],
        ]);

        // POST /woocommerce-ext/v1/tax-rates/batch - Batch operations
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/batch', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_tax_rates, 'rest_batch_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => $this->get_wc_tax_rates_batch_args(),
        ]);

        // POST /woocommerce-ext/v1/tax-rates/import - Import from CSV
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/import', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$wc_tax_rates, 'rest_import_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => $this->get_wc_tax_rates_import_args(),
        ]);

        // GET /woocommerce-ext/v1/tax-rates/export - Export to CSV
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/export', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax_rates, 'rest_export_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_read_permission'],
            'args'                => $this->get_wc_tax_rates_export_args(),
        ]);

        // GET /woocommerce-ext/v1/tax-rates/classes - Get tax classes
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/classes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$wc_tax_rates, 'rest_get_classes_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_read_permission'],
        ]);

        // DELETE /woocommerce-ext/v1/tax-rates/all - Delete all rates (optionally by class)
        register_rest_route(self::WC_NAMESPACE, '/tax-rates/all', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$wc_tax_rates, 'rest_delete_all_handler'],
            'permission_callback' => [$wc_tax_rates, 'check_write_permission'],
            'args'                => [
                'class' => [
                    'description' => __('Tax class to delete rates for (empty for standard)', 'acf-rest-api'),
                    'type'        => 'string',
                    'required'    => false,
                ],
            ],
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
                'description' => __('Whether prices are entered with tax included (yes/no or boolean)', 'acf-rest-api'),
                'type'        => ['boolean', 'string'],
                'required'    => false,
            ],
            'tax_based_on' => [
                'description' => __('Calculate tax based on: shipping, billing, or base', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
                'enum'        => ['shipping', 'billing', 'base'],
            ],
            'shipping_tax_class' => [
                'description' => __('Shipping tax class: inherit, standard, reduced-rate, zero-rate, or custom', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'tax_round_at_subtotal' => [
                'description' => __('Round tax at subtotal level (yes/no or boolean)', 'acf-rest-api'),
                'type'        => ['boolean', 'string'],
                'required'    => false,
            ],
            'tax_classes' => [
                'description' => __('Additional tax classes (newline-separated string or array)', 'acf-rest-api'),
                'type'        => ['string', 'array'],
                'required'    => false,
            ],
            'tax_display_shop' => [
                'description' => __('Display prices in the shop: incl or excl', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
                'enum'        => ['incl', 'excl'],
            ],
            'tax_display_cart' => [
                'description' => __('Display prices during cart and checkout: incl or excl', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
                'enum'        => ['incl', 'excl'],
            ],
            'tax_total_display' => [
                'description' => __('Display tax totals: single or itemized', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
                'enum'        => ['single', 'itemized'],
            ],
            'price_display_suffix' => [
                'description' => __('Price display suffix text', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Rates list request
     *
     * @return array
     */
    private function get_wc_tax_rates_list_args() {
        return [
            'class' => [
                'description' => __('Filter by tax class (standard, reduced-rate, zero-rate, or custom)', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'page' => [
                'description' => __('Page number', 'acf-rest-api'),
                'type'        => 'integer',
                'default'     => 1,
                'minimum'     => 1,
            ],
            'per_page' => [
                'description' => __('Items per page', 'acf-rest-api'),
                'type'        => 'integer',
                'default'     => 100,
                'minimum'     => 1,
                'maximum'     => 1000,
            ],
            'orderby' => [
                'description' => __('Order by field', 'acf-rest-api'),
                'type'        => 'string',
                'default'     => 'tax_rate_order',
                'enum'        => ['tax_rate_id', 'tax_rate_country', 'tax_rate_state', 'tax_rate', 'tax_rate_name', 'tax_rate_priority', 'tax_rate_order'],
            ],
            'order' => [
                'description' => __('Sort order', 'acf-rest-api'),
                'type'        => 'string',
                'default'     => 'ASC',
                'enum'        => ['ASC', 'DESC'],
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Rates create request
     *
     * @return array
     */
    private function get_wc_tax_rates_create_args() {
        return [
            'country' => [
                'description' => __('Two-letter country code', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'state' => [
                'description' => __('State code', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'postcode' => [
                'description' => __('Postcode(s), semicolon-separated or array', 'acf-rest-api'),
                'type'        => ['string', 'array'],
                'required'    => false,
            ],
            'city' => [
                'description' => __('City/cities, semicolon-separated or array', 'acf-rest-api'),
                'type'        => ['string', 'array'],
                'required'    => false,
            ],
            'rate' => [
                'description' => __('Tax rate percentage', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => true,
            ],
            'name' => [
                'description' => __('Tax rate name', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'priority' => [
                'description' => __('Tax rate priority', 'acf-rest-api'),
                'type'        => 'integer',
                'default'     => 1,
            ],
            'compound' => [
                'description' => __('Whether tax is compound', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => false,
            ],
            'shipping' => [
                'description' => __('Whether tax applies to shipping', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => true,
            ],
            'class' => [
                'description' => __('Tax class (standard, reduced-rate, zero-rate, or custom)', 'acf-rest-api'),
                'type'        => 'string',
                'default'     => 'standard',
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Rates update request
     *
     * @return array
     */
    private function get_wc_tax_rates_update_args() {
        $args = $this->get_wc_tax_rates_create_args();
        $args['id'] = [
            'description' => __('Tax rate ID', 'acf-rest-api'),
            'type'        => 'integer',
            'required'    => true,
        ];
        // Make rate not required for updates
        $args['rate']['required'] = false;
        return $args;
    }

    /**
     * Get arguments for WC Tax Rates batch request
     *
     * @return array
     */
    private function get_wc_tax_rates_batch_args() {
        return [
            'create' => [
                'description' => __('Array of tax rates to create', 'acf-rest-api'),
                'type'        => 'array',
                'required'    => false,
            ],
            'update' => [
                'description' => __('Array of tax rates to update (must include id)', 'acf-rest-api'),
                'type'        => 'array',
                'required'    => false,
            ],
            'delete' => [
                'description' => __('Array of tax rate IDs to delete', 'acf-rest-api'),
                'type'        => 'array',
                'required'    => false,
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Rates import request
     *
     * @return array
     */
    private function get_wc_tax_rates_import_args() {
        return [
            'csv_content' => [
                'description' => __('CSV content string', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'update_existing' => [
                'description' => __('Update existing rates if found', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => false,
            ],
            'delete_existing' => [
                'description' => __('Delete existing rates before import', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => false,
            ],
            'class' => [
                'description' => __('Override tax class for all imported rates', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
        ];
    }

    /**
     * Get arguments for WC Tax Rates export request
     *
     * @return array
     */
    private function get_wc_tax_rates_export_args() {
        return [
            'class' => [
                'description' => __('Export only rates for this tax class', 'acf-rest-api'),
                'type'        => 'string',
                'required'    => false,
            ],
            'download' => [
                'description' => __('Return as downloadable file', 'acf-rest-api'),
                'type'        => 'boolean',
                'default'     => false,
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