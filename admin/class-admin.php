<?php
/**
 * Admin class.
 *
 * @package restock-alerts-for-woocommerce\admin\
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
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_insert_post', array( $this, 'save_product' ), 99, 3 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ) );
		add_filter( 'stobokit_product_lists', array( $this, 'add_product' ) );
	}

	public function add_product( $products = array() ) {
		$products['restock-alerts-for-woocommerce']['name'] = esc_html__( 'Restock Alerts for WooCommerce', 'restock-alerts-for-woocommerce' );
		$products['restock-alerts-for-woocommerce']['id']   = 74;

		return $products;
	}

	public function order_completed( $order_id = 0 ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$customer_email = $order->get_billing_email();

			global $wpdb;

			$wpdb->update(
				$wpdb->prefix . 'restaler_restock_alerts',
				array( 'status' => 'completed' ),
				array(
					'email'      => $customer_email,
					'product_id' => $product_id,
				)
			);
		}
	}

	public function admin_menu() {
		add_menu_page(
			esc_html__( 'Restock Alerts', 'restock-alerts-for-woocommerce' ),
			esc_html__( 'Restock Alerts', 'restock-alerts-for-woocommerce' ),
			'manage_options',
			'stobokit-notify-list',
			array( $this, 'render_notify_list_page' ),
			'dashicons-bell',
			50
		);
	}

	public function render_notify_list_page() {
		echo '<div class="stobokit-wrapper no-spacing">';
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Email Notifications', 'restock-alerts-for-woocommerce' ) . '</h1>';
		$notify_table = new Notify_List_Table();
		$notify_table->prepare_items();
		echo '<form method="post">';
		$notify_table->display();
		echo '</form></div>';
		echo '</div>';
	}

	public function save_product( $post_id = 0 ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$product      = wc_get_product( $post_id );
		$stock_status = $product->get_stock_status();

		if ( 'outofstock' === $stock_status ) {
			return;
		}

		// To prevent call stack.
		remove_action( 'wp_save_product', array( $this, 'save_product' ) );

		$results = $this->get_emails( $post_id );

		if ( $results ) {
			$enable_followup = get_option( 'restaler_enable_followup', '' );

			$discount_type        = get_option( 'restaler_discount_type', 'percent' );
			$amount               = get_option( 'restaler_discount_amount', 20 );
			$first_followup_days  = get_option( 'restaler_first_followup_days', 2 );
			$second_followup_days = get_option( 'restaler_second_followup_days', 3 );
			$coupon_expires_in    = get_option( 'restaler_coupon_expires_in', 3 );

			$first_followup  = time() + ( $first_followup_days * DAY_IN_SECONDS ); // 2 days later
			$second_followup = $first_followup + ( $second_followup_days * DAY_IN_SECONDS ); // 5 days total

			$coupon_expires      = $second_followup + ( $coupon_expires_in * DAY_IN_SECONDS ); // Add 3 days.
			$coupon_expires_date = gmdate( 'd-m-Y', $coupon_expires );

			$args = array(
				'first_followup'      => $first_followup,
				'second_followup'     => $second_followup,
				'product'             => $product,
				'discount_type'       => $discount_type,
				'amount'              => $amount,
				'coupon_expires_in'   => $coupon_expires_in,
				'coupon_expires_date' => $coupon_expires_date,
			);

			$coupon = Utils::generate_discount( $args );

			$args['coupon'] = $coupon;

			foreach ( $results as $row ) {
				$this->send_notify_emails( $row );
				$this->change_status_to_email_sent( $row );

				if ( ! empty( $enable_followup ) ) {
					$this->create_followup_schedule( $row, $args );
				}
			}
		}

		add_action( 'wp_save_product', array( $this, 'save_product' ) );
	}

	public function get_emails( $post_id = 0 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'restaler_restock_alerts';

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
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = get_option( 'restaler_email_subject', esc_html__( 'Back in Stock!', 'restock-alerts-for-woocommerce' ) );

		ob_start();
		include RESTALER_PATH . '/templates/email/html-back-in-stock-email.php';
		$content = ob_get_contents();
		ob_end_clean();

		// CssInliner loads from WooCommerce.
		$html = CssInliner::fromHtml( $content )->inlineCss()->render();

		$result = wp_mail( $email, $subject, $html, $headers );
		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'restock-alerts-for-woocommerce' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'restock-alerts-for-woocommerce' );
		}
	}

	public function change_status_to_email_sent( $row = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'restaler_restock_alerts';
		$wpdb->update(
			$table,
			array( 'status' => 'email-sent' ),
			array(
				'id' => $row['id'],
			)
		);
	}

	public function create_followup_schedule( $row = array(), $args = array() ) {
		wp_schedule_single_event(
			$args['first_followup'],
			'restaler_still_interested_followup_email',
			array( $row, $args )
		);

		wp_schedule_single_event(
			$args['second_followup'],
			'restaler_urgency_followup_email',
			array( $row, $args )
		);
	}
}

\RESTALER\Admin::instance();
