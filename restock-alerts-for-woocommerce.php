<?php
/**
 * Plugin Name: Restock Alerts for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://wordpress.org/plugins/search/restock-alerts-for-woocommerce/
 * Description: Add a "Notify Me" button for out-of-stock products, send back-in-stock alerts and follow-up emails with unique discount codes.
 * Version: 1.0
 * Author: Store Boost Kit
 * Author URI: https://storeboostkit.com/
 * Text Domain: restock-alerts-for-woocommerce
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Domain Path: /languages/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.6
 *
 * @package restock-alerts-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SBK_RAW_PLUGIN_FILE' ) ) {
	define( 'SBK_RAW_PLUGIN_FILE', __FILE__ );
}

// Include the main class.
if ( ! class_exists( 'SBK_RAW', false ) ) {
	include_once dirname( SBK_RAW_PLUGIN_FILE ) . '/includes/class-sbk_raw.php';
}

/**
 * Returns the main instance of SBK_RAW.
 *
 * @since  1.0
 * @return SBK_RAW
 */
function sbk_raw() {
	return \SBK_RAW\SBK_RAW::instance();
}

// Global for backwards compatibility.
$GLOBALS['sbk_raw'] = sbk_raw();
