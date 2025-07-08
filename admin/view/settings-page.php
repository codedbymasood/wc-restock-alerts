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

$fields = array(
	esc_html__( 'General', 'product-availability-notifier-for-woocommerce' ) => array(
		array(
			'id'    => 'site_subtitle',
			'label' => esc_html__( 'Site Subtitle', 'product-availability-notifier-for-woocommerce' ),
			'type'  => 'text',
		),
		array(
			'id'    => 'site_description',
			'label' => esc_html__( 'Site Description', 'product-availability-notifier-for-woocommerce' ),
			'type'  => 'textarea',
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


