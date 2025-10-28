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
	 * Check is valid timestamp.
	 *
	 * @param string $timestamp Timestamp.
	 * @return boolean
	 */
	public static function is_timestamp( $timestamp = '' ) {
		// Check if it's numeric.
		if ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		// Try to create a DateTime object.
		try {
			$date = new DateTime( '@' . $timestamp );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Static logger instance
	 *
	 * @var Logger
	 */
	private static $logger;

	/**
	 * Get logger instance (lazy initialization)
	 *
	 * @return Logger
	 */
	private static function get_logger() {
		if ( ! self::$logger ) {
			self::$logger = new Logger();
		}
		return self::$logger;
	}

	/**
	 * Process callback for storage
	 *
	 * @param mixed $callback The callback to process.
	 * @return array|false Processed callback data or false on failure.
	 */
	public static function process_callback( $callback ) {
		// Handle string callbacks.
		if ( is_string( $callback ) ) {
			return array(
				'type'     => 'function',
				'callback' => $callback,
			);
		}

		// Handle array callbacks (object methods or static methods).
		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			$object_or_class = $callback[0];
			$method          = $callback[1];

			// Handle object method.
			if ( is_object( $object_or_class ) ) {
				return array(
					'type'   => 'object_method',
					'class'  => get_class( $object_or_class ),
					'method' => $method,
				);
			}

			// Handle static method.
			if ( is_string( $object_or_class ) ) {
				return array(
					'type'   => 'static_method',
					'class'  => $object_or_class,
					'method' => $method,
				);
			}
		}

		return false;
	}

	/**
	 * Reconstruct callback from stored data
	 *
	 * @param array $callback_data Stored callback data.
	 * @return mixed Reconstructed callback or false.
	 */
	public static function reconstruct_callback( $callback_data ) {
		$logger = self::get_logger();

		if ( ! is_array( $callback_data ) || ! isset( $callback_data['type'] ) ) {
			$logger->error( 'Invalid callback data' );
			return false;
		}

		switch ( $callback_data['type'] ) {
			case 'function':
				if ( function_exists( $callback_data['callback'] ) ) {
					return $callback_data['callback'];
				} else {
					$logger->error( 'Function does not exist: ' . $callback_data['callback'] );
					return false;
				}

			case 'static_method':
				if ( isset( $callback_data['class'] ) && isset( $callback_data['method'] ) ) {
					if ( ! class_exists( $callback_data['class'] ) ) {
						$logger->error( 'Class does not exist: ' . $callback_data['class'] );
						return false;
					}

					if ( ! method_exists( $callback_data['class'], $callback_data['method'] ) ) {
						$logger->error( 'Method does not exist: ' . $callback_data['class'] . '::' . $callback_data['method'] );
						return false;
					}

					return array( $callback_data['class'], $callback_data['method'] );
				} else {
					$logger->error( 'Missing class or method in static callback data' );
					return false;
				}

			case 'object_method':
				if ( isset( $callback_data['class'] ) && isset( $callback_data['method'] ) ) {
					if ( ! class_exists( $callback_data['class'] ) ) {
						$logger->error( 'Class does not exist: ' . $callback_data['class'] );
						return false;
					}

					// Try to get a singleton instance or create new instance.
					$instance = self::get_class_instance( $callback_data['class'] );
					if ( $instance ) {
						if ( method_exists( $instance, $callback_data['method'] ) ) {
							return array( $instance, $callback_data['method'] );
						} else {
							$logger->error( 'Method does not exist on instance: ' . $callback_data['class'] . '->' . $callback_data['method'] );
							return false;
						}
					} else {
						$logger->error( 'Could not instantiate class: ' . $callback_data['class'] );
						return false;
					}
				} else {
					$logger->error( 'Missing class or method in object callback data' );
					return false;
				}

			default:
				$logger->error( 'Unknown callback type: ' . $callback_data['type'] );
				return false;
		}
	}

	/**
	 * Get class instance for callback execution
	 *
	 * @param string $class_name Class name.
	 * @return object|false Class instance or false.
	 */
	private static function get_class_instance( $class_name ) {
		$logger = self::get_logger();

		if ( method_exists( $class_name, 'get_instance' ) ) {
			return call_user_func( array( $class_name, 'get_instance' ) );
		}

		try {
			$reflection  = new \ReflectionClass( $class_name );
			$constructor = $reflection->getConstructor();

			// Only create if constructor has no required parameters.
			if ( ! $constructor || $constructor->getNumberOfRequiredParameters() === 0 ) {
				return new $class_name();
			}
		} catch ( Exception $e ) {
			$logger->error( 'Could not instantiate class ' . $class_name . ':' . $e->getMessage() );
		}

		return false;
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
			$bool = self::string_to_bool( $bool );
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
