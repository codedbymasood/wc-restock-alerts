<?php
/**
 * Scheduler logger class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Scheduler logger class.
 */
class Schedule_Logger {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Database
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->table_name = $wpdb->prefix . 'stobokit_scheduler_logs';
	}

	/**
	 * Insert a new cron log entry
	 *
	 * @param string $uid Unique ID.
	 * @param string $hook_name The name of the cron hook.
	 * @param array  $args Arguments passed to the cron job (will be serialized).
	 * @param string $schedule Schedule type (hourly, daily, etc.).
	 * @param string $next_run Next run datetime (Y-m-d H:i:s format).
	 * @param string $status Status of the job (default: 'scheduled').
	 *
	 * @return int|false Insert ID on success, false on failure.
	 */
	public function insert_log( $uid = '', $hook_name = '', $args = array(), $schedule = null, $next_run = null, $status = 'scheduled' ) {
		$data = array(
			'uid'        => sanitize_text_field( $uid ),
			'hook_name'  => sanitize_text_field( $hook_name ),
			'args'       => wp_json_encode( $args ),
			'schedule'   => $schedule ? sanitize_text_field( $schedule ) : null,
			'next_run'   => $next_run,
			'status'     => sanitize_text_field( $status ),
			'attempts'   => 0,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array(
			'%s', // uid.
			'%s', // hook_name.
			'%s', // args.
			'%s', // schedule.
			'%s', // next_run.
			'%s', // status.
			'%d', // attempts.
			'%s', // created_at.
			'%s', // updated_at.
		);

		$result = $this->wpdb->insert( $this->table_name, $data, $format );

		return false !== $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Update an existing cron log entry
	 *
	 * @param int   $id Log entry ID.
	 * @param array $data Data to update.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function update_log( $id, $data ) {
		$allowed_fields = array( 'hook_name', 'args', 'schedule', 'next_run', 'status', 'attempts' );
		$update_data    = array();
		$format         = array();

		foreach ( $data as $field => $value ) {
			if ( in_array( $field, $allowed_fields, true ) ) {
				switch ( $field ) {
					case 'uid':
					case 'hook_name':
					case 'schedule':
					case 'status':
						$update_data[ $field ] = sanitize_text_field( $value );

						$format[] = '%s';
						break;
					case 'args':
						$update_data[ $field ] = is_array( $value ) ? wp_json_encode( $value ) : sanitize_text_field( $value );

						$format[] = '%s';
						break;
					case 'attempts':
						$update_data[ $field ] = absint( $value );

						$format[] = '%d';
						break;
					case 'next_run':
						$update_data[ $field ] = $value;

						$format[] = '%s';
						break;
				}
			}
		}

		// Always update the updated_at timestamp.
		$update_data['updated_at'] = current_time( 'mysql' );

		$format[] = '%s';

		$where        = array( 'id' => absint( $id ) );
		$where_format = array( '%d' );

		$result = $this->wpdb->update( $this->table_name, $update_data, $where, $format, $where_format );

		return false !== $result;
	}

	/**
	 * Update log status
	 *
	 * @param int    $id Log entry ID.
	 * @param string $status New status.
	 *
	 * @return bool True on success, false on failure
	 */
	public function update_status( $id = 0, $status = '' ) {
			return $this->update_log( $id, array( 'status' => $status ) );
	}

	/**
	 * Increment attempts counter
	 *
	 * @param int $id Log entry ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function increment_attempts( $id = 0 ) {
		$current_attempts = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT attempts FROM {$this->table_name} WHERE id = %d",
				$id
			)
		);

		if ( null !== $current_attempts ) {
			return $this->update_log(
				$id,
				array(
					'attempts' => $current_attempts + 1,
				)
			);
		}

		return false;
	}

	/**
	 * Get a log entry by UID
	 *
	 * @param string $uid Unique identifier.
	 * @return object|null Log entry object or null if not found.
	 */
	public function get_log_by_hook( $hook = '' ) {
		if ( empty( $uid ) ) {
			return null;
		}

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE hook_name = %s",
				sanitize_text_field( $hook )
			)
		);
	}

	/**
	 * Get a log entry by UID
	 *
	 * @param string $uid Unique identifier.
	 * @return object|null Log entry object or null if not found.
	 */
	public function get_log_by_uid( $uid = '' ) {
		if ( empty( $uid ) ) {
			return null;
		}

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE uid = %s",
				sanitize_text_field( $uid )
			)
		);
	}

	/**
	 * Update a log entry by UID
	 *
	 * @param string $uid Unique identifier.
	 * @param array  $data Data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_log_by_uid( $uid = '', $data = array() ) {
		if ( empty( $uid ) ) {
			return false;
		}

		$allowed_fields = array( 'hook_name', 'args', 'schedule', 'next_run', 'status', 'attempts' );
		$update_data    = array();
		$format         = array();

		foreach ( $data as $field => $value ) {
			if ( in_array( $field, $allowed_fields, true ) ) {
				switch ( $field ) {
					case 'hook_name':
					case 'schedule':
					case 'status':
						$update_data[ $field ] = sanitize_text_field( $value );
						$format[]              = '%s';
						break;
					case 'args':
						if ( is_array( $value ) ) {
							$encoded = wp_json_encode( $value );
							$update_data[ $field ] = ( false !== $encoded ) ? $encoded : '[]';
						} else {
							$update_data[ $field ] = sanitize_text_field( $value );
						}
						$format[] = '%s';
						break;
					case 'attempts':
						$update_data[ $field ] = absint( $value );
						$format[]              = '%d';
						break;
					case 'next_run':
						// Validate datetime format
						$sanitized_value = sanitize_text_field( $value );
						// Optional: Add stricter datetime validation
						if ( strtotime( $sanitized_value ) !== false ) {
							$update_data[ $field ] = $sanitized_value;
							$format[]              = '%s';
						}
						break;
				}
			}
		}

		// If no valid fields to update, return false
		if ( empty( $update_data ) ) {
			return false;
		}

		// Always update the updated_at timestamp.
		$update_data['updated_at'] = current_time( 'mysql' );
		$format[] = '%s';

		$where        = array( 'uid' => sanitize_text_field( $uid ) );
		$where_format = array( '%s' );

		$result = $this->wpdb->update( $this->table_name, $update_data, $where, $format, $where_format );

		return false !== $result;
	}

	/**
	 * Update log status by UID
	 *
	 * @param string $uid Unique identifier.
	 * @param string $status New status.
	 * @return bool True on success, false on failure.
	 */
	public function update_status_by_uid( $uid = '', $status = '' ) {
		return $this->update_log_by_uid( $uid, array( 'status' => $status ) );
	}

	/**
	 * Delete a log entry by ID
	 *
	 * @param int $id Log entry ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_log( $id = 0 ) {
		if ( empty( $id ) ) {
			return false;
		}

		$result = $this->wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a log entry by UID
	 *
	 * @param string $uid Unique identifier.
	 * @return bool True on success, false on failure.
	 */
	public function delete_log_by_uid( $uid = '' ) {
		if ( empty( $uid ) ) {
			return false;
		}

		$result = $this->wpdb->delete(
			$this->table_name,
			array( 'uid' => $uid ),
			array( '%s' )
		);

		return false !== $result;
	}

	/**
	 * Delete log entries by hook name
	 *
	 * @param string $hook_name Hook name to delete logs for.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public function delete_logs_by_hook( $hook_name = '' ) {
		if ( empty( $hook_name ) ) {
			return false;
		}

		return $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE hook_name = %s",
				$hook_name
			)
		);
	}

	/**
	 * Delete old log entries
	 *
	 * @param int $days Delete logs older than this many days.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public function cleanup_old_logs( $days = 30 ) {
		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE created_at < %s",
				$date_threshold
			)
		);
	}
}
