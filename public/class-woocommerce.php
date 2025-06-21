<?php
/**
 * Plugin initialization class.
 *
 * @package product-availability-notifier-for-woocommerce\includes\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin loader.
 */
class Woocommerce {

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
		add_filter( 'woocommerce_get_stock_html', array( $this, 'append_notify_form' ), 10, 2 );
	}

	public function append_notify_form( $html, $product ) {
		if ( $html ) {
			$form  = '<form>';
			$form .= '<input type="email" placeholder="' . esc_attr__( 'Enter your email address', 'product-availability-notifier-for-woocommerce' ) . '">';
			$form .= '<button type="type">' . esc_html__( 'Notify Me', 'product-availability-notifier-for-woocommerce' ) . '</button>';
			$form .= '</form>';
			return $html . $form;
		}

		return $html;
	}

}

\PAW\Woocommerce::instance();
