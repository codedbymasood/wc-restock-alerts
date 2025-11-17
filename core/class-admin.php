<?php
/**
 * Admin class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Admin {

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Create a table when activate the plugin.
		add_action( 'plugins_loaded', array( $this, 'maybe_create_table' ) );

		add_action( 'wp_ajax_stobokit_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Make sure the table exists, otherwise create the required table.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		global $wpdb;

		$scheduler_logs = $wpdb->prefix . 'stobokit_scheduler_logs';

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$scheduler_logs_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$scheduler_logs
			)
		);

		if ( $scheduler_logs_table_exists !== $scheduler_logs ) {
			$this->create_scheduler_logs_table();
		}

		$email_queue = $wpdb->prefix . 'stobokit_email_queue';

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_queue_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$email_queue
			)
		);

		if ( $email_queue_table_exists !== $email_queue ) {
			$this->create_email_queue_table();
		}

		$email_logs = $wpdb->prefix . 'stobokit_email_logs';

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$email_logs_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$email_logs
			)
		);

		if ( $email_logs_table_exists !== $email_logs ) {
			$this->create_email_logs_table();
		}
	}

	/**
	 * Create a alert table.
	 *
	 * @return void
	 */
	public function create_scheduler_logs_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'stobokit_scheduler_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uid varchar(128) NOT NULL,
			hook_name varchar(128) NOT NULL,
			args varchar(255) NOT NULL,
			schedule varchar(50) DEFAULT NULL,
			next_run datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'scheduled',
			attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_uid (uid),
			KEY idx_hook_name (hook_name),
			KEY idx_status_next_run (status, next_run)
	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create email queue table
	 *
	 * @return void
	 */
	public static function create_email_queue_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Email queue table.
		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_id varchar(100) NOT NULL,
			sequence_id varchar(100) DEFAULT NULL,
			to_email varchar(255) NOT NULL,
			subject varchar(500) NOT NULL,
			message longtext NOT NULL,
			args longtext DEFAULT NULL,
			validation_callback longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'scheduled',
			retry_count int(11) NOT NULL DEFAULT 0,
			daily_retry_count int(11) NOT NULL DEFAULT 0,
			max_retries int(11) NOT NULL DEFAULT 3,
			scheduled_time datetime NOT NULL,
			last_attempt_time datetime DEFAULT NULL,
			last_error_message text DEFAULT NULL,
			notes text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY email_id (email_id),
			KEY status (status),
			KEY scheduled_time (scheduled_time),
			KEY sequence_id (sequence_id),
			KEY to_email (to_email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create email queue table
	 *
	 * @return void
	 */
	public static function create_email_logs_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Email logs table.
		$log_table_name = $wpdb->prefix . 'stobokit_email_logs';
		$sql_logs = "CREATE TABLE IF NOT EXISTS {$log_table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_id varchar(100) DEFAULT NULL,
			to_email varchar(255) NOT NULL,
			subject varchar(500) NOT NULL,
			sent tinyint(1) NOT NULL DEFAULT 0,
			skip_reason text DEFAULT NULL,
			sent_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY email_id (email_id),
			KEY to_email (to_email),
			KEY sent_at (sent_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_logs );
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! isset( $_POST['nonce'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'stobokit_save_settings' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry, not verified.', 'plugin-slug' ),
				)
			);
		}

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$inputs = isset( $_POST['inputs'] ) && ! empty( $_POST['inputs'] ) ? wp_unslash( $_POST['inputs'] ) : array();

		if ( ! empty( $inputs ) ) {
			foreach ( $inputs as $index => $field ) {
				if ( '_wp_http_referer' !== $index && 'stobokit_save_settings_nonce' !== $index ) {
					$name  = sanitize_text_field( $field['name'] );
					$value = sanitize_text_field( $field['value'] );
					update_option( $name, $value );
				}
			}
		}

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Settings saved!', 'plugin-slug' ),
			)
		);

		exit();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook Menu hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		wp_enqueue_style( 'stobokit-admin', STOBOKIT_URL . 'assets/css/admin.css', array(), '1.0' );
		wp_enqueue_script( 'stobokit-admin', STOBOKIT_URL . 'assets/js/admin.js', array(), '1.0', true );
	}

	/**
	 * Add menu page hook callback.
	 *
	 * @return void
	 */
	public function admin_menu() {
		$icon = $this->get_svg_icon();

		add_menu_page(
			esc_html__( 'Store Boost Kit', 'plugin-slug' ),
			esc_html__( 'Store Boost Kit', 'plugin-slug' ),
			'manage_woocommerce',
			'stobokit-dashboard',
			array( $this, 'dashboard' ),
			$icon,
			50
		);

		add_submenu_page(
			'stobokit-dashboard',
			esc_html__( 'Dashboard', 'plugin-slug' ),
			esc_html__( 'Dashboard', 'plugin-slug' ),
			'manage_woocommerce',
			'stobokit-dashboard',
			array( $this, 'dashboard' )
		);

		add_submenu_page(
			'stobokit-dashboard',
			esc_html__( 'Status', 'plugin-slug' ),
			esc_html__( 'Status', 'plugin-slug' ),
			'manage_woocommerce',
			'stobokit-status',
			array( $this, 'status' )
		);

		$product_lists = apply_filters( 'stobokit_product_lists', array() );

		if ( ! empty( $product_lists ) ) {
			add_submenu_page(
				'stobokit-dashboard',
				esc_html__( 'License', 'plugin-slug' ),
				esc_html__( 'License', 'plugin-slug' ),
				'manage_woocommerce',
				'stobokit-license',
				array( $this, 'license' )
			);
		}

		do_action( 'stobokit_admin_menu_registered' );
	}

	/**
	 * Dashboard page.
	 *
	 * @return void
	 */
	public function dashboard() {
		include_once STOBOKIT_PATH . '/views/dashboard.php';
	}

	/**
	 * Status page.
	 *
	 * @return void
	 */
	public function status() {
		include_once STOBOKIT_PATH . '/views/status.php';
	}

	/**
	 * License page.
	 *
	 * @return void
	 */
	public function license() {
		include_once STOBOKIT_PATH . '/views/license.php';
	}

	public function get_svg_icon() {
		return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTU5IiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1OSAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0zNC43OTE4IDE0MS4yNjJDNTcuOTU1MyAxNDEuMjYyIDgxLjExODggMTQxLjI2MiAxMDQuOTg0IDE0MS4yNjJDMTA2LjIyIDEzOC44OTcgMTA3LjQ1NSAxMzYuNTMyIDEwOC43MjggMTM0LjA5NUMxMDkuODggMTMxLjk5MSAxMDkuODggMTMxLjk5MSAxMTEuMDM2IDEyOS44ODlDMTEyLjUwNCAxMjcuMjExIDExMy44OTYgMTI0LjQ5NSAxMTUuMjkxIDEyMS43NzhDMTE2Ljc1NCAxMTguOTMxIDExOC4yNTIgMTE2LjEwNyAxMTkuOCAxMTMuMzA1QzEyMS4wOTEgMTEwLjk2IDEyMi4zMjIgMTA4LjU4NSAxMjMuNTQ2IDEwNi4yMDVDMTI0LjkxOCAxMDMuNTM4IDEyNi4zMDkgMTAwLjg4NSAxMjcuNzU4IDk4LjI1ODZDMTI4LjM4NCA5Ny4xMTcgMTI5LjAwNyA5NS45NzQxIDEyOS42MyA5NC44MzA4QzEyOS43MyA5NC42NDc0IDEyOS44MyA5NC40NjQgMTI5LjkzMyA5NC4yNzVDMTMwLjYwMSA5My4wNTE0IDEzMS4yNjIgOTEuODI0NSAxMzEuOTE1IDkwLjU5MzRDMTMyLjEzMiA5MC4xODgxIDEzMi4zNSA4OS43ODM4IDEzMi41NzEgODkuMzgwN0MxMzIuODc4IDg4LjgxNzEgMTMzLjE3OSA4OC4yNTAxIDEzMy40NzggODcuNjgxOEMxMzMuNjUzIDg3LjM1NSAxMzMuODI5IDg3LjAyODIgMTM0LjAxIDg2LjY5MTRDMTM0LjMwOSA4NS43OTM4IDEzNC4zMDkgODUuNzkzOCAxMzQuMDc5IDg0Ljk0MjJDMTMzLjY0MSA4NC4xNTU4IDEzMy4xNTcgODMuNDU0IDEzMi42MTMgODIuNzM2QzEzMS41NzUgODEuMzM5OSAxMzAuNTcyIDc5LjkyNjQgMTI5LjU5MSA3OC40OTAyQzEyOC4yNjQgNzYuNTUzIDEyNi45MjQgNzQuNjI2NyAxMjUuNTc0IDcyLjcwNTdDMTI0LjI3OSA3MC44NjEyIDEyMi45ODkgNjkuMDEzNSAxMjEuNzEzIDY3LjE1NUMxMjAuMzI0IDY1LjEzMDQgMTE4LjkyNSA2My4xMTI5IDExNy41MjEgNjEuMDk3OUMxMTcuMzA5IDYwLjc5MzMgMTE3LjA5NyA2MC40ODg3IDExNi44NzkgNjAuMTc0OEMxMTUuODUxIDU4LjcgMTE0LjgyMSA1Ny4yMjY3IDExMy43ODYgNTUuNzU2NUMxMTMuNTk3IDU1LjQ4NzkgMTEzLjQwOSA1NS4yMTkzIDExMy4yMTQgNTQuOTQyNUMxMTIuODY3IDU0LjQ1MDEgMTEyLjUyMSA1My45NTgxIDExMi4xNzMgNTMuNDY2NkMxMTEuOTMxIDUzLjEyMjYgMTExLjkzMSA1My4xMjI2IDExMS42ODQgNTIuNzcxOEMxMTEuNTE3IDUyLjU0ODQgMTExLjM1IDUyLjMyNSAxMTEuMTc3IDUyLjA5NDlDMTEwLjcxNSA1MS4zODI0IDExMC40MzQgNTAuODE5OCAxMTAuMTMxIDUwLjA0NDRDMTA5LjUgNDguNjA3OCAxMDguNzk5IDQ3LjQyNjEgMTA3LjQ4IDQ2LjUyOTdDMTA0Ljc4MyA0NS43MDQ4IDEwMS44MjYgNDUuOTcwMSA5OS4wMzcyIDQ2LjA1NTdDOTcuOTE3OCA0Ni4wODUxIDk2Ljc5ODMgNDYuMDg3NyA5NS42Nzg1IDQ2LjA5MzJDOTMuNTYyMyA0Ni4xMDc3IDkxLjQ0NzIgNDYuMTQ2IDg5LjMzMTQgNDYuMTkzQzg2LjkyMTEgNDYuMjQ1NCA4NC41MTA3IDQ2LjI3MSA4Mi4wOTk5IDQ2LjI5NDRDNzcuMTQ0NyA0Ni4zNDMyIDcyLjE5MDYgNDYuNDI1MyA2Ny4yMzYzIDQ2LjUyOTdDNjcuNTggNDcuMDcxNSA2Ny45MjQxIDQ3LjYxMzEgNjguMjY4MyA0OC4xNTQ3QzY4LjQ1OTggNDguNDU2NCA2OC42NTE0IDQ4Ljc1OCA2OC44NDg4IDQ5LjA2ODhDNjkuNjUxMyA1MC4zMTczIDcwLjQ4NzIgNTEuNTQxMSA3MS4zMzA4IDUyLjc2MjFDNzEuNjg3MSA1My4yNzk4IDcyLjA0MzMgNTMuNzk3NSA3Mi4zOTk1IDU0LjMxNTNDNzIuNTc4OCA1NC41NzU0IDcyLjc1OCA1NC44MzU1IDcyLjk0MjYgNTUuMTAzNUM3My40NTEyIDU1Ljg0MjggNzMuOTU4IDU2LjU4MzMgNzQuNDYzOSA1Ny4zMjQ0Qzc2LjA3NzIgNTkuNjg3MSA3Ny42OTk0IDYyLjA0MjkgNzkuMzM5OCA2NC4zODdDODEuNTI3NCA2Ny41MTM0IDgzLjY3NDEgNzAuNjYyOCA4NS43NzM3IDczLjg0OUM4Ni45Mzg0IDc1LjYxMDEgODguMTQ2OCA3Ny4zMjgyIDg5LjM5NyA3OS4wMjk4QzkwLjM3OSA4MC4zODEgOTEuMzE1MiA4MS43NjI0IDkyLjI1MjEgODMuMTQ1QzkyLjY2MTEgODMuNzQ2NCA5My4wNzAxIDg0LjM0NzcgOTMuNDc5MiA4NC45NDlDOTMuNjgxOCA4NS4yNDcyIDkzLjg4NDUgODUuNTQ1MyA5NC4wOTMzIDg1Ljg1MjVDOTQuOTkxMSA4Ny4xNzIyIDk1Ljg5MjggODguNDg5MyA5Ni43OTUxIDg5LjgwNTlDOTYuOTYxIDkwLjA0ODEgOTcuMTI2OSA5MC4yOTAyIDk3LjI5NzggOTAuNTM5N0M5OC4zNjM1IDkyLjA5NDUgOTkuNDMxIDkzLjY0ODEgMTAwLjUgOTUuMjAwOEMxMDAuNjM3IDk1LjQwMDEgMTAwLjc3NCA5NS41OTk0IDEwMC45MTUgOTUuODA0N0MxMDEuNjU0IDk2Ljg3OSAxMDIuMzk2IDk3Ljk1MSAxMDMuMTQyIDk5LjAyMDZDMTAzLjM3OSA5OS4zNjIxIDEwMy4zNzkgOTkuMzYyMSAxMDMuNjIxIDk5LjcxMDZDMTAzLjkyNSAxMDAuMTQ4IDEwNC4yMyAxMDAuNTg0IDEwNC41MzUgMTAxLjAyQzEwNS4xMTggMTAxLjg2IDEwNS41OTUgMTAyLjU4MiAxMDUuOTIgMTAzLjU1NkMxMDUuNjI4IDEwNC4yODggMTA1LjMxNyAxMDQuOTYzIDEwNC45NiAxMDUuNjYyQzEwNC44NTggMTA1Ljg2OCAxMDQuNzU2IDEwNi4wNzUgMTA0LjY1IDEwNi4yODhDMTA0LjMyMyAxMDYuOTUgMTAzLjk5MSAxMDcuNjEgMTAzLjY1OCAxMDguMjY5QzEwMy4zMzEgMTA4LjkyMyAxMDMuMDA0IDEwOS41NzYgMTAyLjY4IDExMC4yMzFDMTAyLjQ3OCAxMTAuNjM4IDEwMi4yNzQgMTExLjA0NSAxMDIuMDY4IDExMS40NUMxMDEuMzEzIDExMi45NjcgMTAwLjg1NSAxMTQuMzczIDEwMC4zMDUgMTE2LjAyMUM4Mi41OTc1IDExNi4wMjEgNjQuODkwMyAxMTYuMDIxIDQ2LjY0NjUgMTE2LjAyMUM0NS4yMDUyIDExOS4wMDMgNDMuNzYzOSAxMjEuOTg1IDQyLjI3ODkgMTI1LjA1OEM0MS4xMDgyIDEyNy40NTggMzkuOTM2OSAxMjkuODU4IDM4Ljc2MTggMTMyLjI1N0MzNC43OTE4IDE0MC4zNjEgMzQuNzkxOCAxNDAuMzYxIDM0Ljc5MTggMTQxLjI2MloiIGZpbGw9IiNBN0FBQUQiLz4KPHBhdGggZD0iTTIyLjI0MDYgNjQuNzE5NUMyMi41OTczIDY1LjQ4MDMgMjIuOTc4MyA2Ni4xMTk0IDIzLjQ2NTQgNjYuODAyMUMyMy42MzQ4IDY3LjA0MTQgMjMuODA0MiA2Ny4yODA3IDIzLjk3ODcgNjcuNTI3M0MyNC4xNjI0IDY3Ljc4MzYgMjQuMzQ2MSA2OC4wNCAyNC41MzU0IDY4LjMwNDJDMjQuOTMxNiA2OC44NjMyIDI1LjMyNzYgNjkuNDIyMyAyNS43MjM1IDY5Ljk4MTZDMjUuOTMxIDcwLjI3MzcgMjYuMTM4NSA3MC41NjU4IDI2LjM1MjIgNzAuODY2OEMyNy40MjUzIDcyLjM4MjkgMjguNDg1NyA3My45MDc3IDI5LjU0NjQgNzUuNDMyNUMyOS43NTgzIDc1LjczNjkgMjkuOTcwMyA3Ni4wNDE0IDMwLjE4ODcgNzYuMzU1QzMxLjQ5OTIgNzguMjM4MSAzMi44MDMgODAuMTI1NiAzNC4xMDE2IDgyLjAxNjlDMzYuNDA0MiA4NS4zNjc1IDM4LjczNzIgODguNjk3MiA0MS4wNjI2IDkyLjAzMkM0MS43MDg1IDkyLjk1ODQgNDIuMzU0MiA5My44ODQ5IDQyLjk5OTkgOTQuODExNEM0My4xODk4IDk1LjA4MzUgNDMuMzc5NyA5NS4zNTU3IDQzLjU3NTMgOTUuNjM2MUM0My43NjM5IDk1LjkwNjcgNDMuOTUyNCA5Ni4xNzcyIDQ0LjE0NjYgOTYuNDU1OUM0NC4zMzk2IDk2LjcxNzEgNDQuNTMyNiA5Ni45Nzg0IDQ0LjczMTQgOTcuMjQ3NkM0NS4yNDM2IDk4LjAyNDMgNDUuNTkwOSA5OC42NjkgNDUuOTUyMiA5OS41MDgzQzQ2LjY2NzggMTAxLjAxIDQ3LjQ0MjMgMTAyLjI4MSA0OC44Mjk4IDEwMy4yNDVDNTEuNTExMSAxMDQuMTA2IDU0LjQ1OTMgMTAzLjg0MiA1Ny4yNDExIDEwMy43NUM1OC4zNTA5IDEwMy43MTkgNTkuNDYwOSAxMDMuNzE2IDYwLjU3MTEgMTAzLjcxQzYyLjY2ODYgMTAzLjY5NSA2NC43NjQ4IDEwMy42NTQgNjYuODYxOCAxMDMuNjA0QzY5LjI1MSAxMDMuNTQ4IDcxLjY0MDMgMTAzLjUyIDc0LjAzIDEwMy40OTVDNzguOTQxMiAxMDMuNDQ0IDgzLjg1MTIgMTAzLjM1NiA4OC43NjE1IDEwMy4yNDVDODguMzI4NiAxMDIuMTI1IDg3Ljg1NzggMTAxLjIwOSA4Ny4xNjAyIDEwMC4yMjlDODYuODYzNyA5OS44MDkzIDg2Ljg2MzcgOTkuODA5MyA4Ni41NjEzIDk5LjM4MDdDODYuMjQxMyA5OC45MzEzIDg2LjI0MTMgOTguOTMxMyA4NS45MTQ4IDk4LjQ3MjlDODMuODU5OSA5NS41NTg3IDgxLjg1NzUgOTIuNjE1MiA3OS44OTU5IDg5LjYzNzZDNzguNzMwNyA4Ny44NzYzIDc3LjUyMjEgODYuMTU3NyA3Ni4yNzE3IDg0LjQ1NThDNzUuMjg5NyA4My4xMDQ1IDc0LjM1MzYgODEuNzIzMSA3My40MTY2IDgwLjM0MDVDNzMuMDA1NiA3OS43MzYzIDcyLjU5NDUgNzkuMTMyMSA3Mi4xODM0IDc4LjUyOEM3MS45ODA1IDc4LjIyOTUgNzEuNzc3NiA3Ny45MzEgNzEuNTY4NiA3Ny42MjM0QzcwLjcwNDcgNzYuMzUyOSA2OS44Mzg3IDc1LjA4MzkgNjguOTcxMSA3My44MTZDNjguODA4MSA3My41Nzc2IDY4LjY0NTEgNzMuMzM5MyA2OC40NzcxIDczLjA5MzdDNjcuMzY2MSA3MS40NzI4IDY2LjI0MzIgNjkuODYwNCA2NS4xMTY1IDY4LjI1MDJDNjIuOTI4NyA2NS4xMjMgNjAuNzgxMyA2MS45NzMgNTguNjgxMyA1OC43ODYxQzU3LjE0MzEgNTYuNDYwMyA1NS41MTU2IDU0LjIxIDUzLjg1ODEgNTEuOTY4QzUzLjMwNjggNTEuMjE3NiA1Mi43NjM5IDUwLjQ2MTggNTIuMjIyNCA0OS43MDQzQzUyLjAzNSA0OS40NTMxIDUxLjg0NzYgNDkuMjAxOCA1MS42NTQ1IDQ4Ljk0MjlDNTEuNDgwOCA0OC42OTczIDUxLjMwNzEgNDguNDUxNyA1MS4xMjgxIDQ4LjE5ODZDNTAuODkyMiA0Ny44NzM5IDUwLjg5MjIgNDcuODczOSA1MC42NTE1IDQ3LjU0MjdDNTAuMjM1MiA0Ni42NTE5IDUwLjIzNTIgNDYuNjUxOSA1MS4wMTM1IDQ0Ljk3MTZDNTIuNzYzNyA0MS4zNzI0IDU0LjUxMzggMzcuNzczMiA1Ni4zMTcgMzQuMDY0OUM3My45MjEyIDMzLjk2MjEgOTEuNTI1NSAzMy44NTkyIDEwOS42NjMgMzMuNzUzM0MxMTEuNjU4IDI5Ljc2ODEgMTEzLjY0IDI1Ljc4OTUgMTE1LjU1MiAyMS43NjU2QzExNi4zMDkgMjAuMTc3MSAxMTcuMDc5IDE4LjU5NDYgMTE3Ljg1MiAxNy4wMTM0QzExOC4wODMgMTYuNTQwMyAxMTguMzE0IDE2LjA2NzEgMTE4LjU0NiAxNS41OTM5QzExOC44OSAxNC44ODg2IDExOS4yMzUgMTQuMTgzNSAxMTkuNTgxIDEzLjQ3OUMxMTkuOTA0IDEyLjgyMTYgMTIwLjIyNSAxMi4xNjM1IDEyMC41NDcgMTEuNTA1M0MxMjAuNjQ1IDExLjMwNjMgMTIwLjc0MyAxMS4xMDc0IDEyMC44NDUgMTAuOTAyNEMxMjEuNTE4IDkuNTE3NzQgMTIxLjUxOCA5LjUxNzc0IDEyMS41MTggOC44MjM2N0M5OC4zNTQ0IDguODIzNjcgNzUuMTkwOSA4LjgyMzY3IDUxLjMyNTUgOC44MjM2N0M0Ny4xMDEgMTYuNTYgNDcuMTAxIDE2LjU2IDQ1LjU1NDEgMTkuNTc0NkM0NC4zMDQgMjEuOTk0MSA0My4wMjkyIDI0LjM5NjUgNDEuNzEzIDI2Ljc4MDhDNDAuMDcyNyAyOS43NTQ2IDM4LjUxMzYgMzIuNzY2NyAzNi45NjI1IDM1Ljc4NzZDMzUuNjQ3MSAzOC4zNDgxIDM0LjMxMjcgNDAuODk0IDMyLjkxOTUgNDMuNDEzNUMzMS4yOTg2IDQ2LjM0NDggMjkuNzYxNSA0OS4zMTU0IDI4LjIzMTkgNTIuMjk0OUMyNi40MTUyIDU1LjgzMTUgMjQuNTQxNyA1OS4zMzM0IDIyLjYxMjUgNjIuODEwNEMyMS45ODk2IDYzLjk0NjMgMjEuOTg5NiA2My45NDYzIDIyLjI0MDYgNjQuNzE5NVoiIGZpbGw9IiNBN0FBQUQiLz4KPC9zdmc+Cg==';
	}
}

new Admin();

