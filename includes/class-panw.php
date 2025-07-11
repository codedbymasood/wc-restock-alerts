<?php
/**
 * Plugin initialization class.
 *
 * @package product-availability-notifier-for-woocommerce\includes\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PANW;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
final class PANW {

	/**
	 * Singleton instance.
	 *
	 * @var PANW|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PANW
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
		define( 'PANW_VERSION', '1.0.0' );
		define( 'PANW_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'PANW_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once PANW_PATH . '/includes/class-utils.php';

		require_once PANW_PATH . '/public/class-cron.php';
		require_once PANW_PATH . '/public/class-frontend.php';

		if ( is_admin() ) {
			require_once PANW_PATH . '/admin/class-settings.php';
			include_once PANW_PATH . '/admin/view/settings-page.php';
			require_once PANW_PATH . '/admin/class-admin.php';
			require_once PANW_PATH . '/admin/class-notify-list-table.php';
		}
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {

		// Create a table when activate the plugin.
		register_activation_hook( PANW_PLUGIN_FILE, array( $this, 'create_notify_table' ) );
	}

	public function create_notify_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'panw_product_notify';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id INT(11) NOT NULL AUTO_INCREMENT,
			email VARCHAR(255) NOT NULL,
			product_id INT(11) NOT NULL,
			status VARCHAR(50) DEFAULT 'pending',
			token VARCHAR(255) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
