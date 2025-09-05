<?php
/**
 * Plugin initialization class.
 *
 * @package plugin-slug\includes\
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
	 * Schedule logger class
	 *
	 * @var \StoboKit\Schedule_Logger
	 */
	public $scheduler;

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

		$this->init_core();

		// Assign template override.
		$this->templates = \StoboKit\Template_Factory::get_instance(
			'plugin-slug',
			RESTALER_PLUGIN_FILE
		);

		// Emailer.
		$this->emailer = \StoboKit\Emailer::get_instance();

		// Logger.
		$this->logger = new \StoboKit\Logger();

		// Schedule logger.
		$this->scheduler = new \StoboKit\Schedule_Logger();

		$this->load_dependencies();
		$this->init_hooks();
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
		require_once RESTALER_PATH . '/common/includes/class-hooks.php';

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

		require_once __DIR__ . '/class-hooks.php';
		require_once __DIR__ . '/class-admin.php';
		require_once __DIR__ . '/views/email-templates.php';
		require_once __DIR__ . '/class-notify-list-table-pro.php';
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init_onboarding' ) );
		add_action( 'plugins_loaded', array( $this, 'ensure_table_exists' ) );

		// Create a table when activate the plugin.
		register_activation_hook( RESTALER_PLUGIN_FILE, array( $this, 'maybe_create_table' ) );
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
				'plugin_slug'   => 'plugin-slug',
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

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		if ( $table_exists !== $table ) {
			$this->maybe_create_table();
		}
	}

	/**
	 * Create a alert table.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
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
