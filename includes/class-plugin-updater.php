<?php
/**
 * Plugin Auto-Updater with Signed URL Support
 *
 * @package ACF_REST_API
 * @since 1.4.1
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
    private $cache_expiration = 3600; // 1 hour (shorter due to 1-day URL expiry)

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
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'after_update'], 10, 2);
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_action_links']);
        add_action('admin_init', [$this, 'handle_manual_update_check']);
        add_action('admin_notices', [$this, 'check_url_expiry_notice']);
    }

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

    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $current_version = $this->get_current_version();
        $remote_info = $this->get_remote_info(true);

        if (!$remote_info || !isset($remote_info->version)) {
            return $transient;
        }

        $remote_version = trim($remote_info->version);
        $current_version = trim($current_version);

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

    private function is_download_url_expired($remote_info) {
        if (!isset($remote_info->download_url_expires)) {
            return false;
        }
        
        $expiry_time = strtotime($remote_info->download_url_expires . ' UTC');
        return $expiry_time && time() > $expiry_time;
    }

    public function check_url_expiry_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $remote_info = $this->get_remote_info();
        
        if ($remote_info && $this->is_download_url_expired($remote_info)) {
            $current_version = $this->get_current_version();
            
            if (version_compare($current_version, $remote_info->version, '<')) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('REST API Extended:', 'acf-rest-api'); ?></strong>
                        <?php _e('Update available but download URL has expired. Please wait for auto-refresh or contact developer.', 'acf-rest-api'); ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;
        
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $source;
        }

        $expected_slug = dirname($this->plugin_basename);
        $corrected_source = trailingslashit($remote_source) . $expected_slug . '/';

        if (trailingslashit($source) === $corrected_source) {
            return $source;
        }

        if (!$wp_filesystem->exists($source)) {
            return new WP_Error('source_not_found', 'Source directory not found.');
        }

        if ($wp_filesystem->exists($corrected_source)) {
            $wp_filesystem->delete($corrected_source, true);
        }

        $result = $wp_filesystem->move($source, $corrected_source, true);
        
        if (!$result) {
            return new WP_Error('rename_failed', 'Could not rename plugin directory.');
        }

        return $corrected_source;
    }

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
        $plugin_info->name = $remote_info->name ?? 'REST API Extended';
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

        if ($this->is_download_url_expired($remote_info)) {
            $plugin_info->sections['upgrade_notice'] = 
                '<p style="color: #d63638;"><strong>⚠️ Download URL expired. Waiting for auto-refresh...</strong></p>';
        }

        if (isset($remote_info->banners)) {
            $plugin_info->banners = (array) $remote_info->banners;
        }

        if (isset($remote_info->icons)) {
            $plugin_info->icons = (array) $remote_info->icons;
        }

        return $plugin_info;
    }

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

    public function after_update($upgrader, $options) {
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }

        $our_plugin_updated = false;
        
        if (isset($options['plugins']) && is_array($options['plugins'])) {
            $our_plugin_updated = in_array($this->plugin_basename, $options['plugins'], true);
        } elseif (isset($options['plugin']) && $options['plugin'] === $this->plugin_basename) {
            $our_plugin_updated = true;
        }

        if ($our_plugin_updated) {
            delete_transient($this->cache_key);
            delete_site_transient('update_plugins');
            wp_clean_plugins_cache(true);
        }
    }

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

        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        wp_clean_plugins_cache(true);
        wp_update_plugins();

        wp_redirect(admin_url('plugins.php?acf_rest_api_checked=1'));
        exit;
    }
}
