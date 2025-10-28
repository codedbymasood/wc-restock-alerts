<?php
/**
 * Admin class.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

use Pelago\Emogrifier\CssInliner;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin {

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
		add_filter(
			'stobokit_plugins',
			function ( $plugins = array() ) {
				$plugins[] = 'restock-alerts-development';

				return $plugins;
			}
		);
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_insert_post', array( $this, 'save_product' ), 99, 3 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ) );

		// It should run before initialize table.
		add_filter( 'restock_alerts_table_allow_export_csv', '__return_true' );
		add_filter( 'restock_alerts_table_csv_export_columns', array( $this, 'csv_export_columns' ), 99 );
		add_filter( 'restock_alerts_table_bulk_actions', array( $this, 'bulk_actions' ), 99 );
	}

	public function bulk_actions( $actions = array() ) {
		$actions['export_csv'] = esc_html__( 'Export to CSV', 'plugin-slug' );
		return $actions;
	}

	public function csv_export_columns() {
		return array(
			'id'           => esc_html__( 'ID', 'plugin-slug' ),
			'email'        => esc_html__( 'Email', 'plugin-slug' ),
			'product_id'   => esc_html__( 'Product', 'plugin-slug' ),
			'variation_id' => esc_html__( 'Variation', 'plugin-slug' ),
			'status'       => esc_html__( 'Status', 'plugin-slug' ),
			'created_at'   => esc_html__( 'Created At', 'plugin-slug' ),
		);
	}

	public function order_completed( $order_id = 0 ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_id     = $item->get_product_id();
			$customer_email = $order->get_billing_email();

			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->prefix . 'restaler_restock_alerts',
				array( 'status' => 'purchased' ),
				array(
					'email'      => $customer_email,
					'product_id' => $product_id,
				)
			);
		}
	}

	public function admin_menu() {
		add_menu_page(
			esc_html__( 'Restock Alerts', 'plugin-slug' ),
			esc_html__( 'Restock Alerts', 'plugin-slug' ),
			'manage_options',
			'stobokit-restaler-notify-list',
			array( $this, 'render_notify_list_page' ),
			'dashicons-bell',
			50
		);

		do_action( 'restock_alerts_menu_registered' );
	}

	public function render_notify_list_page() {
		$args = array(
			'title'      => esc_html__( 'Email Notifications', 'plugin-slug' ),
			'singular'   => 'notification',
			'plural'     => 'notifications',
			'table_name' => 'restaler_restock_alerts',
			'id'         => 'restock_alerts',
		);

		$notify_table = new Notify_List_Table( $args );
		$notify_table->display_table();
	}

	public function save_product( $post_id = 0 ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$product      = wc_get_product( $post_id );
		$stock_status = $product->get_stock_status();

		$product_type = $product->get_type();

		if ( 'outofstock' === $stock_status ) {
			return;
		}

		// To prevent call stack.
		remove_action( 'wp_save_product', array( $this, 'save_product' ) );

		$results = $this->get_emails( $post_id );

		if ( $results ) {
			foreach ( $results as $row ) {

				/**
				 * Before restock alert email sent.
				 */
				do_action( 'restaler_alert_before_email_sent', $row, $product, $this );

				if ( 'simple' === $product_type ) {
					$this->send_notify_emails( $row );
					$this->change_status_to_email_sent( $row );

					/**
					 * After restock alert email sent.
					 */
					do_action( 'restaler_alert_email_sent', $row, $product );
				} elseif ( 'variable' === $product_type ) {

					$variation_id = isset( $row['variation_id'] ) ? $row['variation_id'] : 0;

					$variations = wc_get_product( $variation_id );

					$stock_status = $variations->get_stock_status();

					if ( 'instock' === $stock_status ) {
						$this->send_notify_emails( $row );
						$this->change_status_to_email_sent( $row );

						/**
						 * After restock alert email sent.
						 */
						do_action( 'restaler_alert_email_sent', $row, $product );
					}
				}
			}
		}

		add_action( 'wp_save_product', array( $this, 'save_product' ) );
	}

	public function get_emails( $post_id = 0 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE product_id = %d AND status = %s",
				$post_id,
				'subscribed'
			),
			ARRAY_A
		);

		return $results;
	}

	public function send_notify_emails( $row = array() ) {
		$email        = $row['email'];
		$product_id   = $row['product_id'];
		$variation_id = $row['variation_id'];

		$subject     = get_option( 'restaler_back_in_stock_email_subject', esc_html__( 'Back in Stock!', 'plugin-slug' ) );
		$heading     = get_option( 'restaler_back_in_stock_email_heading', esc_html__( 'Back in Stock!', 'plugin-slug' ) );
		$footer_text = get_option( 'restaler_back_in_stock_email_footer_text', '' );

		$content = get_option(
			'restaler_back_in_stock_email_content',
			"The product you were waiting for is now back in stock

{product_name}{variation}
{buy_now}

Don't wait too long, popular products sell out quickly!

Warmly,
The {site_name} Team"
		);

		$html = restaler()->templates->get_template(
			'email/email-content.php',
			array(
				'heading'     => $heading,
				'content'     => $content['html'],
				'footer_text' => $footer_text,
			)
		);

		// CssInliner loads from WooCommerce.
		$html = CssInliner::fromHtml( $html )->inlineCss()->render();

		$result = restaler()->emailer->send_now(
			$email,
			$subject,
			$html,
			array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
			)
		);

		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'plugin-slug' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'plugin-slug' );
		}
	}

	public function change_status_to_email_sent( $row = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'restaler_restock_alerts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'status' => 'email-sent' ),
			array(
				'id' => $row['id'],
			)
		);
	}
}

\RESTALER\Admin::instance();
