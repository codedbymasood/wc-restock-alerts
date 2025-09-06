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
	}

	public function add_product( $products = array() ) {
		$products['plugin-slug']['name'] = esc_html__( 'Plugin Name', 'plugin-slug' );
		$products['plugin-slug']['id']   = 74;

		return $products;
	}
}

new Admin_Pro();
