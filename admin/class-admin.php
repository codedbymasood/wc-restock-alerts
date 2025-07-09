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
 * Admin class.
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
		add_action( 'wp_insert_post', array( $this, 'save_product' ), 99, 3 );
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

		$product      = wc_get_product( $post_id );
		$stock_status = $product->get_stock_status();

		if ( 'outofstock' === $stock_status ) {
			return;
		}

		// To prevent call stack.
		remove_action( 'wp_save_product', array( $this, 'save_product' ) );

		$results = $this->get_emails( $post_id );

		if ( $results ) {
			foreach ( $results as $row ) {
				$this->send_notify_emails( $row );
				$this->change_status_to_email_sent( $row );
				$this->create_followup_schedule( $row, $product );
			}
		}

		add_action( 'wp_save_product', array( $this, 'save_product' ) );
	}

	public function get_emails( $post_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'paw_product_notify';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE product_id = %d AND status = %s",
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

	public function change_status_to_email_sent( $row = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'paw_product_notify';
		$wpdb->update(
			$table_name,
			array( 'status' => 'email-sent' ),
			array(
				'id' => $row['id'],
			)
		);
	}

	public function create_followup_schedule( $row = array(), $product = null ) {
		$first_followup  = time() + ( 2 * DAY_IN_SECONDS ); // 2 days later
		$second_followup = $first_followup + ( 3 * DAY_IN_SECONDS ); // 5 days total

		wp_schedule_single_event(
			$first_followup,
			'paw_still_interested_followup_email',
			array( $row, $product )
		);

		wp_schedule_single_event(
			$second_followup,
			'paw_urgency_followup_email',
			array( $row, $product )
		);
	}
}

\PAW\Admin::instance();
