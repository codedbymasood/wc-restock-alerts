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
	function() {
		$fields = array(
			esc_html__( 'Mail Settings', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'    => 'stobokit_email_from_name',
					'label' => esc_html__( 'From Name', 'restock-alerts-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'stobokit_email_from_email',
					'label' => esc_html__( 'From Email Address', 'restock-alerts-for-woocommerce' ),
					'type'  => 'text',
				),
			),
			esc_html__( 'Followup Email Settings', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'      => 'restaler_enable_followup',
					'label'   => esc_html__( 'Enable Followup', 'restock-alerts-for-woocommerce' ),
					'type'    => 'checkbox',
					'default' => '1',
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_first_followup_days',
					'label'   => esc_html__( 'First Followup Days', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 2,
					'pro'     => true,
				),
				array(
					'id'      => 'restaler_second_followup_days',
					'label'   => esc_html__( 'Second Followup Days', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 3,
				),
				array(
					'id'      => 'restaler_discount_type',
					'label'   => esc_html__( 'Discount Type', 'restock-alerts-for-woocommerce' ),
					'type'    => 'select',
					'options' => array(
						'percent'    => esc_html__( 'Percentage discount', 'restock-alerts-for-woocommerce' ),
						'fixed_cart' => esc_html__( 'Fixed cart discount', 'restock-alerts-for-woocommerce' ),
					),
				),
				array(
					'id'      => 'restaler_discount_amount',
					'label'   => esc_html__( 'Discount Amount', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 20,
				),
				array(
					'id'      => 'restaler_coupon_expires_in',
					'label'   => esc_html__( 'Coupon Expires In', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 3,
				),
			),
		);

		new Settings(
			'restock-alerts-for-woocommerce',
			'stobokit-restaler-notify-list',          // Parent menu slug.
			'stobokit-restaler-notify-list-settings', // menu slug.
			esc_html__( 'Settings', 'restock-alerts-for-woocommerce' ),
			esc_html__( 'Settings', 'restock-alerts-for-woocommerce' ),
			'manage_options',
			'',
			0,
			$fields
		);
	}
);
