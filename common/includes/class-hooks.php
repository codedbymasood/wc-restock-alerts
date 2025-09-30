<?php
/**
 * Hooks class.
 *
 * @package plugin-slug\common\includes\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

use Pelago\Emogrifier\CssInliner;

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
		add_action( 'restaler_alert_email_sent', array( $this, 'alert_email_sent' ), 10, 2 );
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
				$variation_name = false;

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
			'coupon_code',
			function ( $args ) {
				return isset( $args['coupon'] ) ? $args['coupon'] : '';
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
}

new Hooks();
