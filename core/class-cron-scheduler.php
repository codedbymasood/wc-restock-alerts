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
		$this->logger = new Logger();

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
			'override'      => false,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required parameters.
		if ( empty( $args['hook_name'] ) || empty( $args['callback'] ) ) {
			return false;
		}

		// Process the callback for storage.
		$callback_data = Utils::process_callback( $args['callback'] );
		if ( ! $callback_data ) {
			return false;
		}

		$unique_hook = $args['override']
			? 'stobokit_cron_' . $args['hook_name']
			: 'stobokit_cron_' . $args['hook_name'] . '_' . Utils::uid();

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
	 * Execute the cron job
	 *
	 * @param string $unique_hook The unique hook identifier.
	 */
	public function execute_cron_job( $unique_hook ) {

		$this->logger->info( 'Cron running - is_admin(): ' . ( is_admin() ? 'true' : 'false' ) );

		// Retrieve the callback data.
		$callback_data = get_option( $unique_hook . '_data' );

		if ( ! $callback_data ) {
			return;
		}

		$start_time = microtime( true );
		$success    = false;
		$error      = '';

		try {
			$callback = Utils::reconstruct_callback( $callback_data['callback'] );

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
			$this->logger->info( 'Successfully scheduled', array( 'execution_time' => $execution_time ) );
			$this->scheduler->update_status_by_uid( $unique_hook, 'completed' );
		} else {
			$this->logger->error( $error, array( 'execution_time' => $execution_time ) );
			$this->scheduler->update_status_by_uid( $unique_hook, 'failed' );
		}

		// Clean up the stored data after execution.
		delete_option( $unique_hook . '_data' );
	}
}
