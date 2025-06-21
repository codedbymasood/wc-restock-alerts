<?php
/**
 * Plugin Name: Product availability for WooCommerce
 * Plugin URI: https://github.com/masoodmohamed90/product-availability-notifier-for-woocommerce
 * Description: Add a "Notify Me When Available" button for out-of-stock items. Store owner gets the list, user gets email when back in stock.
 * Version: 1.0
 * Author: Masood Mohamed
 * Author URI: https://github.com/masoodmohamed90/
 * Text Domain: product-availability-notifier-for-woocommerce
 * Domain Path: /languages/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 *
 * @package wp-plugin-base
 */

defined( 'ABSPATH' ) || exit;

// Insert form.
// Store emails.
// Notify when restocked.

if ( ! defined( 'PAW_PLUGIN_FILE' ) ) {
	define( 'PAW_PLUGIN_FILE', __FILE__ );
}

// Include the main class.
if ( ! class_exists( 'PAW', false ) ) {
	include_once dirname( PAW_PLUGIN_FILE ) . '/includes/class-paw.php';
}

/**
 * Returns the main instance of PAW.
 *
 * @since  1.0
 * @return PAW
 */
function paw() {
	return \PAW\PAW::instance();
}

// Global for backwards compatibility.
$GLOBALS['paw'] = paw();
