<?php
/**
 * Plugin Name: REST API Extended
 * Plugin URI: https://example.com/plugins/acf-rest-api
 * Description: Extends WordPress REST API with ACF Options and GTM Tracking endpoints. Provides GET/POST routes for managing ACF option fields and GTM tracking settings.
 * Version: 1.0.1
 * Author: TanaponBB
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-rest-api
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ACF_REST_API_VERSION', '1.0.1');
define('ACF_REST_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACF_REST_API_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Auto-Update Configuration
 * 
 * Set your Google Cloud Storage bucket URL here.
 * The URL should point to a JSON file containing plugin update information.
 * 
 * Example: https://storage.googleapis.com/your-bucket-name/acf-rest-api/plugin-info.json
 */
if (!defined('ACF_REST_API_UPDATE_URL')) {
    define('ACF_REST_API_UPDATE_URL', 'https://storage.googleapis.com/tanapon-wp-plugins/acf-rest-api/plugin-info.json');
}

/**
 * Main Plugin Class
 */
class ACF_REST_API_Plugin {

    /**
     * Single instance of the class
     */
    private static $instance = null;

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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load required files
        add_action('plugins_loaded', [$this, 'load_dependencies']);
        
        // Initialize components
        add_action('init', [$this, 'init_components']);
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Admin notices
        add_action('admin_notices', [$this, 'check_dependencies']);
        add_action('admin_notices', [$this, 'show_update_check_notice']);
    }

    /**
     * Load dependencies
     */
    public function load_dependencies() {
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-options-api.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-gtm-tracking.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-rest-endpoints.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-plugin-updater.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-coupon-setting.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-tax-setting.php';
    }

    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize GTM Tracking
        if (class_exists('ACF_REST_GTM_Tracking')) {
            ACF_REST_GTM_Tracking::get_instance();
        }
        
        // Initialize Options API
        if (class_exists('ACF_REST_Options_API')) {
            ACF_REST_Options_API::get_instance();
        }

        // Initialize Auto-Updater
        if (class_exists('ACF_REST_Plugin_Updater')) {
            ACF_REST_Plugin_Updater::get_instance();
        }

        // Initialize WooCommerce Coupon Settings
        if (class_exists('ACF_REST_WC_Coupon_Settings')) {
            ACF_REST_WC_Coupon_Settings::get_instance();
        }

        // Initialize WooCommerce Tax Settings
        if (class_exists('ACF_REST_WC_Tax_Settings')) {
            ACF_REST_WC_Tax_Settings::get_instance();
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        if (class_exists('ACF_REST_Endpoints')) {
            $endpoints = ACF_REST_Endpoints::get_instance();
            $endpoints->register_routes();
        }
    }

    /**
     * Check for required dependencies
     */
    public function check_dependencies() {
        if (!function_exists('get_field')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('ACF REST API Extended:', 'acf-rest-api'); ?></strong>
                    <?php _e('This plugin requires Advanced Custom Fields (ACF) to be installed and activated.', 'acf-rest-api'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Show notice after manual update check
     */
    public function show_update_check_notice() {
        if (isset($_GET['acf_rest_api_checked'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('ACF REST API Extended:', 'acf-rest-api'); ?></strong>
                    <?php _e('Update check completed. If an update is available, it will appear below.', 'acf-rest-api'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create options page on activation if ACF is available
        if (function_exists('acf_add_options_page')) {
            self::create_options_pages();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear update cache
        delete_transient('acf_rest_api_update_data');
        
        flush_rewrite_rules();
    }

    /**
     * Create ACF options pages
     */
    private static function create_options_pages() {
        // This will be handled by the GTM Tracking class
    }
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, ['ACF_REST_API_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['ACF_REST_API_Plugin', 'deactivate']);

// Initialize the plugin
function acf_rest_api_init() {
    return ACF_REST_API_Plugin::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'acf_rest_api_init', 5);