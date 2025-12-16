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
        $this->current_version = ACF_REST_API_VERSION;
        
        // Set your GCS bucket URL here
        // Format: https://storage.googleapis.com/YOUR_BUCKET_NAME/plugin-info.json
        $this->update_url = defined('ACF_REST_API_UPDATE_URL') 
            ? ACF_REST_API_UPDATE_URL 
            : '';

        if (!empty($this->update_url)) {
            $this->init_hooks();
        }
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

        $remote_info = $this->get_remote_info();

        if ($remote_info && isset($remote_info->version)) {
            if (version_compare($this->current_version, $remote_info->version, '<')) {
                $plugin_data = new stdClass();
                $plugin_data->slug = $this->plugin_slug;
                $plugin_data->plugin = $this->plugin_basename;
                $plugin_data->new_version = $remote_info->version;
                $plugin_data->url = $remote_info->homepage ?? '';
                $plugin_data->package = $remote_info->download_url ?? '';
                $plugin_data->icons = $remote_info->icons ?? [];
                $plugin_data->banners = $remote_info->banners ?? [];
                $plugin_data->tested = $remote_info->tested ?? '';
                $plugin_data->requires_php = $remote_info->requires_php ?? '7.4';
                $plugin_data->compatibility = new stdClass();

                $transient->response[$this->plugin_basename] = $plugin_data;
            } else {
                // No update available - add to no_update list
                $plugin_data = new stdClass();
                $plugin_data->slug = $this->plugin_slug;
                $plugin_data->plugin = $this->plugin_basename;
                $plugin_data->new_version = $this->current_version;
                $plugin_data->url = '';
                $plugin_data->package = '';

                $transient->no_update[$this->plugin_basename] = $plugin_data;
            }
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
        
        // Sections (description, installation, changelog, etc.)
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

        // Fetch from remote
        $response = wp_remote_get($this->update_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
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
     * Clear update cache
     *
     * @param WP_Upgrader $upgrader
     * @param array $options
     */
    public function clear_update_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient($this->cache_key);
        }
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

        // Clear cache and force refresh
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');

        // Trigger update check
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
