<?php
/**
 * Register cronjobs class.
 *
 * @package product-availability-notifier-for-woocommerce\public\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PANW;

defined( 'ABSPATH' ) || exit;

/**
 * Register cronjobs class.
 */
class Cron {

	/**
	 * Singleton instance.
	 *
	 * @var PANW|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PANW
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
		add_action( 'panw_still_interested_followup_email', array( $this, 'send_still_interested_followup_email' ), 10, 2 );
		add_action( 'panw_urgency_followup_email', array( $this, 'send_urgency_followup_email' ), 10, 2 );
	}

	public function send_still_interested_followup_email( $row = array(), $args ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = get_option( 'panw_first_followup_email_subject', esc_html__( 'Just a Quick Reminder', 'product-availability-notifier-for-woocommerce' ) );

		ob_start();
		include PANW_PATH . '/template/email/html-still-interested-followup-email.php';
		$content = ob_get_contents();
		ob_end_clean();

		$result = wp_mail( $email, $subject, $content, $headers );
		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'product-availability-notifier-for-woocommerce' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'product-availability-notifier-for-woocommerce' );
		}
	}

	public function send_urgency_followup_email( $row = array(), $args = array() ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = get_option( 'panw_second_followup_email_subject', esc_html__( 'Last Chance! 20% Off Ends in 3 Days', 'product-availability-notifier-for-woocommerce' ) );

		ob_start();
		include PANW_PATH . '/template/email/html-urgency-followup-email.php';
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

\PANW\Cron::instance();




