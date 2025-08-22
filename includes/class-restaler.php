<?php
/**
 * Plugin initialization class.
 *
 * @package restock-alerts-for-woocommerce\includes\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
final class RESTALER {

	/**
	 * Singleton instance.
	 *
	 * @var RESTALER|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return RESTALER
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		$this->define_constants();

		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	private function __wakeup() {}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'RESTALER_VERSION', '1.0.0' );
		define( 'RESTALER_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'RESTALER_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once RESTALER_PATH . '/core/init-core.php';
		require_once RESTALER_PATH . '/includes/class-utils.php';

		require_once RESTALER_PATH . '/public/class-cron.php';
		require_once RESTALER_PATH . '/public/class-frontend.php';

		if ( is_admin() ) {
			include_once RESTALER_PATH . '/admin/view/settings-page.php';
			require_once RESTALER_PATH . '/admin/class-admin.php';
			require_once RESTALER_PATH . '/admin/class-notify-list-table.php';
		}
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'ensure_table_exists' ) );

		// Create a table when activate the plugin.
		register_activation_hook( RESTALER_PLUGIN_FILE, array( $this, 'create_notify_table' ) );
		add_action( 'before_woocommerce_init', array( $this, 'enable_hpos' ) );
	}

	public function ensure_table_exists() {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		// Check if table exists.
		if( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->create_notify_table();
		}
	}

	public function create_notify_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT(11) NOT NULL AUTO_INCREMENT,
			email VARCHAR(255) NOT NULL,
			product_id INT(11) NOT NULL,
			status VARCHAR(50) DEFAULT 'pending',
			token VARCHAR(255) NOT NULL,
			token_expires TIMESTAMP NOT NULL,			
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function enable_hpos() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				RESTALER_PLUGIN_FILE,
				true
			);
		}
	}
}
