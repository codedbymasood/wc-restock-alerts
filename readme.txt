=== Plugin Name ===
Tags: online store, ecommerce, shop, shopping cart, sell online
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Add a "Notify Me When Available" button for out-of-stock items. Store owner gets the list, user gets email when back in stock.

== Description ==
A lightweight WooCommerce addon that allows customers to subscribe for notifications when an out-of-stock product is restocked. Includes intelligent follow-up emails and include dynamic discount codes to recover more sales.

- Show **"Notify Me"** button on out-of-stock product pages
- Store user email and product info in a custom table
- Send **automatic email** when the product is back in stock
- **Track purchases** to prevent unnecessary emails

== Installation ==

= Minimum Requirements =

* PHP 7.4 or greater is required (PHP 8.0 or greater is recommended)
* MySQL 5.5.5 or greater, OR MariaDB version 10.1 or greater, is required
* WordPress 6.7 or greater

= Manual installation =

Manual installation method requires downloading the WooCommerce plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

== External Services ==

This plugin connects to storeboostkit.com to display promotional content and news in the plugin dashboard.

**What the service is used for:**
- Retrieving and displaying promotional announcements in the plugin dashboard
- Showing latest blog posts/news from StoreboostKit.com in the plugin dashboard

**What data is sent and when:**
- No personal or sensitive data is transmitted
- Only standard HTTP request headers (user agent, etc.) are sent
- Requests are made only when administrators access the plugin dashboard

**When data is sent:**
- When plugin administrators view the plugin dashboard page
- Data retrieval is limited to publicly available content

**Service provider:**
- Terms of Service: [https://storeboostkit.com/terms-and-conditions/]
- Privacy Policy: [https://storeboostkit.com/privacy-policy/]

This external service usage is solely for displaying informational content and does not affect the core functionality of the plugin. All communications use secure HTTPS connections.

= Updating =

Automatic updates should work smoothly, but we still recommend you back up your site.

If you encounter issues with the pages after an update, flush the permalinks by going to WordPress > Settings > Permalinks and hitting "Save." That should return things to normal.