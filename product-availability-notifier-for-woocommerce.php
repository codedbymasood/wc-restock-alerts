<?php
/**
 * Plugin Name: Product availability notifier for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://wordpress.org/plugins/search/product-availability-notifier-for-woocommerce/
 * Description: Add a "Notify Me" button for out-of-stock products, send back-in-stock alerts and follow-up emails with unique discount codes.
 * Version: 1.0
 * Author: Masood Mohamed
 * Author URI: https://github.com/codedbymasood
 * Text Domain: product-availability-notifier-for-woocommerce
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Domain Path: /languages/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.6
 *
 * @package product-availability-notifier-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PRODAVNO_PLUGIN_FILE' ) ) {
	define( 'PRODAVNO_PLUGIN_FILE', __FILE__ );
}

// Include the main class.
if ( ! class_exists( 'PRODAVNO', false ) ) {
	include_once dirname( PRODAVNO_PLUGIN_FILE ) . '/includes/class-prodavno.php';
}

/**
 * Returns the main instance of PRODAVNO.
 *
 * @since  1.0
 * @return PRODAVNO
 */
function prodavno() {
	return \PRODAVNO\PRODAVNO::instance();
}

// Global for backwards compatibility.
$GLOBALS['prodavno'] = prodavno();
