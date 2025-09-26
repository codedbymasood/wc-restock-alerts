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
		add_filter( 'restaler_email_templates_settings', array( $this, 'email_templates_settings' ) );
	}

	public function email_templates_settings( $fields = array() ) {

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

		$fields['First Follow Up Email'] = array(
			array(
				'id'      => 'restaler_first_follow_up_email_subject',
				'label'   => esc_html__( 'Subject', 'plugin-slug' ),
				'type'    => 'text',
				'default' => esc_html__( 'Still on your mind?', 'plugin-slug' ),
			),
			array(
				'id'      => 'restaler_first_follow_up_email_heading',
				'label'   => esc_html__( 'Heading', 'plugin-slug' ),
				'type'    => 'text',
				'default' => esc_html__( 'Still on your mind?', 'plugin-slug' ),
			),
			array(
				'id'             => 'restaler_first_follow_up_email_content',
				'label'          => esc_html__( 'Email Content', 'plugin-slug' ),
				'type'           => 'richtext_editor',
				'options'        => array( 'html' ),
				'default_editor' => 'html',
				'default'        => array(
					'html' => $first_follow_up_email_html,
					'css'  => '',
				),
				'description'    => 'You can use {product_name}, {buy_now}, {customer_name}, {site_name}, {site_url} in the editor.',
			),
			array(
				'id'      => 'restaler_first_follow_up_email_footer_text',
				'label'   => esc_html__( 'Footer Text', 'plugin-slug' ),
				'type'    => 'textarea',
				'default' => esc_html__( 'Thanks again for your interest!', 'plugin-slug' ),
			),
		);

		$fields['Second Follow Up Email'] = array(
			array(
				'id'      => 'restaler_second_follow_up_email_subject',
				'label'   => esc_html__( 'Subject', 'plugin-slug' ),
				'type'    => 'text',
				'default' => esc_html__( 'Last Chance! {discount} Off Ends in {coupon_expires} Days', 'plugin-slug' ),
			),
			array(
				'id'      => 'restaler_second_follow_up_email_heading',
				'label'   => esc_html__( 'Heading', 'plugin-slug' ),
				'type'    => 'text',
				'default' => esc_html__( 'Your {discount} Discount Is About to Expire', 'plugin-slug' ),
			),
			array(
				'id'             => 'restaler_second_follow_up_email_content',
				'label'          => esc_html__( 'Email Content', 'plugin-slug' ),
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
				'label'   => esc_html__( 'Footer Text', 'plugin-slug' ),
				'type'    => 'textarea',
				'default' => esc_html__( 'Thanks again for your interest!', 'plugin-slug' ),
			),
		);

		return $fields;
	}

	public function add_product( $products = array() ) {
		$products['plugin-slug']['name'] = esc_html__( 'Plugin Name', 'plugin-slug' );
		$products['plugin-slug']['id']   = 74;

		return $products;
	}
}

new Admin_Pro();
