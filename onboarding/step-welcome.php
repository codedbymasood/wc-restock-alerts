<?php
/**
 * Onboarding welcome page.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PRODEXMA;

defined( 'ABSPATH' ) || exit;
?>

<div class="intro">
	<div class="header">
		<h2><?php esc_html_e( 'Welcome to Restock Alerts', 'restock-alerts-for-woocommerce' ); ?></h2>
		<p class="sub-heading"><strong><?php esc_html_e( 'Thank you for installing Restock Alerts for WooCommerce!', 'restock-alerts-for-woocommerce' ); ?></strong></p>
	</div>

	<div class="section">
		<h3><?php esc_html_e( 'Why you\'ll love this?', 'restock-alerts-for-woocommerce' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( 'Smart expiry rules', 'restock-alerts-for-woocommerce' ); ?></strong><?php esc_html_e( ' - Set once, automate forever', 'restock-alerts-for-woocommerce' ); ?></li>
			<li><strong><?php esc_html_e( 'Instant product updates', 'restock-alerts-for-woocommerce' ); ?></strong><?php esc_html_e( ' - Hide expired items automatically', 'restock-alerts-for-woocommerce' ); ?></li>
			<li><strong><?php esc_html_e( 'Proactive alerts', 'restock-alerts-for-woocommerce' ); ?></strong><?php esc_html_e( ' - Get notified before expiration', 'restock-alerts-for-woocommerce' ); ?></li>
			<li><strong><?php esc_html_e( 'Happy customers', 'restock-alerts-for-woocommerce' ); ?></strong><?php esc_html_e( ' - Always show fresh, available products.', 'restock-alerts-for-woocommerce' ); ?></li>
		</ul>
	</div>
	<p><?php esc_html_e( 'In just a few steps, you\'ll be ready to set expiry rules for your products.', 'restock-alerts-for-woocommerce' ); ?></p>
</div>
