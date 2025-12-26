<?php
/**
 * Plugin Configuration (Obfuscated)
 *
 * @package ACF_REST_API
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_REST_API_Config {

    /**
     * Get update server URL
     * URL is encoded to prevent casual discovery
     *
     * @return string
     */
    public static function get_update_url() {
        // Encoded URL - not visible in plain text
        $parts = [
            'aHR0cHM6Ly9zdG9yYWdlLmdvb2dsZWFwaXM',  // Part 1
            'uY29tL3dwLXNpZ25lZC11cmxzL2FjZi1yZX',  // Part 2
            'N0LWFwaS9wbHVnaW4taW5mby5qc29u',       // Part 3
        ];
        
        return base64_decode(implode('', $parts));
    }

    /**
     * Alternative: XOR encoding (harder to decode)
     *
     * @return string
     */
    public static function get_update_url_v2() {
        // XOR encoded with key
        $encoded = [
            0x2b, 0x2d, 0x2d, 0x21, 0x38, 0x4a, 0x48, 0x48, 
            0x38, 0x2d, 0x34, 0x37, 0x26, 0x2c, 0x2a, 0x49
            // ... (shortened for example)
        ];
        
        $key = 'S3cr3tK3y!';
        $result = '';
        
        foreach ($encoded as $i => $byte) {
            $result .= chr($byte ^ ord($key[$i % strlen($key)]));
        }
        
        return $result;
    }

    /**
     * Get plugin slug
     *
     * @return string
     */
    public static function get_plugin_slug() {
        return 'acf-rest-api';
    }

    /**
     * Get plugin version from main file
     *
     * @return string
     */
    public static function get_version() {
        return ACF_REST_API_VERSION;
    }
}