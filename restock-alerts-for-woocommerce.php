<?php
/**
 * Plugin Name: Restock Alerts for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://wordpress.org/plugins/search/restock-alerts-for-woocommerce/
 * Description: Add a Notify Me button for out-of-stock products, send back-in-stock alerts and follow-up emails with unique discount codes.
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

if ( ! defined( 'RESTALER_PLUGIN_FILE' ) ) {
	define( 'RESTALER_PLUGIN_FILE', __FILE__ );
}

// Include the main class.
if ( ! class_exists( 'RESTALER', false ) ) {
	include_once dirname( RESTALER_PLUGIN_FILE ) . '/includes/class-restaler.php';
}

/**
 * Returns the main instance of RESTALER.
 *
 * @since  1.0
 * @return RESTALER
 */
function restaler() {
	return \RESTALER\RESTALER::instance();
}

// Global for backwards compatibility.
$GLOBALS['restaler'] = restaler();

/**
 * ==========================
 *  Onborading
 * ==========================
 */

// Include the onboarding class.
if ( ! class_exists( '\STOBOKIT\Onboarding' ) ) {
	include_once dirname( RESTALER_PLUGIN_FILE ) . '/core/class-onboarding.php';
}

register_activation_hook( __FILE__, 'restaler_on_plugin_activation' );

/**
 * Handle plugin activation.
 */
function restaler_on_plugin_activation() {
	// Set flag that plugin was just activated.
	set_transient( 'restaler_onboarding_activation_redirect', true, 60 );

	// Set onboarding as pending.
	update_option( 'restaler_onboarding_completed', false );
	update_option( 'restaler_onboarding_started', current_time( 'timestamp' ) );

	// Clear any existing onboarding progress.
	delete_option( 'restaler_onboarding_current_step' );
}

/**
 * Initialize the plugin.
 */
function restaler_init() {
	$steps = array(
		'welcome'  => 'Welcome',
		'settings' => 'General Setup',
		'finish'   => 'Finish',
	);

	new \STOBOKIT\Onboarding(
		array(
			'path'          => RESTALER_PATH,
			'plugin_slug'   => 'restock-alerts-for-woocommerce',
			'steps'         => $steps,
			'redirect_page' => 'stobokit-review-follow-up-settings',
			'page_slug'     => 'stobokit-onboarding-restaler',
			'option_prefix' => 'restaler_onboarding',
		)
	);

}
add_action( 'plugins_loaded', 'restaler_init' );

