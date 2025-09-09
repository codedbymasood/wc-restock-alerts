<?php
/**
 * Frontend class.
 *
 * @package plugin-slug\public\
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
		add_action( 'init', array( $this, 'handle_email_verification_link' ) );

		add_action( 'wp_ajax_restaler_save_notify_email', array( $this, 'save_notify_email' ) );
		add_action( 'wp_ajax_nopriv_restaler_save_notify_email', array( $this, 'save_notify_email' ) );

		add_action( 'woocommerce_simple_add_to_cart', array( $this, 'append_notify_form' ), 35 );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'restaler-main', RESTALER_URL . '/common/public/assets/css/main.css', array(), '1.0', 'all' );
		wp_enqueue_script( 'restaler-main', RESTALER_URL . '/common/public/assets/js/main.js', array( 'jquery' ), '1.0', true );
	}

	public function mail_from() {
		$from_email = get_option( 'stobokit_email_from_email', '' );
		$from_email = $from_email ? $from_email : get_option( 'admin_email', '' );

		return $from_email;
	}

	public function mail_from_name() {
		$from_name = get_option( 'stobokit_email_from_name', '' );
		$from_name = $from_name ? $from_name : get_option( 'blogname', '' );

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
			die( esc_html__( 'Nonce failed.', 'plugin-slug' ) );
		} else {
			$email        = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			$product      = isset( $_POST['product'] ) ? sanitize_text_field( wp_unslash( $_POST['product'] ) ) : 0;
			$product_type = isset( $_POST['product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['product_type'] ) ) : '';
			$variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : 0;

			$this->save_data_in_table( $email, $product, $variation_id );

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

	public function save_data_in_table( $email = '', $product = 0, $variation_id = 0 ) {
		global $wpdb;

		if ( ! empty( $email ) && ! empty( $product ) ) {
			$table = $wpdb->prefix . 'restaler_restock_alerts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
						'email'        => $email,
						'product_id'   => $product,
						'variation_id' => $variation_id,
						'status'       => 'pending',
					),
					array( '%s', '%d', '%d', '%s' )
				);
			} else {
				die( esc_html__( 'Email already added in this product.', 'plugin-slug' ) );
			}
		} else {
			die( esc_html__( 'Please enter the email address.', 'plugin-slug' ) );
		}
	}

	public function send_verification_email( $email = '', $product = 0, $verify_url = '' ) {
		$subject     = get_option( 'restaler_verification_email_subject', esc_html__( 'Send verification email', 'plugin-slug' ) );
		$heading     = get_option( 'restaler_verification_email_heading', esc_html__( 'Confirm your email', 'plugin-slug' ) );
		$footer_text = get_option(
			'restaler_verification_email_footer_text',
			esc_html__(
				'You\'re receiving this email because you registered with {site_name}.

If you didn\'t request this, please ignore it.',
				'plugin-slug'
			)
		);

		$content = get_option(
			'restaler_verification_email_content',
			"Hi {customer_name},

Thanks for signing up. Please confirm your email address by clicking the button below:

{verify_url_btn}

If the button doesn't work, copy and paste this link into your browser:

{verify_url}

This verification link will expire soon. If you did not request this, please ignore this email.

Warmly,
The {site_name} Team"
		);

		$html = restaler()->templates->get_template(
			'email/email-content.php',
			array(
				'heading'     => $heading,
				'content'     => $content,
				'footer_text' => $footer_text,
			)
		);

		$result = restaler()->emailer->send_now( $email, $subject, $html, array( 'verify_url' => $verify_url ) );

		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'plugin-slug' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'plugin-slug' );
		}
	}

	/**
	 * Append notify form fields after the `out of stock` notice.
	 *
	 * @return void
	 */
	public function append_notify_form() {
		global $product;

		$product_type = $product->get_type();

		if ( 'simple' === $product_type && $product->is_purchasable() && ! $product->is_in_stock() ) {
			restaler()->templates->include_template(
				'notify-form.php',
				array(
					'product' => $product,
					'type'    => $product_type,
					'hide'    => false,
				)
			);
		}
	}
}

\RESTALER\Frontend::instance();
