<?php
/**
 * Settings class.
 *
 * @package plugin-slug\admin\
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

{product_name}{variation}
{buy_now}

Don't wait too long, popular products sell out quickly!

Warmly,
The {site_name} Team";

		$fields = array(
			esc_html__( 'Verification Email', 'plugin-slug' ) => array(
				array(
					'id'      => 'restaler_verification_email_subject',
					'label'   => esc_html__( 'Subject', 'plugin-slug' ),
					'type'    => 'text',
					'default' => esc_html__( 'Send verification email', 'plugin-slug' ),
				),
				array(
					'id'      => 'restaler_verification_email_heading',
					'label'   => esc_html__( 'Heading', 'plugin-slug' ),
					'type'    => 'text',
					'default' => esc_html__( 'Confirm your email', 'plugin-slug' ),
				),
				array(
					'id'             => 'restaler_verification_email_content',
					'label'          => esc_html__( 'Email Content', 'plugin-slug' ),
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
					'label'   => esc_html__( 'Footer Text', 'plugin-slug' ),
					'type'    => 'textarea',
					'default' => esc_html__(
						'You\'re receiving this email because you registered with {site_name}.

If you didn\'t request this, please ignore it.',
						'plugin-slug'
					),
				),
			),
			esc_html__( 'Back In Stock Email', 'plugin-slug' ) => array(
				array(
					'id'      => 'restaler_back_in_stock_email_subject',
					'label'   => esc_html__( 'Subject', 'plugin-slug' ),
					'type'    => 'text',
					'default' => esc_html__( 'Back in Stock!', 'plugin-slug' ),
				),
				array(
					'id'      => 'restaler_back_in_stock_email_heading',
					'label'   => esc_html__( 'Heading', 'plugin-slug' ),
					'type'    => 'text',
					'default' => esc_html__( 'Back in Stock!', 'plugin-slug' ),
				),
				array(
					'id'             => 'restaler_back_in_stock_email_content',
					'label'          => esc_html__( 'Email Content', 'plugin-slug' ),
					'type'           => 'richtext_editor',
					'options'        => array( 'html' ),
					'default_editor' => 'html',
					'default'        => array(
						'html' => $back_in_stock_email_html,
						'css'  => '',
					),
					'description'    => 'You can use {product_name}, {variation}, {buy_now}, {customer_name}, {site_name}, {site_url} in the editor.',
				),
				array(
					'id'      => 'restaler_back_in_stock_email_footer_text',
					'label'   => esc_html__( 'Footer Text', 'plugin-slug' ),
					'type'    => 'textarea',
					'default' => '',
				),
			),
		);

		new Settings(
			'plugin-slug',
			'stobokit-restaler-notify-list',     // Parent menu slug.
			'stobokit-restaler-email-templates', // menu slug.
			esc_html__( 'Email Templates', 'plugin-slug' ),
			esc_html__( 'Email Templates', 'plugin-slug' ),
			'manage_options',
			'',
			0,
			apply_filters( 'restaler_email_templates_settings', $fields ),
		);
	}
);
