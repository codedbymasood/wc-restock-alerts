<?php
/**
 * Hooks class.
 *
 * @package plugin-slug\common\includes\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

// Include the onboarding class.
if ( ! class_exists( '\STOBOKIT\Onboarding' ) ) {
	include_once dirname( RESTALER_PLUGIN_FILE ) . '/core/class-onboarding.php';
}

/**
 * Runs an activate the plugin.
 */
class Install {
	/**
	 * Init activation.
	 *
	 * @return void
	 */
	public static function init() {
		self::maybe_create_table();
		self::add_rewrite_rule();
		self::init_onboarding();
	}

	/**
	 * Handle plugin activation.
	 */
	public static function init_onboarding() {

		// Set flag that plugin was just activated.
		set_transient( 'restaler_onboarding_activation_redirect', true, 60 );

		// Set onboarding as pending.
		update_option( 'restaler_onboarding_completed', false );
		update_option( 'restaler_onboarding_started', current_time( 'timestamp' ) );

		// Clear any existing onboarding progress.
		delete_option( 'restaler_onboarding_current_step' );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule(
			'^verify-email/?$',
			'index.php?verify_email=1',
			'top'
		);

		add_rewrite_tag( '%verify_email%', '1' );
		add_rewrite_tag( '%email%', '([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})' );
		add_rewrite_tag( '%token%', '([a-zA-Z0-9]+)' );

		flush_rewrite_rules();
	}

	/**
	 * Create a alert table.
	 *
	 * @return void
	 */
	public static function maybe_create_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT(11) NOT NULL AUTO_INCREMENT,
			email VARCHAR(255) NOT NULL,
			product_id INT(11) NOT NULL,
			variation_id INT(11) NOT NULL,
			status VARCHAR(50) DEFAULT 'pending',
			token VARCHAR(255) NOT NULL,
			token_expires TIMESTAMP NOT NULL,			
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
