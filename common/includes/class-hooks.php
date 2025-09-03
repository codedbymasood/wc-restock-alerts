<?php
/**
 * Hooks class.
 *
 * @package restock-alerts-for-woocommerce\includes\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Hooks {

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		$this->register_mail_tags();
	}

	public function register_mail_tags() {
		restaler()->emailer->register_shortcode(
			'verify_url',
			function ( $args ) {
				return isset( $args['verify_url'] ) ? $args['verify_url'] : '';
			}
		);

		restaler()->emailer->register_shortcode(
			'verify_url_btn',
			function ( $args ) {

				$verify_btn_text = get_option( 'verify_btn_text', esc_html__( 'Verify Email', 'restock-alerts-for-woocommerce' ) );

				$verify_url = isset( $args['verify_url'] ) ? $args['verify_url'] : '';

				if ( $verify_url ) {
					return sprintf( '<a href="%s" class="button">%s</a>', esc_url( $verify_url ), esc_html( $verify_btn_text ) );
				}
				return '';
			}
		);

		restaler()->emailer->register_shortcode(
			'product_name',
			function ( $args ) {

				$verify_btn_text = get_option( 'verify_btn_text', esc_html__( 'Verify Email', 'restock-alerts-for-woocommerce' ) );

				$product_id = isset( $args['product_id'] ) ? $args['product_id'] : '';

				if ( $product_id ) {
					$product = wc_get_product( $product_id );

					return sprintf( '<span class="product-title">%s</span>', esc_html( $product->get_name() ) );
				}
				return '';
			}
		);

		restaler()->emailer->register_shortcode(
			'buy_now',
			function ( $args ) {

				$buy_now_btn_text = get_option( 'buy_now_btn_text', esc_html__( 'Buy Now', 'restock-alerts-for-woocommerce' ) );

				$product_id = isset( $args['product_id'] ) ? $args['product_id'] : '';

				if ( $product_id ) {
					return sprintf( '<a href="%s" class="button">%s</a>', esc_url( get_permalink( $product_id ) ), esc_html( $buy_now_btn_text ) );
				}
				return '';
			}
		);
	}
}

new Hooks();
