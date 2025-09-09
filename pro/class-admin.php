<?php
/**
 * Admin class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Admin_Pro {

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_filter( 'stobokit_product_lists', array( $this, 'add_product' ) );
		add_action( 'restaler_alert_before_email_sent', array( $this, 'before_alert_email_sent' ), 10, 2 );
	}

	public function add_product( $products = array() ) {
		$products['plugin-slug']['name'] = esc_html__( 'Plugin Name', 'plugin-slug' );
		$products['plugin-slug']['id']   = 74;

		return $products;
	}

	public function before_alert_email_sent( $row = array(), $product = null ) {

		$product_type = $product->get_type();

		if ( 'variable' === $product_type ) {

			$variation_id = isset( $row['variation_id'] ) ? $row['variation_id'] : 0;

			$variations = wc_get_product( $variation_id );

			$stock_status = $variations->get_stock_status();

			if ( 'instock' === $stock_status ) {
				Admin::instance()->send_notify_emails( $row );
				Admin::instance()->change_status_to_email_sent( $row );

				/**
				 * After restock alert email sent.
				 */
				do_action( 'restaler_alert_email_sent', $row, $product );
			}
		}
	}
}

new Admin_Pro();
