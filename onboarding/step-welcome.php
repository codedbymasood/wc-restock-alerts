<?php
/**
 * Onboarding welcome page.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace PRODEXMA;

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
			<li><strong><?php esc_html_e( 'Smart expiry rules', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Set once, automate forever', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Instant product updates', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Hide expired items automatically', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Proactive alerts', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Get notified before expiration', 'plugin-slug' ); ?></li>
			<li><strong><?php esc_html_e( 'Happy customers', 'plugin-slug' ); ?></strong><?php esc_html_e( ' - Always show fresh, available products.', 'plugin-slug' ); ?></li>
		</ul>
	</div>
	<p><?php esc_html_e( 'In just a few steps, you\'ll be ready to set expiry rules for your products.', 'plugin-slug' ); ?></p>
</div>
