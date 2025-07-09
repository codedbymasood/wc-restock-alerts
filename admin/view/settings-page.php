<?php
/**
 * Settings class.
 *
 * @package product-availability-notifier-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

add_action( 
	'init',
	function() {
		$fields = array(
			esc_html__( 'General', 'product-availability-notifier-for-woocommerce' ) => array(
				array(
					'id'    => 'paw_from_address',
					'label' => esc_html__( 'From Address', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'paw_enable_followup',
					'label' => esc_html__( 'Enable Followup', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => 'paw_first_followup_days',
					'label' => esc_html__( 'First Followup Days', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'paw_second_followup_days',
					'label' => esc_html__( 'Second Followup Days', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'paw_attach_discount_on_followup',
					'label' => esc_html__( 'Attach Discount on Followup', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => 'paw_discount_type',
					'label'   => esc_html__( 'Discount Type', 'product-availability-notifier-for-woocommerce' ),
					'type'    => 'select',
					'options' => array(
						'percentage' => esc_html__( 'Percentage', 'product-availability-notifier-for-woocommerce' ),
						'fixed'      => esc_html__( 'Fixed', 'product-availability-notifier-for-woocommerce' ),
					),
				),
				array(
					'id'    => 'paw_discount_amount',
					'label' => esc_html__( 'Discount Amount', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'paw_coupon_expires_in',
					'label' => esc_html__( 'Coupon Expires In', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
			),
		);

		new Settings(
			'notify-list',          // Parent menu slug.
			'notify-list-settings', // menu slug.
			esc_html__( 'Settings', 'product-availability-notifier-for-woocommerce' ),
			esc_html__( 'Settings', 'product-availability-notifier-for-woocommerce' ),
			'manage_options',
			$fields
		);
	}
);
