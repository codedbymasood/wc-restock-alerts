<?php
/**
 * Admin class.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

$args = array(
	'file'      => RESTALER_PLUGIN_FILE,
	'slug'      => 'restock-alerts-for-woocommerce',
	'version'   => RESTALER_VERSION,
	'license'   => get_option( 'restock-alerts-for-woocommerce_license_key', '' ),
	'item_name' => 'Restock Alerts for WooCommerce',
	'item_id'   => 74,
);

new Update_Handler( $args );
