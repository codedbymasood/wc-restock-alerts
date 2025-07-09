<?php
/**
 * Utils class.
 *
 * @package product-availability-notifier-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Utils class.
 */
class Utils {
	/**
	 * Generate coupons.
	 *
	 * @return void
	 */
	public static function generate_discount( $args = array() ) {
		$coupon = new \WC_Coupon();

		$coupon->set_code( self::generate_random_string() );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 20 );
		$coupon->set_date_expires( '31-12-2027' );
		$coupon->set_product_ids( array( $product_id ) );
		$coupon->set_usage_limit_per_user( 1 );

		$coupon->save();
	}

	/**
	 * Generate coupon code.
	 *
	 * @return string
	 */
	public static function generate_random_string( $length = 8 ) {
		$random_string = '';
		$characters    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}
		return $random_string;
	}
}
