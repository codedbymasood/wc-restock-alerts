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
		$fields = array(
			esc_html__( 'Mail Settings', 'plugin-slug' ) => array(
				array(
					'id'    => 'stobokit_email_from_name',
					'label' => esc_html__( 'From Name', 'plugin-slug' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'stobokit_email_from_email',
					'label' => esc_html__( 'From Email Address', 'plugin-slug' ),
					'type'  => 'text',
				),
			),
			esc_html__( 'General Settings', 'plugin-slug' ) => array(
				array(
					'id'      => 'restaler_show_signup_message',
					'label'   => esc_html__( 'Show Signup Message', 'plugin-slug' ),
					'type'    => 'checkbox',
					'default' => '0',
				),
				array(
					'id'      => 'restaler_signup_message',
					'label'   => esc_html__( 'Signup Message', 'plugin-slug' ),
					'type'    => 'text',
					'default' => esc_html__( 'Want a reminder when more arrives? Sign up below.', 'plugin-slug' ),
				),
				array(
					'id'      => 'restaler_enable_stock_threshold',
					'label'   => esc_html__( 'Stock Threshold', 'plugin-slug' ),
					'type'    => 'checkbox',
					'default' => '0',
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_stock_threshold_count',
					'label'   => esc_html__( 'Stock Threshold Count', 'plugin-slug' ),
					'type'    => 'number',
					'default' => 3,
					'pro'     => true,
				),
			),
			esc_html__( 'Followup Email Settings', 'plugin-slug' ) => array(
				array(
					'id'      => 'restaler_enable_followup',
					'label'   => esc_html__( 'Enable Followup', 'plugin-slug' ),
					'type'    => 'checkbox',
					'default' => '0',
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_first_followup_days',
					'label'   => esc_html__( 'First Followup Days', 'plugin-slug' ),
					'type'    => 'number',
					'default' => 2,
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_second_followup_days',
					'label'   => esc_html__( 'Second Followup Days', 'plugin-slug' ),
					'type'    => 'number',
					'default' => 3,
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_discount_type',
					'label'   => esc_html__( 'Discount Type', 'plugin-slug' ),
					'type'    => 'select',
					'options' => array(
						'percent'    => esc_html__( 'Percentage discount', 'plugin-slug' ),
						'fixed_cart' => esc_html__( 'Fixed cart discount', 'plugin-slug' ),
					),
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_discount_amount',
					'label'   => esc_html__( 'Discount Amount', 'plugin-slug' ),
					'type'    => 'number',
					'default' => 20,
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_coupon_expires_in',
					'label'   => esc_html__( 'Coupon Expires In', 'plugin-slug' ),
					'type'    => 'number',
					'default' => 3,
					'pro'     => true,
				),
			),
		);

		new Settings(
			'plugin-slug',
			'stobokit-restaler-notify-list',          // Parent menu slug.
			'stobokit-restaler-notify-list-settings', // menu slug.
			esc_html__( 'Settings', 'plugin-slug' ),
			esc_html__( 'Settings', 'plugin-slug' ),
			'manage_options',
			'',
			0,
			$fields
		);
	}
);
