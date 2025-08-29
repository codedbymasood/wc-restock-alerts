<?php
/**
 * Register cronjobs class.
 *
 * @package restock-alerts-for-woocommerce\public\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

use Pelago\Emogrifier\CssInliner;

defined( 'ABSPATH' ) || exit;

/**
 * Register cronjobs class.
 */
class Cron {

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
		add_action( 'restaler_still_interested_followup_email', array( $this, 'send_still_interested_followup_email' ), 10, 2 );
		add_action( 'restaler_urgency_followup_email', array( $this, 'send_urgency_followup_email' ), 10, 2 );
	}

	public function send_still_interested_followup_email( $row = array(), $args ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = get_option( 'restaler_first_followup_email_subject', esc_html__( 'Just a Quick Reminder', 'restock-alerts-for-woocommerce' ) );

		ob_start();
		include RESTALER_PATH . '/templates/email/html-still-interested-followup-email.php';
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

	public function send_urgency_followup_email( $row = array(), $args = array() ) {
		$email      = $row['email'];
		$product_id = $row['product_id'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$subject = get_option( 'restaler_second_followup_email_subject', esc_html__( 'Last Chance! 20% Off Ends in 3 Days', 'restock-alerts-for-woocommerce' ) );

		ob_start();
		include RESTALER_PATH . '/templates/email/html-urgency-followup-email.php';
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

}

\RESTALER\Cron::instance();




