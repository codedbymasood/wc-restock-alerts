<?php
/**
 * Settings class.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace SBK_RAW;

defined( 'ABSPATH' ) || exit;

add_action( 
	'init',
	function() {
		$fields = array(
			esc_html__( 'Mail Settings', 'restock-alerts-for-woocommerce' ) => array(
				array(
					'id'    => 'email_from_name',
					'label' => esc_html__( 'From Name', 'restock-alerts-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'email_from_address',
					'label' => esc_html__( 'From Address', 'restock-alerts-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'id'      => 'sbk_raw_email_subject',
					'label'   => esc_html__( 'From Address', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Back in Stock!', 'restock-alerts-for-woocommerce' ),
				),
			),
			esc_html__( 'Followup Email', 'restock-alerts-for-woocommerce' ) => array(				
				array(
					'id'      => 'sbk_raw_enable_followup',
					'label'   => esc_html__( 'Enable Followup', 'restock-alerts-for-woocommerce' ),
					'type'    => 'checkbox',
					'default' => '1',
				),
				array(
					'id'      => 'sbk_raw_first_followup_days',
					'label'   => esc_html__( 'First Followup Days', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 2,
				),
				array(
					'id'      => 'sbk_raw_first_followup_email_subject',
					'label'   => esc_html__( 'First Followup Email Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Just a Quick Reminder', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'sbk_raw_second_followup_days',
					'label'   => esc_html__( 'Second Followup Days', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 3,
				),
				array(
					'id'      => 'sbk_raw_second_followup_email_subject',
					'label'   => esc_html__( 'Second Followup Email Subject', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => esc_html__( 'Last Chance! 20% Off Ends in 3 Days', 'restock-alerts-for-woocommerce' ),
				),
				array(
					'id'      => 'sbk_raw_discount_type',
					'label'   => esc_html__( 'Discount Type', 'restock-alerts-for-woocommerce' ),
					'type'    => 'select',
					'options' => array(
						'percent'    => esc_html__( 'Percentage discount', 'restock-alerts-for-woocommerce' ),
						'fixed_cart' => esc_html__( 'Fixed cart discount', 'restock-alerts-for-woocommerce' ),
					),
				),
				array(
					'id'      => 'sbk_raw_discount_amount',
					'label'   => esc_html__( 'Discount Amount', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 20,
				),
				array(
					'id'      => 'sbk_raw_coupon_expires_in',
					'label'   => esc_html__( 'Coupon Expires In', 'restock-alerts-for-woocommerce' ),
					'type'    => 'text',
					'default' => 3,
				),
			),
		);

		new Settings(
			'notify-list',          // Parent menu slug.
			'notify-list-settings', // menu slug.
			esc_html__( 'Settings', 'restock-alerts-for-woocommerce' ),
			esc_html__( 'Settings', 'restock-alerts-for-woocommerce' ),
			'manage_options',
			$fields
		);
	}
);
