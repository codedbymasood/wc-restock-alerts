<?php
/**
 * Onboarding welcome page.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="intro">
	<div class="header">
		<h2><?php esc_html_e( 'Welcome to Restock Alerts', 'plugin-slug' ); ?></h2>
		<p class="sub-heading"><strong><?php esc_html_e( 'Thank you for installing Plugin Name!', 'plugin-slug' ); ?></strong></p>
	</div>

	<div class="section">
		<h3><?php esc_html_e( 'Why you\'ll love this?', 'plugin-slug' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( 'Automatic restock alerts', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Notify customers instantly when items are back in stock', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Smart stock monitoring', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - CRON scheduling & threshold alerts ensure no restock goes unnoticed', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Beautiful email campaigns', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Customizable templates with professional designs & dynamic mail tags', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Subscriber & email logs', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Track alerts, manage subscribers, and export lists in CSV', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Seamless WooCommerce integration', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Works with simple & variable products, no third-party services required', 'plugin-slug' ); ?></li>
		</ul>
	</div>
	<p><?php esc_html_e( 'In just a few steps, you\'ll be ready to set expiry rules for your products.', 'plugin-slug' ); ?></p>
</div>
