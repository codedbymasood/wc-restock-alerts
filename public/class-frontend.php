<?php
/**
 * Frontend class.
 *
 * @package product-availability-notifier-for-woocommerce\public\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
class Frontend {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );

		/**
		 * TODO:
		 * Variable product not supported yet, needs to send a variation product ID on email.
		 */
		add_filter( 'woocommerce_get_stock_html', array( $this, 'append_notify_form' ), 10, 2 );
		add_action( 'init', array( $this, 'handle_email_verification_link' ) );

		add_action( 'wp_ajax_paw_save_notify_email', array( $this, 'save_notify_email' ) );
		add_action( 'wp_ajax_nopriv_paw_save_notify_email', array( $this, 'save_notify_email' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'paw-main', PAW_URL . '/public/assets/js/main.js', array( 'jquery' ), '1.0', true );
	}

	public function mail_from() {
		$from_address = get_option( 'paw_from_address', '' );
		return $from_address;
	}

	public function mail_from_name() {
		$from_name = get_option( 'paw_from_name', '' );
		return $from_name;
	}

	public function handle_email_verification_link() {
		if ( ! isset( $_GET['verify_email'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['token'] ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'paw_product_notify';
		$email = sanitize_email( $_GET['email'] );
		$token = sanitize_text_field( $_GET['token'] );

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s AND token = %s", $email, $token ) );

		if ( $row ) {
			// Update status to 'subscribed'.
			$wpdb->update(
				$table,
				array( 'status' => 'subscribed' ),
				array( 'email' => $email )
			);
		}
	}

	public function save_notify_email() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'paw-save-email' ) ) {
			die( esc_html__( 'Nonce failed.', 'product-availability-notifier-for-woocommerce' ) );
		} else {
			$email   = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			$product = isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '';

			$token = wp_generate_password( 32, false );

			$this->save_data_in_table( $email, $product, $token );

			/**
			 * TODO:
			 * If email is already exist any of the product skip the verification
			 * email directly change the staus to `subscribed`.
			 */

			$this->send_verification_email( $email, $product, $token );
		}

		die();
	}

	public function save_data_in_table( $email = '', $product = 0, $token = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'paw_product_notify';

		if ( ! empty( $email ) && ! empty( $product ) ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE email = %s AND product_id = %d",
					$email,
					$product
				)
			);

			if ( ! $exists ) {
				$wpdb->insert(
					$wpdb->prefix . 'paw_product_notify',
					array(
						'email'      => $email,
						'product_id' => $product,
						'status'     => 'pending',
						'token'      => $token,
					),
					array( '%s', '%d', '%s', '%s' )
				);
			} else {
				die( esc_html__( 'Email already added in this product.', 'product-availability-notifier-for-woocommerce' ) );
			}
		} else {
			die( esc_html__( 'Please enter the email address.', 'product-availability-notifier-for-woocommerce' ) );
		}
	}

	public function send_verification_email( $email = '', $product = 0, $token = '' ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = esc_html__( 'Send verification email', 'product-availability-notifier-for-woocommerce' );

		ob_start();
		include PAW_PATH . '/template/email/html-verification-email.php';
		$content = ob_get_contents();
		ob_end_clean();

		$result = wp_mail( $email, $subject, $content, $headers );
		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'product-availability-notifier-for-woocommerce' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'product-availability-notifier-for-woocommerce' );
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
			$nonce = wp_create_nonce( 'paw-save-email' );

			$form  = '<form id="paw-notify-form" method="POST" data-product-id="' . esc_attr( $product->get_id() ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
			$form .= '<input name="email" type="text" placeholder="' . esc_attr__( 'Enter your email address', 'product-availability-notifier-for-woocommerce' ) . '">';
			$form .= '<button type="submit">' . esc_html__( 'Notify Me', 'product-availability-notifier-for-woocommerce' ) . '</button>';
			$form .= '</form>';
			return $html . $form;
		}

		return $html;
	}

}

\PAW\Frontend::instance();




