<?php
/**
 * Plugin Auto-Updater
 *
 * Handles automatic plugin updates from Google Cloud Storage bucket.
 * This allows the plugin to check for updates and install them
 * without being hosted on wordpress.org.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_Plugin_Updater {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Plugin slug
     */
    private $plugin_slug;

    /**
     * Plugin basename (e.g., acf-rest-api/acf-rest-api.php)
     */
    private $plugin_basename;

    /**
     * Current plugin version
     */
    private $current_version;

    /**
     * URL to the update info JSON file
     */
    private $update_url;

    /**
     * Cache key for update data
     */
    private $cache_key = 'acf_rest_api_update_data';

    /**
     * Cache expiration in seconds (12 hours)
     */
    private $cache_expiration = 43200;

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
        $this->plugin_slug = 'acf-rest-api';
        $this->plugin_basename = 'acf-rest-api/acf-rest-api.php';
        
        // Get version directly from the plugin file header, not from constant
        // This ensures we always have the actual installed version
        $this->current_version = $this->get_installed_version();
        
        // Set your GCS bucket URL here
        $this->update_url = defined('ACF_REST_API_UPDATE_URL') 
            ? ACF_REST_API_UPDATE_URL 
            : '';

        if (!empty($this->update_url)) {
            $this->init_hooks();
        }
    }

    /**
     * Get the actual installed plugin version from file header
     * 
     * @return string
     */
    private function get_installed_version() {
        // First try the constant
        if (defined('ACF_REST_API_VERSION')) {
            return ACF_REST_API_VERSION;
        }
        
        // Fallback: read from plugin file header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
        
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file, false, false);
            if (!empty($plugin_data['Version'])) {
                return $plugin_data['Version'];
            }
        }
        
        return '0.0.0';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Check for updates
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        
        // Plugin information popup
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        
        // After update, clear cache
        add_action('upgrader_process_complete', [$this, 'clear_update_cache'], 10, 2);
        
        // Fix source directory name during auto-update
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
        
        // Add "Check for updates" link in plugins page
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_action_links']);
        
        // Handle manual update check
        add_action('admin_init', [$this, 'handle_manual_update_check']);
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Always get fresh version from file
        $current_version = $this->get_installed_version();
        $remote_info = $this->get_remote_info();

        if (!$remote_info || !isset($remote_info->version)) {
            return $transient;
        }

        $remote_version = $remote_info->version;
        
        // Normalize versions for comparison (remove any whitespace)
        $current_version = trim($current_version);
        $remote_version = trim($remote_version);

        // Compare versions - only show update if remote is GREATER than current
        $needs_update = version_compare($current_version, $remote_version, '<');

        if ($needs_update) {
            $plugin_data = new stdClass();
            $plugin_data->slug = $this->plugin_slug;
            $plugin_data->plugin = $this->plugin_basename;
            $plugin_data->new_version = $remote_version;
            $plugin_data->url = $remote_info->homepage ?? '';
            $plugin_data->package = $remote_info->download_url ?? '';
            $plugin_data->icons = (array) ($remote_info->icons ?? []);
            $plugin_data->banners = (array) ($remote_info->banners ?? []);
            $plugin_data->tested = $remote_info->tested ?? '';
            $plugin_data->requires_php = $remote_info->requires_php ?? '7.4';
            $plugin_data->compatibility = new stdClass();

            $transient->response[$this->plugin_basename] = $plugin_data;
            
            // Make sure it's not in no_update
            unset($transient->no_update[$this->plugin_basename]);
        } else {
            // No update available - add to no_update list
            $plugin_data = new stdClass();
            $plugin_data->slug = $this->plugin_slug;
            $plugin_data->plugin = $this->plugin_basename;
            $plugin_data->new_version = $current_version;
            $plugin_data->url = '';
            $plugin_data->package = '';

            $transient->no_update[$this->plugin_basename] = $plugin_data;
            
            // Make sure it's not in response
            unset($transient->response[$this->plugin_basename]);
        }

        return $transient;
    }

    /**
     * Plugin information for the popup
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return false|object
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $remote_info = $this->get_remote_info();

        if (!$remote_info) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = $remote_info->name ?? 'ACF REST API Extended';
        $plugin_info->slug = $this->plugin_slug;
        $plugin_info->version = $remote_info->version ?? $this->current_version;
        $plugin_info->author = $remote_info->author ?? '';
        $plugin_info->author_profile = $remote_info->author_profile ?? '';
        $plugin_info->homepage = $remote_info->homepage ?? '';
        $plugin_info->requires = $remote_info->requires ?? '5.8';
        $plugin_info->tested = $remote_info->tested ?? '';
        $plugin_info->requires_php = $remote_info->requires_php ?? '7.4';
        $plugin_info->downloaded = $remote_info->downloaded ?? 0;
        $plugin_info->last_updated = $remote_info->last_updated ?? '';
        $plugin_info->download_link = $remote_info->download_url ?? '';
        
        // Sections
        $plugin_info->sections = [];
        if (isset($remote_info->sections)) {
            foreach ($remote_info->sections as $key => $value) {
                $plugin_info->sections[$key] = $value;
            }
        }

        // Banners
        if (isset($remote_info->banners)) {
            $plugin_info->banners = (array) $remote_info->banners;
        }

        // Icons
        if (isset($remote_info->icons)) {
            $plugin_info->icons = (array) $remote_info->icons;
        }

        return $plugin_info;
    }

    /**
     * Get remote plugin info from GCS
     *
     * @param bool $force_refresh Force refresh cache
     * @return object|false
     */
    private function get_remote_info($force_refresh = false) {
        if (empty($this->update_url)) {
            return false;
        }

        // Check cache first
        if (!$force_refresh) {
            $cached = get_transient($this->cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fetch from remote with cache-busting
        $url = add_query_arg('nocache', time(), $this->update_url);
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'Cache-Control' => 'no-cache',
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return false;
        }

        // Cache the result
        set_transient($this->cache_key, $data, $this->cache_expiration);

        return $data;
    }

    /**
     * Clear update cache after plugin update
     *
     * @param WP_Upgrader $upgrader
     * @param array $options
     */
    public function clear_update_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            // Clear our custom cache
            delete_transient($this->cache_key);
            
            // If our plugin was updated, also clear WordPress update cache
            if (isset($options['plugins']) && is_array($options['plugins'])) {
                if (in_array($this->plugin_basename, $options['plugins'], true)) {
                    // Force refresh of plugin update transient
                    delete_site_transient('update_plugins');
                }
            }
        }
    }

    /**
     * Fix the source directory name during update
     * 
     * @param string $source        Path to upgrade/update source
     * @param string $remote_source Remote source URL  
     * @param WP_Upgrader $upgrader WP_Upgrader instance
     * @param array $hook_extra     Extra arguments
     * @return string|WP_Error
     */
    public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;
        
        // Check if this is our plugin being updated
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $source;
        }
        
        // Get the expected directory name
        $expected_dir = dirname($this->plugin_basename);
        $corrected_source = trailingslashit($remote_source) . trailingslashit($expected_dir);
        
        // If source already has correct name, return it
        if (trailingslashit($source) === $corrected_source) {
            return $source;
        }
        
        // Check if the source directory exists
        if (!$wp_filesystem->exists($source)) {
            return new WP_Error(
                'source_not_exists',
                __('Update source directory does not exist.', 'acf-rest-api')
            );
        }
        
        // Remove destination if exists
        if ($wp_filesystem->exists($corrected_source)) {
            $wp_filesystem->delete($corrected_source, true);
        }
        
        // Rename/move the source directory
        if ($wp_filesystem->move($source, $corrected_source, true)) {
            return $corrected_source;
        }
        
        return new WP_Error(
            'rename_failed',
            __('Unable to rename update source directory.', 'acf-rest-api')
        );
    }

    /**
     * Add action links to plugins page
     *
     * @param array $links
     * @return array
     */
    public function add_action_links($links) {
        $check_update_link = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(
                admin_url('plugins.php?acf_rest_api_check_update=1'),
                'acf_rest_api_check_update'
            ),
            __('Check for updates', 'acf-rest-api')
        );
        
        array_unshift($links, $check_update_link);
        
        return $links;
    }

    /**
     * Handle manual update check
     */
    public function handle_manual_update_check() {
        if (!isset($_GET['acf_rest_api_check_update'])) {
            return;
        }

        if (!wp_verify_nonce($_GET['_wpnonce'], 'acf_rest_api_check_update')) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        // Clear ALL caches
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        
        // Force WordPress to check for updates
        wp_clean_plugins_cache(true);
        wp_update_plugins();

        // Redirect back with message
        wp_redirect(admin_url('plugins.php?acf_rest_api_checked=1'));
        exit;
    }

    /**
     * Get current version
     *
     * @return string
     */
    public function get_current_version() {
        return $this->current_version;
    }

    /**
     * Get update URL
     *
     * @return string
     */
    public function get_update_url() {
        return $this->update_url;
    }
}