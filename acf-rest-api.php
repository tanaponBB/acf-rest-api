<?php
/**
 * Plugin Name: REST API Extended
 * Plugin URI: https://theneighbors.co/
 * Description: Extends WordPress REST API with ACF Options and GTM Tracking endpoints. Provides GET/POST routes for managing ACF option fields and GTM tracking settings.
 * Version: 1.4.0
 * Author: TanaponBB
 * Author URI: https://theneighbors.co/
 * Text Domain: acf-rest-api
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ACF_REST_API_VERSION', '1.4.0');
define('ACF_REST_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACF_REST_API_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Auto-Update Configuration
 */
if (!defined('ACF_REST_API_UPDATE_URL')) {
    define('ACF_REST_API_UPDATE_URL', 'https://storage.googleapis.com/wp-signed-urls/acf-rest-api/plugin-info.json');
}

/**
 * Main Plugin Class
 */
class ACF_REST_API_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_dependencies'], 5);
        add_action('plugins_loaded', [$this, 'init_gtm_tracking'], 10);
        add_action('init', [$this, 'init_components']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_notices', [$this, 'check_dependencies']);
        add_action('admin_notices', [$this, 'show_update_check_notice']);
    }

    public function load_dependencies() {
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-options-api.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-gtm-tracking.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-rest-endpoints.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-plugin-updater.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-coupon-setting.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-tax-setting.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-tax-options-setting.php';
        require_once ACF_REST_API_PLUGIN_DIR . 'includes/class-tax-rates.php';
    }

    public function init_gtm_tracking() {
        if (class_exists('ACF_REST_GTM_Tracking')) {
            ACF_REST_GTM_Tracking::get_instance();
        }
    }

    public function init_components() {
        if (class_exists('ACF_REST_Options_API')) {
            ACF_REST_Options_API::get_instance();
        }

        if (class_exists('ACF_REST_Plugin_Updater')) {
            ACF_REST_Plugin_Updater::get_instance();
        }

        if (class_exists('ACF_REST_WC_Coupon_Settings')) {
            ACF_REST_WC_Coupon_Settings::get_instance();
        }

        if (class_exists('ACF_REST_WC_Tax_Settings')) {
            ACF_REST_WC_Tax_Settings::get_instance();
        }

        if (class_exists('ACF_REST_WC_Tax_Options_Settings')) {
            ACF_REST_WC_Tax_Options_Settings::get_instance();
        }

        if (class_exists('ACF_REST_WC_Tax_Rates')) {
            ACF_REST_WC_Tax_Rates::get_instance();
        }
    }

    public function register_rest_routes() {
        if (class_exists('ACF_REST_Endpoints')) {
            $endpoints = ACF_REST_Endpoints::get_instance();
            $endpoints->register_routes();
        }
    }

    public function check_dependencies() {
        if (!function_exists('get_field')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('REST API Extended:', 'acf-rest-api'); ?></strong>
                    <?php _e('This plugin requires Advanced Custom Fields (ACF) to be installed and activated.', 'acf-rest-api'); ?>
                </p>
            </div>
            <?php
        }
    }

    public function show_update_check_notice() {
        if (isset($_GET['acf_rest_api_checked'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('REST API Extended:', 'acf-rest-api'); ?></strong>
                    <?php _e('Update check completed. If an update is available, it will appear below.', 'acf-rest-api'); ?>
                </p>
            </div>
            <?php
        }
    }

    public static function activate() {
        if (function_exists('acf_add_options_page')) {
            self::create_options_pages();
        }
        flush_rewrite_rules();
    }

    public static function deactivate() {
        delete_transient('acf_rest_api_update_data');
        flush_rewrite_rules();
    }

    private static function create_options_pages() {
        // Handled by GTM Tracking class
    }
}

register_activation_hook(__FILE__, ['ACF_REST_API_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['ACF_REST_API_Plugin', 'deactivate']);

function acf_rest_api_init() {
    return ACF_REST_API_Plugin::get_instance();
}

add_action('plugins_loaded', 'acf_rest_api_init', 1);
