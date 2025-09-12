<?php
/**
 * Utils class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Utils class.
 */
class Utils {

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
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 *
	 * @param string|bool $string String to convert. If a bool is passed it will be returned as-is.
	 * @return bool
	 */
	public static function string_to_bool( $string ) {
		$string = $string ?? '';
		return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @param bool|string $bool Bool to convert. If a string is passed it will first be converted to a bool.
	 * @return string
	 */
	public static function bool_to_string( $bool ) {
		if ( ! is_bool( $bool ) ) {
			$bool = wc_string_to_bool( $bool );
		}
		return true === $bool ? 'yes' : 'no';
	}

	/**
	 * Generate random string.
	 *
	 * @param integer $length Length.
	 * @return string
	 */
	public static function generate_random_string( $length = 8 ) {
		$random_string = '';
		$characters    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}
		return $random_string;
	}

	/**
	 * Generate unique ID
	 *
	 * @return string
	 */
	public static function uid() {
		return self::generate_random_string( 4 ) . '-' . self::generate_random_string( 4 );
	}
}
