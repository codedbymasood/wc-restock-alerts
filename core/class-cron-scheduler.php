<?php
/**
 * Cron scheduler class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.1
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Cron scheduler class.
 */
class Cron_Scheduler {

	/**
	 * Constructor - Register the universal cron handler
	 */
	public function __construct() {
		$this->scheduler = new Schedule_Logger();
		// Register a single action that handles all our cron jobs.
		add_action( 'stobokit_cron_execute', array( $this, 'execute_cron_job' ), 10, 3 );
	}

	/**
	 * Create a new single event cron schedule
	 *
	 * @param array $args {
	 *     Array of arguments for creating the cron schedule.
	 *
	 *     @type string $hook_name     Hook name for the scheduled event.
	 *     @type mixed  $callback      Callback function to execute.
	 *     @type int    $timestamp     Unix timestamp when to run the event.
	 *     @type array  $callback_args Optional arguments to pass to callback.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function create_schedule( $args ) {

		$defaults = array(
			'hook_name'     => '',
			'callback'      => '',
			'timestamp'     => time() + 3600, // 1 hour default
			'callback_args' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required parameters.
		if ( empty( $args['hook_name'] ) || empty( $args['callback'] ) ) {
			return false;
		}

		// Process the callback for storage.
		$callback_data = $this->process_callback( $args['callback'] );
		if ( ! $callback_data ) {
			return false;
		}

		$unique_hook = $args['override']
			? 'stobokit_cron_' . $args['hook_name']
			: 'stobokit_cron_' . $args['hook_name'] . '_' . uniqid();

		// Handle override logic.
		if ( $args['override'] ) {
			wp_unschedule_hook( $unique_hook );
		}

		if ( wp_next_scheduled( $unique_hook ) ) {
			return true;
		}

		// Store callback data in WordPress options for retrieval during execution.
		update_option(
			$unique_hook . '_data',
			array(
				'original_hook' => $args['hook_name'],
				'callback'      => $callback_data,
				'callback_args' => $args['callback_args'],
			)
		);

		// Schedule the event.
		wp_schedule_single_event( $args['timestamp'], 'stobokit_cron_execute', array( $unique_hook ) );

		$this->handle_schedule_logging( $unique_hook, $args );

		return true;
	}

	/**
	 * Handle logging for scheduled events
	 */
	private function handle_schedule_logging( $unique_hook, $args ) {
		if ( $args['override'] ) {
			$row = $this->scheduler->get_log_by_uid( $unique_hook );
			if ( $row && $row->id ) {
				$this->scheduler->update_log(
					$row->id,
					array(
						'schedule' => $args['timestamp'],
						'status'   => 'scheduled',
					)
				);
				return;
			}
		}

		$this->scheduler->insert_log(
			$unique_hook,
			$args['hook_name'],
			array( $args['callback_args'] ),
			$args['timestamp'],
			null,
			'scheduled'
		);
	}

	/**
	 * Process callback for storage
	 *
	 * @param mixed $callback The callback to process.
	 * @return array|false Processed callback data or false on failure.
	 */
	private function process_callback( $callback ) {
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
	 * Execute the cron job
	 *
	 * @param string $unique_hook The unique hook identifier.
	 */
	public function execute_cron_job( $unique_hook ) {
		// Retrieve the callback data.
		$callback_data = get_option( $unique_hook . '_data' );

		if ( ! $callback_data ) {
			return;
		}

		$start_time = microtime( true );
		$success    = false;
		$error      = '';

		try {
			$callback = $this->reconstruct_callback( $callback_data['callback'] );

			if ( $callback && is_callable( $callback ) ) {
				if ( ! empty( $callback_data['callback_args'] ) ) {
					call_user_func( $callback, $callback_data['callback_args'] );
				} else {
					call_user_func( $callback );
				}
				$success = true;
			} else {
				$error = 'Callback is not callable';
			}
		} catch ( Exception $e ) {
			$error = $e->getMessage();
		} catch ( Error $e ) {
			$error = $e->getMessage();
		}

		$execution_time = microtime( true ) - $start_time;

		if ( $success ) {
			$this->scheduler->update_status_by_uid( $unique_hook, 'completed' );
		} else {
			$this->scheduler->update_status_by_uid( $unique_hook, 'failed' );
		}

		// Clean up the stored data after execution.
		delete_option( $unique_hook . '_data' );
	}

	/**
	 * Reconstruct callback from stored data
	 *
	 * @param array $callback_data Stored callback data.
	 * @return mixed Reconstructed callback or false.
	 */
	private function reconstruct_callback( $callback_data ) {
		if ( ! is_array( $callback_data ) || ! isset( $callback_data['type'] ) ) {
			return false;
		}

		switch ( $callback_data['type'] ) {
			case 'function':
				return $callback_data['callback'];

			case 'static_method':
				if ( isset( $callback_data['class'] ) && isset( $callback_data['method'] ) ) {
					return array( $callback_data['class'], $callback_data['method'] );
				}
				break;

			case 'object_method':
				if ( isset( $callback_data['class'] ) && isset( $callback_data['method'] ) ) {
					// Try to get a singleton instance or create new instance.
					$instance = $this->get_class_instance( $callback_data['class'] );
					if ( $instance ) {
						return array( $instance, $callback_data['method'] );
					}
				}
				break;
		}

		return false;
	}

	/**
	 * Get class instance for callback execution
	 *
	 * @param string $class_name Class name.
	 * @return object|false Class instance or false.
	 */
	private function get_class_instance( $class_name ) {
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
			error_log( "Could not instantiate class {$class_name}: " . $e->getMessage() );
		}

		return false;
	}
}
