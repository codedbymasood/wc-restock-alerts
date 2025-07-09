<?php
/**
 * Register cronjobs class.
 *
 * @package product-availability-notifier-for-woocommerce\public\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Register cronjobs class.
 */
class Cron {

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
		add_action( 'paw_still_interested_followup_email', array( $this, 'send_still_interested_followup_email' ) );
		add_action( 'paw_urgency_followup_email', array( $this, 'send_urgency_followup_email' ) );
	}

	public function send_still_interested_followup_email( $row = array() ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = esc_html__( 'Still interested?', 'product-availability-notifier-for-woocommerce' );

		ob_start();
		include PAW_PATH . '/template/email/html-still-interested-followup-email.php';
		$content = ob_get_contents();
		ob_end_clean();

		$result = wp_mail( $email, $subject, $content, $headers );
		if ( ! $result ) {
			esc_html_e( 'Mail failed to sent.', 'product-availability-notifier-for-woocommerce' );
		} else {
			esc_html_e( 'Mail sent successfully.', 'product-availability-notifier-for-woocommerce' );
		}
	}

	public function send_urgency_followup_email( $row = array() ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = esc_html__( 'Hurry!!!', 'product-availability-notifier-for-woocommerce' );

		ob_start();
		include PAW_PATH . '/template/email/html-urgency-followup-email.php';
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

\PAW\Cron::instance();




