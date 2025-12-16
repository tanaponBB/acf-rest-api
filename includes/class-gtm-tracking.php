<?php
/**
 * GTM Tracking Handler
 *
 * Handles Google Tag Manager tracking functionality including
 * options page creation and script injection.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_GTM_Tracking {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Options page post ID
     */
    const OPTIONS_POST_ID = 'gtm_tracking';

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
        // Create ACF options page
        add_action('acf/init', [$this, 'create_options_page']);
        
        // Inject tracking scripts
        add_action('wp_head', [$this, 'inject_header_tracking'], 1);
        add_action('wp_body_open', [$this, 'inject_body_tracking'], 1);
    }

    /**
     * Create ACF options page for GTM Tracking
     */
    public function create_options_page() {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'    => __('GTM Tracking', 'acf-rest-api'),
            'menu_title'    => __('GTM Tracking', 'acf-rest-api'),
            'menu_slug'     => 'gtm_tracking',
            'capability'    => 'manage_options',
            'position'      => 20,
            'post_id'       => self::OPTIONS_POST_ID,
            'redirect'      => false,
            'icon_url'      => 'dashicons-chart-area',
            'update_button' => __('Save GTM Settings', 'acf-rest-api'),
            'updated_message' => __('GTM Settings Saved', 'acf-rest-api'),
        ]);
    }

    /**
     * Inject header tracking code
     */
    public function inject_header_tracking() {
        if (!function_exists('get_field')) {
            return;
        }

        $tracking_header = get_field('gtm_tracking_header', self::OPTIONS_POST_ID);
        
        if (!empty($tracking_header)) {
            echo "\n<!-- GTM Tracking Header - ACF REST API Plugin -->\n";
            echo $tracking_header . "\n";
        }
    }

    /**
     * Inject body tracking code
     */
    public function inject_body_tracking() {
        if (!function_exists('get_field')) {
            return;
        }

        $tracking_body = get_field('gtm_tracking_body', self::OPTIONS_POST_ID);
        
        if (!empty($tracking_body)) {
            echo "\n<!-- GTM Tracking Body - ACF REST API Plugin -->\n";
            echo $tracking_body . "\n";
        }
    }

    /**
     * Get all GTM tracking fields
     *
     * @return array|false
     */
    public function get_all_fields() {
        if (!function_exists('get_fields')) {
            return false;
        }

        return get_fields(self::OPTIONS_POST_ID);
    }

    /**
     * Update GTM tracking fields
     *
     * @param array $data Fields to update
     * @return array Results
     */
    public function update_fields($data) {
        if (!function_exists('update_field')) {
            return [
                'success' => false,
                'message' => __('ACF is not available', 'acf-rest-api')
            ];
        }

        $updated = [];
        $errors = [];

        foreach ($data as $key => $value) {
            // Sanitize the tracking code
            $sanitized_value = $this->sanitize_tracking_code($value);
            
            $result = update_field($key, $sanitized_value, self::OPTIONS_POST_ID);
            
            if ($result) {
                $updated[$key] = true;
            } else {
                $errors[$key] = __('Failed to update', 'acf-rest-api');
            }
        }

        return [
            'success' => empty($errors),
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    /**
     * Sanitize tracking code
     * Allow script tags and common tracking elements
     *
     * @param string $code Tracking code
     * @return string
     */
    private function sanitize_tracking_code($code) {
        // For tracking codes, we need to allow scripts
        // This should only be used by administrators
        if (current_user_can('unfiltered_html')) {
            return $code;
        }

        // For non-admins, use wp_kses with expanded allowed tags
        $allowed_tags = [
            'script' => [
                'src' => true,
                'async' => true,
                'defer' => true,
                'type' => true,
                'id' => true,
                'data-*' => true,
            ],
            'noscript' => [],
            'iframe' => [
                'src' => true,
                'height' => true,
                'width' => true,
                'style' => true,
            ],
            'img' => [
                'src' => true,
                'alt' => true,
                'height' => true,
                'width' => true,
                'style' => true,
            ],
        ];

        return wp_kses($code, $allowed_tags);
    }

    /**
     * Get REST API endpoint handlers
     *
     * @return array
     */
    public function get_rest_handlers() {
        return [
            'get' => [$this, 'rest_get_handler'],
            'post' => [$this, 'rest_update_handler'],
        ];
    }

    /**
     * REST API GET handler
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_handler($request) {
        $fields = $this->get_all_fields();

        if (!$fields) {
            return new WP_REST_Response([
                'message' => __('No fields found', 'acf-rest-api'),
                'code' => 'no_fields'
            ], 404);
        }

        // Include select field choices if available
        if (function_exists('acf_get_field')) {
            $field_name = 'select_product_show_cast';
            $field = acf_get_field($field_name);
            
            if ($field && isset($field['choices'])) {
                $fields['_choices'][$field_name] = $field['choices'];
            }
        }

        return new WP_REST_Response($fields, 200);
    }

    /**
     * REST API POST handler
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_update_handler($request) {
        $data = $request->get_json_params();

        if (!$data || !is_array($data)) {
            return new WP_REST_Response([
                'message' => __('No data provided', 'acf-rest-api'),
                'code' => 'no_data'
            ], 400);
        }

        $result = $this->update_fields($data);

        $status_code = $result['success'] ? 200 : 207;
        
        return new WP_REST_Response([
            'message' => __('GTM fields updated', 'acf-rest-api'),
            'success' => $result['success'],
            'updated' => $result['updated'],
            'errors' => $result['errors'] ?? []
        ], $status_code);
    }
}
