<?php
/**
 * Admin class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

use Pelago\Emogrifier\CssInliner;
use STOBOKIT\Utils as Core_Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Hooks_Pro {

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		$this->register_mail_tags();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin-slug_is_pro_active', '__return_true' );

		add_filter( 'restaler_show_notify_form', array( $this, 'show_notify_form' ), 10, 2 );

		add_action( 'restaler_alert_email_sent', array( $this, 'alert_email_sent' ), 10, 2 );
		add_action( 'woocommerce_variable_add_to_cart', array( $this, 'append_notify_form' ), 35 );
	}

	public function enqueue_scripts() {
		$enable_stock_threshold = get_option( 'restaler_enable_stock_threshold', '' );
		$stock_threshold_count  = get_option( 'restaler_stock_threshold_count', 3 );

		wp_localize_script(
			'jquery',
			'restaler',
			array(
				'enable_stock_threshold' => $enable_stock_threshold,
				'stock_threshold_count'  => $stock_threshold_count,
			)
		);

		wp_enqueue_script( 'restaler-pro', plugin_dir_url( __FILE__ ) . 'assets/js/pro.js', array( 'jquery' ), '1.0', true );
	}

	public function register_mail_tags() {
		restaler()->emailer->register_shortcode(
			'coupon_code',
			function ( $args ) {
				return isset( $args['coupon'] ) ? $args['coupon'] : '';
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

		restaler()->emailer->register_shortcode(
			'coupon_expires',
			function ( $args ) {
				return isset( $args['coupon_expires_in'] ) ? $args['coupon_expires_in'] : '';
			}
		);

		restaler()->emailer->register_shortcode(
			'discount',
			function ( $args ) {
				$discount_type = isset( $args['discount_type'] ) ? $args['discount_type'] : '';
				$amount        = isset( $args['amount'] ) ? $args['amount'] : '';

				return ( 'percent' === $discount_type ) ? $amount . '%' : $amount;
			}
		);
	}

	public function alert_email_sent( $row = array(), $product = array() ) {

		$enable_followup = get_option( 'restaler_enable_followup', '' );

		$discount_type        = get_option( 'restaler_discount_type', 'percent' );
		$amount               = get_option( 'restaler_discount_amount', 20 );
		$first_followup_days  = get_option( 'restaler_first_followup_days', 2 );
		$second_followup_days = get_option( 'restaler_second_followup_days', 3 );
		$coupon_expires_in    = get_option( 'restaler_coupon_expires_in', 3 );

		$first_followup  = time() + ( $first_followup_days * DAY_IN_SECONDS ); // 2 days later
		$second_followup = $first_followup + ( $second_followup_days * DAY_IN_SECONDS ); // 5 days total

		$coupon_expires      = $second_followup + ( $coupon_expires_in * DAY_IN_SECONDS ); // Add 3 days.
		$coupon_expires_date = gmdate( 'd-m-Y', $coupon_expires );

		$args = array(
			'product'             => $product,
			'discount_type'       => $discount_type,
			'amount'              => $amount,
			'coupon_expires_date' => $coupon_expires_date,
		);

		$coupon = Utils::generate_discount( $args );

		$email      = $row['email'];
		$product_id = $row['product_id'];

		$first_follow_up_subject  = get_option( 'restaler_first_follow_up_email_subject', esc_html__( 'Just a Quick Reminder', 'plugin-slug' ) );
		$second_follow_up_subject = get_option( 'restaler_second_follow_up_email_subject', esc_html__( 'Last Chance! 20% Off Ends in 3 Days', 'plugin-slug' ) );

		$first_follow_up_heading     = get_option( 'restaler_first_follow_up_email_heading', esc_html__( 'Confirm your email', 'plugin-slug' ) );
		$first_follow_up_footer_text = get_option(
			'restaler_first_follow_up_email_footer_text',
			esc_html__(
				'You\'re receiving this email because you registered with {site_name}.

If you didn\'t request this, please ignore it.',
				'plugin-slug'
			)
		);

		$first_follow_up_content = get_option(
			'restaler_first_follow_up_email_content',
			"Hi{customer_name},

We just wanted to check in - did you get a chance to explore {product_name}?

If you're still thinking it over, no rush! We're here if you have any questions or need help deciding if it's the right fit for you.

{buy_now}

Sometimes all it takes is a second look.

Warmly,
The {site_name} Team"
		);

		$second_follow_up_heading     = get_option( 'restaler_second_follow_up_email_heading', esc_html__( 'Confirm your email', 'plugin-slug' ) );
		$second_follow_up_footer_text = get_option(
			'restaler_second_follow_up_email_footer_text',
			esc_html__(
				'You\'re receiving this email because you registered with {site_name}.

If you didn\'t request this, please ignore it.',
				'plugin-slug'
			)
		);

		$second_follow_up_content = get_option(
			'restaler_second_follow_up_email_content',
			"Hi{customer_name},

Just a quick reminder - your {discount} off discount code is expiring in {coupon_expires} days

If you've been thinking about getting {product_name}, now's the perfect time. This is your last chance to grab it at a lower price before the offer disappears.

Use code: {coupon_code}
Expires in: {coupon_expires} days

{buy_now}

Don't miss out - after this, it's back to full price.

Warmly,
The {site_name} Team"
		);

		$first_followup_content = restaler()->templates->get_template(
			'email/email-content.php',
			array(
				'heading'     => $first_follow_up_heading,
				'content'     => $first_follow_up_content['html'],
				'footer_text' => $first_follow_up_footer_text,
			)
		);

		// CssInliner loads from WooCommerce.
		$first_followup_html = CssInliner::fromHtml( $first_followup_content )->inlineCss()->render();

		$second_followup_content = restaler()->templates->get_template(
			'email/email-content.php',
			array(
				'heading'     => $second_follow_up_heading,
				'content'     => $second_follow_up_content['html'],
				'footer_text' => $second_follow_up_footer_text,
			)
		);

		// CssInliner loads from WooCommerce.
		$second_followup_html = CssInliner::fromHtml( $second_followup_content )->inlineCss()->render();

		$sequence_id = restaler()->emailer->create_followup_sequence(
			$row['email'],
			array(
				array(
					'days'    => $first_followup,
					'subject' => $first_follow_up_subject,
					'message' => $first_followup_html,
				),
				array(
					'days'    => $second_followup,
					'subject' => $second_follow_up_subject,
					'message' => $second_followup_html,
				),
			),
			array(
				'product'           => $product,
				'coupon'            => $coupon,
				'discount_type'     => $discount_type,
				'amount'            => $amount,
				'coupon_expires_in' => $coupon_expires_in,
			)
		);
	}

	public function show_notify_form( $show = false, $product = null ) {
		$enable_stock_threshold = get_option( 'restaler_enable_stock_threshold', '' );
		$stock_threshold_count  = get_option( 'restaler_stock_threshold_count', 3 );

		if ( Core_Utils::string_to_bool( $enable_stock_threshold ) ) {
			$stock_quantity = $product->get_stock_quantity();

			if ( $stock_quantity <= $stock_threshold_count ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Append notify form fields after the `out of stock` notice.
	 *
	 * @return void
	 */
	public function append_notify_form() {
		global $product;

		$product_type = $product->get_type();

		$hide = true;

		if ( 'variable' === $product_type ) {
			$available_variations = $product->get_available_variations();
			if ( empty( $available_variations ) && false !== $available_variations ) {
				$hide = false;
			}

			restaler()->templates->include_template(
				'notify-form.php',
				array(
					'product' => $product,
					'type'    => $product_type,
					'hide'    => $hide,
				)
			);
		}
	}
}

new Hooks_Pro();
