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
		add_filter( 'woocommerce_get_stock_html', array( $this, 'append_notify_form' ), 10, 2 );

		add_action( 'wp_ajax_paw_save_notify_email', array( $this, 'save_notify_email' ) );
		add_action( 'wp_ajax_nopriv_paw_save_notify_email', array( $this, 'save_notify_email' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'paw-main', PAW_URL . '/public/assets/js/main.js', array( 'jquery' ), '1.0', true );
	}

	public function save_notify_email() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'paw-save-email' ) ) {
			die( esc_html__( 'Nonce failed.', 'product-availability-notifier-for-woocommerce' ) );
		} else {
			$email   = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			$product = isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : '';

			$this->save_data_in_table( $email, $product );
			// $this->send_verification_email( $email, $product );
		}

		die();
	}

	public function save_data_in_table( $email = '', $product = 0 ) {
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
					),
					array( '%s', '%d', '%s' )
				);
			} else {
				die( esc_html__( 'Email already added in this product.', 'product-availability-notifier-for-woocommerce' ) );
			}
		} else {
			die( esc_html__( 'Please enter the email address.', 'product-availability-notifier-for-woocommerce' ) );
		}
	}

	public function send_verification_email( $email = '', $product = 0 ) {
		// pass the product id to the email template.
		// create a verification link.
		// add cronjobs for expire time.
		// send an email.
	}

	/**
	 * Append notify form fields after the `out of stock` notice.
	 *
	 * @param string $html Notice html.
	 * @param object $product Product.
	 * @return string
	 */
	public function append_notify_form( $html, $product ) {
		if ( $html ) {
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
