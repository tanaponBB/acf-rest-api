<?php
/**
 * Plugin Auto-Updater
 *
 * Handles automatic plugin updates from Google Cloud Storage bucket.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_Plugin_Updater {

    private static $instance = null;
    private $plugin_slug;
    private $plugin_basename;
    private $update_url;
    private $cache_key = 'acf_rest_api_update_data';
    private $cache_expiration = 43200; // 12 hours

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_slug = 'acf-rest-api';
        $this->plugin_basename = 'acf-rest-api/acf-rest-api.php';
        
        $this->update_url = defined('ACF_REST_API_UPDATE_URL') 
            ? ACF_REST_API_UPDATE_URL 
            : '';

        if (!empty($this->update_url)) {
            $this->init_hooks();
        }
    }

    private function init_hooks() {
        // Check for updates
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        
        // Plugin information popup
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        
        // Clear cache after ANY plugin update completes
        add_action('upgrader_process_complete', [$this, 'after_update'], 10, 2);
        
        // Add "Check for updates" link
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_action_links']);
        
        // Handle manual update check
        add_action('admin_init', [$this, 'handle_manual_update_check']);
    }

    /**
     * Get current installed version from plugin file
     */
    private function get_current_version() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
        
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file, false, false);
            return $plugin_data['Version'] ?? '0.0.0';
        }
        
        return '0.0.0';
    }

    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get fresh current version from file
        $current_version = $this->get_current_version();
        $remote_info = $this->get_remote_info();

        if (!$remote_info || !isset($remote_info->version)) {
            return $transient;
        }

        $remote_version = trim($remote_info->version);
        $current_version = trim($current_version);

        // Only show update if remote version is GREATER than current
        if (version_compare($current_version, $remote_version, '<')) {
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
            unset($transient->no_update[$this->plugin_basename]);
        } else {
            // No update needed - remove from response, add to no_update
            $plugin_data = new stdClass();
            $plugin_data->slug = $this->plugin_slug;
            $plugin_data->plugin = $this->plugin_basename;
            $plugin_data->new_version = $current_version;
            $plugin_data->url = '';
            $plugin_data->package = '';

            $transient->no_update[$this->plugin_basename] = $plugin_data;
            unset($transient->response[$this->plugin_basename]);
        }

        return $transient;
    }

    /**
     * Plugin information for the popup
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
        $plugin_info->version = $remote_info->version ?? '0.0.0';
        $plugin_info->author = $remote_info->author ?? '';
        $plugin_info->author_profile = $remote_info->author_profile ?? '';
        $plugin_info->homepage = $remote_info->homepage ?? '';
        $plugin_info->requires = $remote_info->requires ?? '5.8';
        $plugin_info->tested = $remote_info->tested ?? '';
        $plugin_info->requires_php = $remote_info->requires_php ?? '7.4';
        $plugin_info->downloaded = $remote_info->downloaded ?? 0;
        $plugin_info->last_updated = $remote_info->last_updated ?? '';
        $plugin_info->download_link = $remote_info->download_url ?? '';
        
        $plugin_info->sections = [];
        if (isset($remote_info->sections)) {
            foreach ($remote_info->sections as $key => $value) {
                $plugin_info->sections[$key] = $value;
            }
        }

        if (isset($remote_info->banners)) {
            $plugin_info->banners = (array) $remote_info->banners;
        }

        if (isset($remote_info->icons)) {
            $plugin_info->icons = (array) $remote_info->icons;
        }

        return $plugin_info;
    }

    /**
     * Get remote plugin info from GCS
     */
    private function get_remote_info($force_refresh = false) {
        if (empty($this->update_url)) {
            return false;
        }

        if (!$force_refresh) {
            $cached = get_transient($this->cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Add cache buster to URL
        $url = add_query_arg('t', time(), $this->update_url);
        
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

        set_transient($this->cache_key, $data, $this->cache_expiration);

        return $data;
    }

    /**
     * After plugin update - clear all caches
     */
    public function after_update($upgrader, $options) {
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }

        // Check if our plugin was updated
        $our_plugin_updated = false;
        
        if (isset($options['plugins']) && is_array($options['plugins'])) {
            $our_plugin_updated = in_array($this->plugin_basename, $options['plugins'], true);
        } elseif (isset($options['plugin']) && $options['plugin'] === $this->plugin_basename) {
            $our_plugin_updated = true;
        }

        if ($our_plugin_updated) {
            // Clear our cache
            delete_transient($this->cache_key);
            
            // Force WordPress to re-check plugins
            delete_site_transient('update_plugins');
            
            // Clear plugin cache
            wp_clean_plugins_cache(true);
        }
    }

    /**
     * Add action links to plugins page
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
        wp_clean_plugins_cache(true);
        
        // Force check
        wp_update_plugins();

        wp_redirect(admin_url('plugins.php?acf_rest_api_checked=1'));
        exit;
    }
}