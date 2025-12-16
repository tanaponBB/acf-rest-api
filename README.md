# ACF REST API Extended

Extends WordPress REST API with ACF Options and GTM Tracking endpoints. Provides GET/POST routes for managing ACF option fields and GTM tracking settings with automatic updates from Google Cloud Storage.

## Features

- **ACF Options API** - GET and POST endpoints for managing all ACF option fields
- **GTM Tracking** - Dedicated endpoints and admin page for Google Tag Manager settings
- **Field Choices** - Automatically includes select field choices in API responses
- **Sanitization** - Proper sanitization of all field values based on field type
- **Permission Control** - Configurable permission callbacks for each endpoint
- **Auto-Updates** - Self-hosted automatic updates via Google Cloud Storage

## Requirements

- WordPress 5.8+
- PHP 7.4+
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) (Free or Pro)

## Installation

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Activate the plugin
5. Ensure ACF is installed and activated

## API Endpoints

### ACF Options

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wp-json/options/all` | Retrieve all ACF option fields |
| POST | `/wp-json/options/all` | Update ACF option fields |

### GTM Tracking

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wp-json/options/track` | Retrieve GTM tracking settings |
| POST | `/wp-json/options/track` | Update GTM tracking settings |

## Usage Examples

### Get All Options

```bash
curl https://your-site.com/wp-json/options/all
```

Response:
```json
{
  "site_logo": 123,
  "site_description": "My awesome site",
  "social_links": {
    "facebook": "https://facebook.com/mysite",
    "twitter": "https://twitter.com/mysite"
  },
  "_choices": {
    "select_product_show_cast": {
      "option1": "Option 1",
      "option2": "Option 2"
    }
  }
}
```

### Update Options

```bash
curl -X POST https://your-site.com/wp-json/options/all \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"site_description": "Updated description"}'
```

Response:
```json
{
  "message": "Update operation completed",
  "updated": {
    "site_description": "Updated description"
  },
  "updated_count": 1
}
```

### Get GTM Tracking

```bash
curl https://your-site.com/wp-json/options/track
```

Response:
```json
{
  "gtm_tracking_header": "<!-- GTM Head Code -->",
  "gtm_tracking_body": "<!-- GTM Body Code -->"
}
```

### Update GTM Tracking

```bash
curl -X POST https://your-site.com/wp-json/options/track \
  -H "Content-Type: application/json" \
  -d '{
    "gtm_tracking_header": "<script>...</script>",
    "gtm_tracking_body": "<noscript>...</noscript>"
  }'
```

## GTM Tracking Admin

After activation, a new **GTM Tracking** menu item appears in the WordPress admin. Use this page to manage your Google Tag Manager tracking codes.

The plugin automatically injects:
- **Header code** in `<head>` via `wp_head` hook
- **Body code** after `<body>` via `wp_body_open` hook

## Auto-Updates Setup

This plugin supports automatic updates from Google Cloud Storage. See [SETUP-AUTO-UPDATE.md](SETUP-AUTO-UPDATE.md) for detailed instructions.

### Quick Setup

1. Create a GCS bucket
2. Update the URL in `acf-rest-api.php`:
   ```php
   define('ACF_REST_API_UPDATE_URL', 'https://storage.googleapis.com/YOUR_BUCKET/acf-rest-api/plugin-info.json');
   ```
3. Set up Cloud Build trigger
4. Push changes to deploy

### Manual Update Check

Click **Check for updates** link on the Plugins page to manually check for new versions.

## File Structure

```
acf-rest-api/
├── acf-rest-api.php              # Main plugin file
├── includes/
│   ├── class-gtm-tracking.php    # GTM tracking functionality
│   ├── class-options-api.php     # ACF options API handler
│   ├── class-rest-endpoints.php  # REST route registration
│   └── class-plugin-updater.php  # Auto-update handler
├── cloudbuild.yaml               # Cloud Build configuration
├── plugin-info.json              # Update metadata template
├── README.md                     # This file
├── SETUP-AUTO-UPDATE.md          # Auto-update setup guide
└── readme.txt                    # WordPress readme
```

## Hooks & Filters

### Actions

```php
// Fires after GTM header code is injected
do_action('acf_rest_api_after_gtm_header');

// Fires after GTM body code is injected  
do_action('acf_rest_api_after_gtm_body');
```

### Filters

```php
// Modify options before returning via API
add_filter('acf_rest_api_options_response', function($fields) {
    // Modify $fields
    return $fields;
});

// Customize update check interval (default: 43200 seconds / 12 hours)
add_filter('acf_rest_api_update_cache_expiration', function($seconds) {
    return 21600; // 6 hours
});
```

## Security

### Permissions

| Endpoint | Read | Write |
|----------|------|-------|
| `/options/all` | Public | `manage_options` capability |
| `/options/track` | Public | Public (configurable) |

### Customizing Permissions

Edit `includes/class-rest-endpoints.php` to modify permission callbacks:

```php
public function check_track_write_permission($request) {
    // Require authentication
    if (!current_user_can('manage_options')) {
        return new WP_Error(
            'rest_forbidden',
            __('Permission denied.', 'acf-rest-api'),
            ['status' => 403]
        );
    }
    return true;
}
```

## Development

### Local Development

```bash
# Clone the repository
git clone https://github.com/your-repo/acf-rest-api.git

# Navigate to your WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Symlink for development
ln -s /path/to/acf-rest-api acf-rest-api
```

### Releasing New Versions

1. Update version in `acf-rest-api.php`:
   ```php
   * Version: 1.1.0
   ```

2. Update the constant:
   ```php
   define('ACF_REST_API_VERSION', '1.1.0');
   ```

3. Commit and push:
   ```bash
   git add .
   git commit -m "Release 1.1.0"
   git push origin main
   ```

Cloud Build automatically handles the rest.

## Troubleshooting

### ACF Not Detected

Ensure Advanced Custom Fields is installed and activated. The plugin shows an admin notice if ACF is missing.

### REST API Returns 404

1. Go to **Settings > Permalinks**
2. Click **Save Changes** to flush rewrite rules

### Updates Not Showing

1. Click **Check for updates** on Plugins page
2. Verify GCS bucket is publicly accessible
3. Check `plugin-info.json` has correct version number

### GTM Code Not Injecting

1. Verify your theme supports `wp_body_open` hook
2. Check ACF fields are saved in GTM Tracking admin page
3. View page source to confirm injection

## Changelog

### 1.0.0
- Initial release
- ACF Options REST API endpoints
- GTM Tracking functionality
- Admin options page for GTM settings
- Auto-update support via Google Cloud Storage

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

- Built for WordPress
- Requires [Advanced Custom Fields](https://www.advancedcustomfields.com/)
- Auto-updates powered by Google Cloud Platform