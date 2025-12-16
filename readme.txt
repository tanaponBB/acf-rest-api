=== ACF REST API Extended ===
Contributors: yourname
Tags: acf, rest-api, gtm, tracking, options
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extends WordPress REST API with ACF Options and GTM Tracking endpoints.

== Description ==

ACF REST API Extended provides a clean, organized way to manage ACF option fields and GTM tracking settings through the WordPress REST API.

= Features =

* **ACF Options API** - GET and POST endpoints for managing all ACF option fields
* **GTM Tracking** - Dedicated endpoints and admin page for Google Tag Manager settings
* **Field Choices** - Automatically includes select field choices in API responses
* **Sanitization** - Proper sanitization of all field values based on field type
* **Permission Control** - Configurable permission callbacks for each endpoint

= API Endpoints =

**Options Endpoints:**

* `GET /wp-json/options/all` - Retrieve all ACF option fields
* `POST /wp-json/options/all` - Update ACF option fields

**GTM Tracking Endpoints:**

* `GET /wp-json/options/track` - Retrieve GTM tracking settings
* `POST /wp-json/options/track` - Update GTM tracking settings

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Advanced Custom Fields (ACF) plugin

== Installation ==

1. Upload the `acf-rest-api` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure ACF is installed and activated
4. Configure GTM tracking settings in the new "GTM Tracking" admin menu

== Frequently Asked Questions ==

= Does this plugin require ACF Pro? =

No, this plugin works with both ACF Free and ACF Pro.

= How do I secure the API endpoints? =

By default, read operations are public and write operations require authentication. You can modify the permission callbacks in the `ACF_REST_Endpoints` class.

= Can I add custom endpoints? =

Yes, you can extend the plugin by creating your own classes that follow the same pattern.

== Changelog ==

= 1.0.0 =
* Initial release
* ACF Options REST API endpoints
* GTM Tracking functionality
* Admin options page for GTM settings

== Upgrade Notice ==

= 1.0.0 =
Initial release of ACF REST API Extended.
