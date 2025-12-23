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
        // Create ACF options page - Priority 10 for acf/init
        add_action('acf/init', [$this, 'create_options_page'], 10);
        
        // Register ACF fields programmatically
        add_action('acf/init', [$this, 'register_acf_fields'], 20);
        
        // Inject tracking scripts - Priority 1 to inject early in head/body
        add_action('wp_head', [$this, 'inject_header_tracking'], 1);
        add_action('wp_body_open', [$this, 'inject_body_tracking'], 1);
        
        // Fallback for themes that don't support wp_body_open
        add_action('after_body_open_tag', [$this, 'inject_body_tracking'], 1);
    }

    /**
     * Create ACF options page for GTM Tracking
     */
    public function create_options_page() {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'      => __('GTM Tracking', 'acf-rest-api'),
            'menu_title'      => __('GTM Tracking', 'acf-rest-api'),
            'menu_slug'       => 'gtm_tracking',
            'capability'      => 'manage_options',
            'position'        => 20,
            'post_id'         => self::OPTIONS_POST_ID,
            'redirect'        => false,
            'icon_url'        => 'dashicons-chart-area',
            'update_button'   => __('Save GTM Settings', 'acf-rest-api'),
            'updated_message' => __('GTM Settings Saved', 'acf-rest-api'),
        ]);
    }

    /**
     * Register ACF fields programmatically
     * This creates the GTM tracking fields if they don't exist
     */
    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_gtm_tracking_settings',
            'title' => __('GTM Tracking Settings', 'acf-rest-api'),
            'fields' => [
                [
                    'key' => 'field_gtm_tracking_header',
                    'label' => __('GTM Header Code', 'acf-rest-api'),
                    'name' => 'gtm_tracking_header',
                    'type' => 'textarea',
                    'instructions' => __('Paste your Google Tag Manager code that goes in the &lt;head&gt; section. This typically includes the main GTM script.', 'acf-rest-api'),
                    'required' => 0,
                    'rows' => 8,
                    'placeholder' => "<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){...})(window,document,'script','dataLayer','GTM-XXXXX');</script>\n<!-- End Google Tag Manager -->",
                    'new_lines' => '',
                ],
                [
                    'key' => 'field_gtm_tracking_body',
                    'label' => __('GTM Body Code (noscript)', 'acf-rest-api'),
                    'name' => 'gtm_tracking_body',
                    'type' => 'textarea',
                    'instructions' => __('Paste your Google Tag Manager noscript code that goes immediately after the opening &lt;body&gt; tag.', 'acf-rest-api'),
                    'required' => 0,
                    'rows' => 6,
                    'placeholder' => "<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=GTM-XXXXX\"...></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->",
                    'new_lines' => '',
                ],
                [
                    'key' => 'field_gtm_additional_scripts',
                    'label' => __('Additional Tracking Scripts', 'acf-rest-api'),
                    'name' => 'gtm_additional_scripts',
                    'type' => 'textarea',
                    'instructions' => __('Add any additional tracking scripts (Facebook Pixel, Google Analytics, etc.) that should be placed in the &lt;head&gt; section.', 'acf-rest-api'),
                    'required' => 0,
                    'rows' => 8,
                    'placeholder' => "<!-- Additional tracking scripts here -->",
                    'new_lines' => '',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'gtm_tracking',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ]);
    }

    /**
     * Inject header tracking code
     * Outputs GTM code and additional scripts in the <head> section
     */
    public function inject_header_tracking() {
        if (!function_exists('get_field')) {
            return;
        }

        // Main GTM Header Code
        $tracking_header = get_field('gtm_tracking_header', self::OPTIONS_POST_ID);
        
        if (!empty($tracking_header)) {
            echo "\n<!-- GTM Tracking Header - ACF REST API Plugin -->\n";
            echo $tracking_header . "\n";
            
            /**
             * Action hook after GTM header code is injected
             * 
             * @since 1.0.0
             */
            do_action('acf_rest_api_after_gtm_header');
        }

        // Additional Tracking Scripts
        $additional_scripts = get_field('gtm_additional_scripts', self::OPTIONS_POST_ID);
        
        if (!empty($additional_scripts)) {
            echo "\n<!-- Additional Tracking Scripts - ACF REST API Plugin -->\n";
            echo $additional_scripts . "\n";
            
            /**
             * Action hook after additional scripts are injected
             * 
             * @since 1.0.0
             */
            do_action('acf_rest_api_after_additional_scripts');
        }
    }

    /**
     * Inject body tracking code
     * Outputs GTM noscript code after the opening <body> tag
     */
    public function inject_body_tracking() {
        if (!function_exists('get_field')) {
            return;
        }

        $tracking_body = get_field('gtm_tracking_body', self::OPTIONS_POST_ID);
        
        if (!empty($tracking_body)) {
            echo "\n<!-- GTM Tracking Body - ACF REST API Plugin -->\n";
            echo $tracking_body . "\n";
            
            /**
             * Action hook after GTM body code is injected
             * 
             * @since 1.0.0
             */
            do_action('acf_rest_api_after_gtm_body');
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
     * Get a specific GTM field
     *
     * @param string $field_name Field name to retrieve
     * @return mixed|null
     */
    public function get_field($field_name) {
        if (!function_exists('get_field')) {
            return null;
        }

        return get_field($field_name, self::OPTIONS_POST_ID);
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
                'crossorigin' => true,
                'integrity' => true,
                'nonce' => true,
            ],
            'noscript' => [],
            'iframe' => [
                'src' => true,
                'height' => true,
                'width' => true,
                'style' => true,
                'frameborder' => true,
                'allow' => true,
                'allowfullscreen' => true,
            ],
            'img' => [
                'src' => true,
                'alt' => true,
                'height' => true,
                'width' => true,
                'style' => true,
                'loading' => true,
            ],
            'link' => [
                'rel' => true,
                'href' => true,
                'as' => true,
                'type' => true,
                'crossorigin' => true,
            ],
            'meta' => [
                'name' => true,
                'content' => true,
                'property' => true,
            ],
        ];

        return wp_kses($code, $allowed_tags);
    }

    /**
     * Check if GTM is configured
     *
     * @return bool
     */
    public function is_configured() {
        $header = $this->get_field('gtm_tracking_header');
        $body = $this->get_field('gtm_tracking_body');
        
        return !empty($header) || !empty($body);
    }

    /**
     * Get GTM Container ID from the header code
     *
     * @return string|null
     */
    public function get_container_id() {
        $header = $this->get_field('gtm_tracking_header');
        
        if (empty($header)) {
            return null;
        }

        // Try to extract GTM-XXXXX pattern
        if (preg_match('/GTM-[A-Z0-9]+/', $header, $matches)) {
            return $matches[0];
        }

        return null;
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

        // Add meta information
        $fields['_meta'] = [
            'is_configured' => $this->is_configured(),
            'container_id' => $this->get_container_id(),
        ];

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