<?php
/**
 * Frontend class.
 *
 * @package restock-alerts-for-woocommerce\public\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
class Frontend {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );

		/**
		 * TODO:
		 * Variable product not supported yet, needs to send a variation product ID on email.
		 */
		add_filter( 'woocommerce_get_stock_html', array( $this, 'append_notify_form' ), 10, 2 );
		add_action( 'init', array( $this, 'handle_email_verification_link' ) );

		add_action( 'wp_ajax_restaler_save_notify_email', array( $this, 'save_notify_email' ) );
		add_action( 'wp_ajax_nopriv_restaler_save_notify_email', array( $this, 'save_notify_email' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'restaler-main', RESTALER_URL . '/public/assets/js/main.js', array( 'jquery' ), '1.0', true );
	}

	public function mail_from() {
		$from_address = get_option( 'stobokit_email_from_address', '' );
		return $from_address;
	}

	public function mail_from_name() {
		$from_name = get_option( 'stobokit_email_from_name', '' );
		return $from_name;
	}

	public function handle_email_verification_link() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['verify_email'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['token'] ) ) {
			return;
		}

		global $wpdb;
		$email = sanitize_email( wp_unslash( $_GET['email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! is_email( $email ) ) {
			wp_die( 'Invalid email format.', 'Verification Error', array( 'response' => 400 ) );
			return;
		}

		$table = $wpdb->prefix . 'restaler_restock_alerts';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE email = %s AND token = %s AND status = 'pending'",
				$email,
				$token
			)
		);

		if ( ! $row ) {
			wp_die( 'Invalid or expired verification link.', 'Verification Error', array( 'response' => 400 ) );
			return;
		}

		if ( $row ) {
			// Update status to 'subscribed'.
			$wpdb->update(
				$table,
				array(
					'status' => 'subscribed',
					'token'  => null,
				),
				array( 'email' => $email )
			);

			wp_safe_redirect( add_query_arg( 'verification', 'success', home_url() ) );
		}
	}

	public function save_notify_email() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'restaler-save-email' ) ) {
			die( esc_html__( 'Nonce failed.', 'restock-alerts-for-woocommerce' ) );
		} else {
			$email   = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			$product = isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '';

			$this->save_data_in_table( $email, $product );

			$verify_url = Utils::generate_verification_url( $email );

			/**
			 * TODO:
			 * If email is already exist any of the product skip the verification
			 * email directly change the staus to `subscribed`.
			 */

			$this->send_verification_email( $email, $product, $verify_url );
		}

		die();
	}

	public function save_data_in_table( $email = '', $product = 0 ) {
		global $wpdb;

		if ( ! empty( $email ) && ! empty( $product ) ) {
			$table = $wpdb->prefix . 'restaler_restock_alerts';

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE email = %s AND product_id = %d",
					$email,
					$product
				)
			);

			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'email'      => $email,
						'product_id' => $product,
						'status'     => 'pending',
					),
					array( '%s', '%d', '%s' )
				);
			} else {
				die( esc_html__( 'Email already added in this product.', 'restock-alerts-for-woocommerce' ) );
			}
		} else {
			die( esc_html__( 'Please enter the email address.', 'restock-alerts-for-woocommerce' ) );
		}
	}

	public function send_verification_email( $email = '', $product = 0, $verify_url = '' ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = esc_html__( 'Send verification email', 'restock-alerts-for-woocommerce' );

		ob_start();
		include RESTALER_PATH . '/template/email/html-verification-email.php';
		$content = ob_get_contents();
		ob_end_clean();

		$result = wp_mail( $email, $subject, $content, $headers );
		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'restock-alerts-for-woocommerce' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'restock-alerts-for-woocommerce' );
		}
	}

	/**
	 * Append notify form fields after the `out of stock` notice.
	 *
	 * @param string $html Notice html.
	 * @param object $product Product.
	 * @return string
	 */
	public function append_notify_form( $html, $product ) {

		$availability = $product->get_availability();

		if ( 'Out of stock' === $availability['availability'] ) {
			$nonce = wp_create_nonce( 'restaler-save-email' );

			$form  = '<form id="restaler-notify-form" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
			$form .= '<input name="email" type="text" placeholder="' . esc_attr__( 'Enter your email address', 'restock-alerts-for-woocommerce' ) . '">';
			$form .= '<button type="submit">' . esc_html__( 'Notify Me', 'restock-alerts-for-woocommerce' ) . '</button>';
			$form .= '</form>';
			return $html . $form;
		}

		return $html;
	}

}

\RESTALER\Frontend::instance();




