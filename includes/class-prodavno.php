<?php
/**
 * Plugin initialization class.
 *
 * @package product-availability-notifier-for-woocommerce\includes\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace SBK_RAW;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
final class SBK_RAW {

	/**
	 * Singleton instance.
	 *
	 * @var SBK_RAW|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return SBK_RAW
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
		define( 'SBK_RAW_VERSION', '1.0.0' );
		define( 'SBK_RAW_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'SBK_RAW_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once SBK_RAW_PATH . '/includes/class-utils.php';

		require_once SBK_RAW_PATH . '/public/class-cron.php';
		require_once SBK_RAW_PATH . '/public/class-frontend.php';

		if ( is_admin() ) {
			require_once SBK_RAW_PATH . '/admin/class-settings.php';
			include_once SBK_RAW_PATH . '/admin/view/settings-page.php';
			require_once SBK_RAW_PATH . '/admin/class-admin.php';
			require_once SBK_RAW_PATH . '/admin/class-notify-list-table.php';
		}
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'ensure_table_exists' ) );

		// Create a table when activate the plugin.
		register_activation_hook( SBK_RAW_PLUGIN_FILE, array( $this, 'create_notify_table' ) );
		add_action( 'before_woocommerce_init', array( $this, 'enable_hpos' ) );
	}

	public function ensure_table_exists() {
		global $wpdb;

		$table = $wpdb->prefix . 'sbk_raw_product_notify';

		// Check if table exists.
		if( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->create_notify_table();
		}
	}

	public function create_notify_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'sbk_raw_product_notify';

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
				SBK_RAW_PLUGIN_FILE,
				true
			);
		}
	}
}
