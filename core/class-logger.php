<?php
/**
 * Logger class.
 *
 * Usage Examples:
 * ==============
 * $logger->info('User login successful', ['user_id' => 123, 'login_method' => 'password']);
 * $logger->warning('API rate limit approaching', ['requests' => 950, 'limit' => 1000]);
 * $logger->error('Payment processing failed', ['order_id' => 456, 'error' => 'Card declined']);
 * $logger->debug('Processing order', ['step' => 'validation', 'order_id' => 789]);
 *
 * $this->addLog('INFO', $message, $context);
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Logger class.
 */
class Logger {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'stobokit_debug_logs';

	/**
	 * Max log count.
	 *
	 * @var integer
	 */
	private $max_logs = 50;

	/**
	 * Constructor.
	 *
	 * @param integer $max_logs Max log count.
	 */
	public function __construct( $max_logs = 50 ) {
		$this->max_logs = $max_logs;
	}

	public function add_log( $level, $message, $context = array() ) {
		// Get existing logs.
		$logs = get_option( $this->option_name, array() );

		// Create new log entry.
		$log_entry = array(
			'id'        => Utils::uid(),
			'timestamp' => current_time( 'mysql' ),
			'level'     => strtoupper( $level ),
			'message'   => $message,
			'context'   => $context,
			'file'      => $this->get_caller_info()['file'] ?? null,
			'line'      => $this->get_caller_info()['line'] ?? null,
			'user_id'   => get_current_user_id(),
			'ip'        => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null,
		);

		// Add to beginning of array.
		array_unshift( $logs, $log_entry );

		// Keep only the last X logs.
		$logs = array_slice( $logs, 0, $this->max_logs );

		// Save back to options.
		update_option( $this->option_name, $logs );
	}

	public function get_caller_info() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 4 );

		// Skip this method and the log level method.
		$caller = $backtrace[3] ?? $backtrace[2] ?? array();

		return array(
			'file' => isset( $caller['file'] ) ? basename( $caller['file'] ) : null,
			'line' => $caller['line'] ?? null,
		);
	}

	public function info( $message, $context = array() ) {
		$this->add_log( 'INFO', $message, $context );
	}

	public function warning( $message, $context = array() ) {
		$this->add_log( 'WARNING', $message, $context );
	}

	public function error( $message, $context = array() ) {
		$this->add_log( 'ERROR', $message, $context );
	}

	public function debug( $message, $context = array() ) {
		$this->add_log( 'DEBUG', $message, $context );
	}

	public function get_logs() {
		return get_option( $this->option_name, array() );
	}

	public function clear_logs() {
		return delete_option( $this->option_name );
	}

	public function export_as_text() {
		$logs = $this->get_logs();

		$text  = "=== DEBUG LOGS EXPORT ===\n";
		$text .= 'Generated: ' . current_time( 'mysql' ) . "\n";
		$text .= 'Total Records: ' . count( $logs ) . "\n";
		$text .= str_repeat( '=', 40 ) . "\n\n";

		foreach ( $logs as $log ) {
			$text .= "[{$log['timestamp']}] {$log['level']}: {$log['message']}\n";

			if ( ! empty( $log['context'] ) ) {
					$text .= 'Context: ' . wp_json_encode( $log['context'] ) . "\n";
			}

			if ( $log['file'] ) {
				$text .= "File: {$log['file']}";
				if ( $log['line'] ) {
					$text .= " (Line: {$log['line']})";
				}
				$text .= "\n";
			}

			if ( $log['user_id'] ) {
				$user = get_user_by( 'ID', $log['user_id'] );
				$text .= 'User: ' . ( $user ? $user->user_login : 'Unknown' ) . " (ID: {$log['user_id']})\n";
			}

			if ( $log['ip'] ) {
				$text .= "IP: {$log['ip']}\n";
			}

			$text .= str_repeat( '-', 25 ) . "\n\n";
		}

		return $text;
	}
}
