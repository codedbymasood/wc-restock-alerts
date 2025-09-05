<?php
/**
 * Settings class.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		$verification_email_html = "Hi{customer_name},

Thanks for signing up. Please confirm your email address by clicking the button below:

{verify_url_btn}

If the button doesn't work, copy and paste this link into your browser:

{verify_url}

This verification link will expire soon. If you did not request this, please ignore this email.

Warmly,
The {site_name} Team";

		$back_in_stock_email_html = "The product you were waiting for is now back in stock

{product_name}
{buy_now}

Don't wait too long, popular products sell out quickly!

Warmly,
The {site_name} Team";

		$first_follow_up_email_html = "Hi{customer_name},

We just wanted to check in - did you get a chance to explore {product_name}?

If you're still thinking it over, no rush! We're here if you have any questions or need help deciding if it's the right fit for you.

{buy_now}

Sometimes all it takes is a second look.

Warmly,
The {site_name} Team";

		$second_follow_up_email_html = "Hi{customer_name},

Just a quick reminder - your {discount} off discount code is expiring in {coupon_expires} days

If you've been thinking about getting {product_name}, now's the perfect time. This is your last chance to grab it at a lower price before the offer disappears.

Use code: {coupon_code}
Expires in: {coupon_expires} days

{buy_now}

Don't miss out - after this, it's back to full price.

Warmly,
The {site_name} Team";

		$fields = array(
			esc_html__( 'Verification Email', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'      => 'restaler_verification_email_subject',
					'label'   => esc_html__( 'Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Send verification email', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'restaler_verification_email_heading',
					'label'   => esc_html__( 'Heading', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Confirm your email', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'             => 'restaler_verification_email_content',
					'label'          => esc_html__( 'Email Content', 'restock-alerts-for-woocommerce' ),
					'type'           => 'richtext_editor',
					'options'        => array( 'html' ),
					'default_editor' => 'html',
					'default'        => array(
						'html' => $verification_email_html,
						'css'  => '',
					),
					'description'    => 'You can use {verify_url}, {verify_url_btn}, {customer_name}, {site_name}, {site_url} in the editor.',
				),
				array(
					'id'      => 'restaler_verification_email_footer_text',
					'label'   => esc_html__( 'Footer Text', 'restock-alerts-for-woocommerce' ),
					'type'    => 'textarea',
					'default' => esc_html__(
						'You\'re receiving this email because you registered with {site_name}.

If you didn\'t request this, please ignore it.',
						'restock-alerts-for-woocommerce'
					),
				),
			),
			esc_html__( 'Back In Stock Email', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'      => 'restaler_back_in_stock_email_subject',
					'label'   => esc_html__( 'Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Back in Stock!', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'restaler_back_in_stock_email_heading',
					'label'   => esc_html__( 'Heading', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Back in Stock!', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'             => 'restaler_back_in_stock_email_content',
					'label'          => esc_html__( 'Email Content', 'restock-alerts-for-woocommerce' ),
					'type'           => 'richtext_editor',
					'options'        => array( 'html' ),
					'default_editor' => 'html',
					'default'        => array(
						'html' => $back_in_stock_email_html,
						'css'  => '',
					),
					'description'    => 'You can use {verify_url}, {verify_url_btn}, {customer_name}, {site_name}, {site_url} in the editor.',
				),
				array(
					'id'      => 'restaler_back_in_stock_email_footer_text',
					'label'   => esc_html__( 'Footer Text', 'restock-alerts-for-woocommerce' ),
					'type'    => 'textarea',
					'default' => '',
				),
			),
			esc_html__( 'First Follow Up Email', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'      => 'restaler_first_follow_up_email_subject',
					'label'   => esc_html__( 'Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Still on your mind?', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'restaler_first_follow_up_email_heading',
					'label'   => esc_html__( 'Heading', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Still on your mind?', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'             => 'restaler_first_follow_up_email_content',
					'label'          => esc_html__( 'Email Content', 'restock-alerts-for-woocommerce' ),
					'type'           => 'richtext_editor',
					'options'        => array( 'html' ),
					'default_editor' => 'html',
					'default'        => array(
						'html' => $first_follow_up_email_html,
						'css'  => '',
					),
					'description'    => 'You can use {verify_url}, {verify_url_btn}, {customer_name}, {site_name}, {site_url} in the editor.',
				),
				array(
					'id'      => 'restaler_first_follow_up_email_footer_text',
					'label'   => esc_html__( 'Footer Text', 'restock-alerts-for-woocommerce' ),
					'type'    => 'textarea',
					'default' => esc_html__( 'Thanks again for your interest!', 'restock-alerts-for-woocommerce' ),
				),
			),
			esc_html__( 'Second Follow Up Email', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'      => 'restaler_second_follow_up_email_subject',
					'label'   => esc_html__( 'Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Last Chance! {discount} Off Ends in {coupon_expires} Days', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'restaler_second_follow_up_email_heading',
					'label'   => esc_html__( 'Heading', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Your {discount} Discount Is About to Expire', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'             => 'restaler_second_follow_up_email_content',
					'label'          => esc_html__( 'Email Content', 'restock-alerts-for-woocommerce' ),
					'type'           => 'richtext_editor',
					'options'        => array( 'html' ),
					'default_editor' => 'html',
					'default'        => array(
						'html' => $second_follow_up_email_html,
						'css'  => '',
					),
					'description'    => 'You can use {product_name}, {buy_now}, {discount}, {coupon_expires}, {coupon_code}, {customer_name}, {site_name}, {site_url} in the editor.',
				),
				array(
					'id'      => 'restaler_second_follow_up_email_footer_text',
					'label'   => esc_html__( 'Footer Text', 'restock-alerts-for-woocommerce' ),
					'type'    => 'textarea',
					'default' => esc_html__( 'Thanks again for your interest!', 'restock-alerts-for-woocommerce' ),
				),
			),
		);

		new Settings(
			'restock-alerts-for-woocommerce',
			'stobokit-restaler-notify-list',              // Parent menu slug.
			'stobokit-restaler-email-templates', // menu slug.
			esc_html__( 'Email Templates', 'restock-alerts-for-woocommerce' ),
			esc_html__( 'Email Templates', 'restock-alerts-for-woocommerce' ),
			'manage_options',
			'',
			0,
			$fields
		);
	}
);
