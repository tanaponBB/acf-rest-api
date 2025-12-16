<?php
/**
 * ACF Options API Handler
 *
 * Handles REST API endpoints for ACF option fields.
 *
 * @package ACF_REST_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_Options_API {

    /**
     * Single instance
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
        // Initialization if needed
    }

    /**
     * Get all ACF option fields
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_all_options($request) {
        if (!function_exists('get_fields')) {
            return new WP_REST_Response([
                'message' => __('ACF is not available', 'acf-rest-api'),
                'code' => 'acf_not_available'
            ], 500);
        }

        $fields = get_fields('options');

        if (!$fields) {
            return new WP_REST_Response([
                'message' => __('No fields found', 'acf-rest-api'),
                'code' => 'no_fields'
            ], 404);
        }

        // Include select field choices if available
        $fields = $this->include_field_choices($fields);

        return new WP_REST_Response($fields, 200);
    }

    /**
     * Update ACF option fields
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function update_options($request) {
        if (!function_exists('update_field') || !function_exists('get_field_object')) {
            return new WP_REST_Response([
                'message' => __('ACF is not available', 'acf-rest-api'),
                'code' => 'acf_not_available'
            ], 500);
        }

        $parameters = $request->get_params();
        $updated_fields = [];
        $errors = [];

        foreach ($parameters as $field_name => $value) {
            // Skip internal parameters
            if (in_array($field_name, ['_wpnonce', 'rest_route'], true)) {
                continue;
            }

            // Verify field exists
            $field_object = get_field_object($field_name, 'option');
            
            if ($field_object) {
                // Sanitize value based on field type
                $sanitized_value = $this->sanitize_field_value($value, $field_object);
                
                $update_result = update_field($field_name, $sanitized_value, 'option');
                
                if ($update_result) {
                    $updated_fields[$field_name] = $sanitized_value;
                } else {
                    $errors[$field_name] = __('Failed to update', 'acf-rest-api');
                }
            } else {
                $errors[$field_name] = __('Field not found', 'acf-rest-api');
            }
        }

        $response = [
            'message' => __('Update operation completed', 'acf-rest-api'),
            'updated' => $updated_fields,
            'updated_count' => count($updated_fields)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $status_code = empty($errors) ? 200 : 207; // 207 Multi-Status if partial success
        
        return new WP_REST_Response($response, $status_code);
    }

    /**
     * Include field choices for select fields
     *
     * @param array $fields Fields array
     * @return array
     */
    private function include_field_choices($fields) {
        if (!function_exists('acf_get_field')) {
            return $fields;
        }

        // Add choices for known select fields
        $select_fields = ['select_product_show_cast'];
        
        foreach ($select_fields as $field_name) {
            $field = acf_get_field($field_name);
            
            if ($field && isset($field['choices'])) {
                if (!isset($fields['_choices'])) {
                    $fields['_choices'] = [];
                }
                $fields['_choices'][$field_name] = $field['choices'];
            }
        }

        return $fields;
    }

    /**
     * Sanitize field value based on field type
     *
     * @param mixed $value Field value
     * @param array $field_object ACF field object
     * @return mixed
     */
    private function sanitize_field_value($value, $field_object) {
        $field_type = $field_object['type'] ?? 'text';

        switch ($field_type) {
            case 'text':
            case 'url':
            case 'email':
                return sanitize_text_field($value);
            
            case 'textarea':
            case 'wysiwyg':
                return wp_kses_post($value);
            
            case 'number':
                return is_numeric($value) ? floatval($value) : 0;
            
            case 'true_false':
                return (bool) $value;
            
            case 'select':
            case 'radio':
            case 'checkbox':
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return sanitize_text_field($value);
            
            case 'image':
            case 'file':
            case 'gallery':
                if (is_numeric($value)) {
                    return absint($value);
                }
                if (is_array($value)) {
                    return array_map('absint', $value);
                }
                return $value;
            
            case 'repeater':
            case 'group':
            case 'flexible_content':
                // Complex fields - return as is for now
                return $value;
            
            default:
                return $value;
        }
    }

    /**
     * Check permission for reading options
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_read_permission($request) {
        // Allow public read access - modify as needed
        return true;
    }

    /**
     * Check permission for updating options
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_write_permission($request) {
        // Require authentication for write operations
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to update options.', 'acf-rest-api'),
                ['status' => 403]
            );
        }
        return true;
    }
}
