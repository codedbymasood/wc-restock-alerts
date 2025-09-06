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
