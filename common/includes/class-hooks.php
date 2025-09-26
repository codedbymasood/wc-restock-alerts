<?php
/**
 * Hooks class.
 *
 * @package plugin-slug\common\includes\
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

				$verify_btn_text = get_option( 'verify_btn_text', esc_html__( 'Verify Email', 'plugin-slug' ) );

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

				$verify_btn_text = get_option( 'verify_btn_text', esc_html__( 'Verify Email', 'plugin-slug' ) );

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

				$buy_now_btn_text = get_option( 'buy_now_btn_text', esc_html__( 'Buy Now', 'plugin-slug' ) );

				$product_id = isset( $args['product_id'] ) ? $args['product_id'] : '';

				if ( $product_id ) {
					return sprintf( '<a href="%s" class="button">%s</a>', esc_url( get_permalink( $product_id ) ), esc_html( $buy_now_btn_text ) );
				}
				return '';
			}
		);

		restaler()->emailer->register_shortcode(
			'variation',
			function ( $args ) {
				$variation_id = isset( $args['variation_id'] ) ? $args['variation_id'] : 0;
				$variation    = wc_get_product( $variation_id );

				if ( $variation && $variation->is_type( 'variation' ) ) {
					// Get variation attributes.
					$attributes = $variation->get_variation_attributes();

					// Get formatted variation name.
					$variation_name = wc_get_formatted_variation( $attributes, true );
				}

				return ( $variation_name ) ? sprintf( ' Variation: %s', esc_html( $variation_name ) ) : '';
			}
		);
	}
}

new Hooks();
