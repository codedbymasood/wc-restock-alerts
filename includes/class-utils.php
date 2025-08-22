<?php
/**
 * Utils class.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace RESTALER;

defined( 'ABSPATH' ) || exit;

/**
 * Utils class.
 */
class Utils {
	/**
	 * Generate coupons.
	 *
	 * @return string
	 */
	public static function generate_discount( $args = array() ) {
		$code = self::generate_random_string();

		$product             = isset( $args['product'] ) ? $args['product'] : 0;
		$discount_type       = isset( $args['discount_type'] ) ? $args['discount_type'] : 'percent';
		$amount              = isset( $args['amount'] ) ? $args['amount'] : 20;
		$coupon_expires_date = isset( $args['coupon_expires_date'] ) ? $args['coupon_expires_date'] : '';

		$coupon = new \WC_Coupon();

		$coupon->set_code( $code );
		$coupon->set_discount_type( $discount_type );
		$coupon->set_amount( $amount );
		$coupon->set_date_expires( $coupon_expires_date );
		$coupon->set_product_ids( array( $product->get_id() ) );
		$coupon->set_usage_limit_per_user( 1 );

		$coupon->save();

		return $code;
	}

	public static function generate_verification_url( $email ) {
		// Generate a secure token.
		$token = bin2hex( random_bytes( 32 ) ); // 64-character hex string

		// Add expiration timestamp for additional security.
		$expires = time() + ( 7 * 24 * 60 * 60 );

		// Store token in database with expiration.
		global $wpdb;
		$table = $wpdb->prefix . 'restaler_restock_alerts';

		$wpdb->update(
			$table,
			array(
				'token'         => $token,
				'token_expires' => $expires,
				'status'        => 'pending',
			),
			array( 'email' => $email )
		);

		$verify_url = add_query_arg(
			array(
				'verify_email' => 1,
				'email'        => rawurlencode( $email ),
				'token'        => $token,
			),
			home_url()
		);

		return $verify_url;
	}

	/**
	 * Convert string cases
	 *
	 * @param string $string String.
	 * @param string $to_case Change text case.
	 * @return string
	 */
	public static function convert_case( $string, $to_case = 'kebab' ) {
		// Normalize the string: replace dashes and underscores with spaces.
		$string = preg_replace( '/[_\-]+/', ' ', $string );
		$string = strtolower( $string );

		$words = explode( ' ', $string );

		switch ( $to_case ) {
			case 'snake':
				return implode( '_', $words );
			case 'kebab':
				return implode( '-', $words );
			case 'camel':
				return lcfirst( str_replace( ' ', '', ucwords( implode( ' ', $words ) ) ) );
			case 'pascal':
				return str_replace( ' ', '', ucwords( implode( ' ', $words ) ) );
			case 'title':
				return ucwords( implode( ' ', $words ) );
			default:
				return $string;
		}
	}

	/**
	 * Generate random string.
	 *
	 * @param integer $length Length.
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
