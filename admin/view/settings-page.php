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
					'id'    => 'from_address',
					'label' => esc_html__( 'From Address', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'enable_followup',
					'label' => esc_html__( 'Enable Followup', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => 'first_followup_days',
					'label' => esc_html__( 'First Followup Days', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'second_followup_days',
					'label' => esc_html__( 'Second Followup Days', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'attach_discount_on_followup',
					'label' => esc_html__( 'Attach Discount on Followup', 'product-availability-notifier-for-woocommerce' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => 'discount_percentage',
					'label' => esc_html__( 'Discount Percentage', 'product-availability-notifier-for-woocommerce' ),
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
