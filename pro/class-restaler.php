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
	 * Logger class
	 *
	 * @var \StoboKit\Logger
	 */
	public $logger;

	/**
	 * Template override class.
	 *
	 * @var \StoboKit\Template_Factory
	 */
	public $templates;

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

		$this->init_core();

		// Assign template override.
		$this->templates = \StoboKit\Template_Factory::get_instance(
			'restock-alerts-for-woocommerce',
			RESTALER_PLUGIN_FILE
		);

		// Logger.
		$this->logger = new \StoboKit\Logger();

		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		if ( ! defined( 'RESTALER_PATH' ) ) {
			define( 'RESTALER_PATH', plugin_dir_path( __DIR__ ) );
		}
		if ( ! defined( 'RESTALER_URL' ) ) {
			define( 'RESTALER_URL', plugin_dir_url( __DIR__ ) );
		}
	}

	/**
	 * Load core.
	 */
	private function init_core() {
		require_once RESTALER_PATH . '/core/init-core.php';
	}

	/**
	 * Load required files.
	 */
	private function load_common() {

		require_once RESTALER_PATH . '/common/includes/class-utils.php';

		require_once RESTALER_PATH . '/common/public/class-cron.php';
		require_once RESTALER_PATH . '/common/public/class-frontend.php';

		if ( is_admin() ) {
			include_once RESTALER_PATH . '/common/admin/view/settings-page.php';
			require_once RESTALER_PATH . '/common/admin/class-admin.php';
			require_once RESTALER_PATH . '/common/admin/class-notify-list-table.php';
		}

		require RESTALER_PATH . '/common/admin/init-update.php';
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		$this->load_common();
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init_onboarding' ) );
		add_action( 'plugins_loaded', array( $this, 'ensure_table_exists' ) );

		// Create a table when activate the plugin.
		register_activation_hook( RESTALER_PLUGIN_FILE, array( $this, 'create_notify_table' ) );
		add_action( 'before_woocommerce_init', array( $this, 'enable_hpos' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init_onboarding() {
		$steps = array(
			'welcome'            => 'Welcome',
			'license-activation' => 'Activate License',
			'settings'           => 'General Setup',
			'finish'             => 'Finish',
		);

		new \STOBOKIT\Onboarding(
			array(
				'path'          => RESTALER_PATH,
				'plugin_slug'   => 'restock-alerts-for-woocommerce',
				'steps'         => $steps,
				'redirect_page' => 'stobokit-review-follow-up-settings',
				'page_slug'     => 'stobokit-onboarding-restaler',
				'option_prefix' => 'restaler_onboarding',
			)
		);
	}

	/**
	 * Make sure the table exists, otherwise create the required table.
	 *
	 * @return void
	 */
	public function ensure_table_exists() {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		// Check if table exists.
		if( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->create_notify_table();
		}
	}

	/**
	 * Create a alert table.
	 *
	 * @return void
	 */
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

	/**
	 * Enable HPOS
	 *
	 * @return void
	 */
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
