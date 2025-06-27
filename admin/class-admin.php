<?php
/**
 * Admin class.
 *
 * @package product-availability-notifier-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
class Admin {

	/**
	 * Singleton instance.
	 *
	 * @var PAW|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PAW
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
		add_action( 'save_post_product', array( $this, 'save_product' ), 10, 3 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ) );
	}

	public function order_completed( $order_id = 0 ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$customer_email = $order->get_billing_email();

			global $wpdb;
			$table_name = $wpdb->prefix . 'paw_product_notify';

			$wpdb->update(
				$table_name,
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
			esc_html__( 'Notify List', 'product-availability-notifier-for-woocommerce' ),
			esc_html__( 'Notify Table', 'product-availability-notifier-for-woocommerce' ),
			'manage_options',
			'notify-list',
			array( $this, 'render_notify_list_page' ),
			'dashicons-email',
			26
		);
	}

	public function render_notify_list_page() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Email Notifications', 'product-availability-notifier-for-woocommerce' ) . '</h1>';
		$notify_table = new Notify_List_Table();
		$notify_table->prepare_items();
		echo '<form method="post">';
		$notify_table->display();
		echo '</form></div>';
	}

	public function save_product( $post_id = 0 ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		remove_action( 'save_post_product', array( $this, 'save_product' ) );

		$results = $this->get_emails( $post_id );

		if ( $results ) {
			/**
			 * Email sending on both conditions( stock-> outstock and outstock->instock, needs to check the condition)
			 */
			$this->send_notify_emails( $results );
			$this->change_status_to_email_sent( $results );

			/**
			 * TODO:
			 * start cronjobs for followup emails
			 */
		}

		add_action( 'save_post_product', array( $this, 'save_product' ) );
	}

	public function get_emails( $post_id = 0 ) {
		$old_status = get_post_meta( $post_id, '_stock_status', true );
		$new_status = isset( $_POST['_stock_status'] ) ? sanitize_text_field( $_POST['_stock_status'] ) : $old_status;

		$old_stock = (int) get_post_meta( $post_id, '_stock', true );
		$new_stock = isset( $_POST['_stock'] ) ? (int) sanitize_text_field( $_POST['_stock'] ) : $old_stock;

		if ( ( 'outofstock' === $old_status && 'instock' === $new_status ) || ( 0 === $old_stock && 0 < $new_stock ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'paw_product_notify';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE product_id = %d",
					$post_id
				),
				ARRAY_A
			);

			return $results;
		} else {
			return false;
		}
	}

	public function send_notify_emails( $results = array() ) {
		foreach ( $results as $row ) {
			$email      = $row['email'];
			$product_id = $row['product_id'];

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$subject = esc_html__( 'Back in Stock:', 'product-availability-notifier-for-woocommerce' );

			ob_start();
			include PAW_PATH . '/template/email/html-back-in-stock-email.php';
			$content = ob_get_contents();
			ob_end_clean();

			$result = wp_mail( $email, $subject, $content, $headers );
			if ( ! $result ) {
				esc_html_e( 'Mail failed to sent.', 'product-availability-notifier-for-woocommerce' );
			} else {
				esc_html_e( 'Mail sent successfully.', 'product-availability-notifier-for-woocommerce' );
			}
		}
	}

	public function change_status_to_email_sent( $results = array() ) {
		$order = wc_get_order( $order_id );

		global $wpdb;
		$table_name = $wpdb->prefix . 'paw_product_notify';
		foreach ( $results as $row ) {

			$wpdb->update(
				$table_name,
				array( 'status' => 'email-sent' ),
				array(
					'id' => $row['id'],
				)
			);
		}
	}
}

\PAW\Admin::instance();
