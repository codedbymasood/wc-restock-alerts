<?php
/**
 * Emailer class with validation callback support and dedicated database table.
 *
 * Usage Examples:
 * ==============
 * // Send later with validation
 * $emailer->send_later(
 *   $customer_email,
 *   'Thanks for your purchase!',
 *   '<h1>Thank you {customer_name}!</h1>',
 *   1,
 *   array('customer_name' => $customer_name),
 *   'thank_you_email',
 *   function($email_data, $args) {
 *     // Only send if order status is still 'completed'
 *     $order_id = isset($args['order_id']) ? $args['order_id'] : null;
 *     if ($order_id) {
 *       $order = wc_get_order($order_id);
 *       return $order && $order->get_status() === 'completed';
 *     }
 *     return true;
 *   }
 * );
 *
 * // Create sequence with validation
 * $sequence_id = $emailer->create_followup_sequence(
 *   $customer_email,
 *   array(
 *     array(
 *       'days' => 1,
 *       'subject' => 'Thank you!',
 *       'message' => '...',
 *       'validation_callback' => function($email_data, $args) {
 *         // Custom validation for this specific email
 *         return some_condition_check($args);
 *       }
 *     ),
 *     array('days' => 3, 'subject' => 'How is it?', 'message' => '...'),
 *   ),
 *   array('customer_name' => $customer_name, 'order_id' => $order_id),
 *   '',
 *   'follow_up_sequence',
 *   function($email_data, $args) {
 *     // Global validation for the entire sequence
 *     $customer_email = $email_data['to'];
 *     $user = get_user_by('email', $customer_email);
 *     return $user && $user->ID; // Only send if user still exists
 *   }
 * );
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Emailer class with validation support and database table.
 */
class Emailer {

	/**
	 * Instance
	 *
	 * @var Emailer
	 */
	private static $instance = null;

	/**
	 * Logger class
	 *
	 * @var \StoboKit\Logger
	 */
	public $logger;

	/**
	 * Schedule logger class
	 *
	 * @var \StoboKit\Schedule_Logger
	 */
	public $scheduler;

	/**
	 * Mail defaults
	 *
	 * @var array
	 */
	private $defaults = array(
		'from_name'    => '',
		'from_email'   => '',
		'content_type' => 'text/html',
		'charset'      => 'UTF-8',
	);

	/**
	 * Mail tags.
	 *
	 * @var array
	 */
	private $mail_tags = array();

	/**
	 * Maximum retry attempts
	 *
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Retry delay multiplier (in minutes)
	 *
	 * @var int
	 */
	private $retry_delay_multiplier = 5;

	/**
	 * Enable daily failed email retry cron
	 *
	 * @var bool
	 */
	private $enable_daily_retry = true;

	/**
	 * Maximum days to consider failed emails for daily retry
	 * Only emails that failed within this many days will be retried
	 *
	 * @var int
	 */
	private $daily_retry_threshold_days = 15;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->logger    = new Logger();
		$this->scheduler = new Schedule_Logger();
		$this->init();
	}

	/**
	 * Instance.
	 *
	 * @return Emailer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init emailer.
	 *
	 * @return void
	 */
	private function init() {
		$from_name  = get_option( 'stobokit_email_from_name', '' );
		$from_email = get_option( 'stobokit_email_from_email', '' );

		$this->defaults['from_email'] = $from_email ? $from_email : get_option( 'admin_email', '' );
		$this->defaults['from_name']  = $from_name ? $from_name : get_option( 'blogname', '' );

		$this->register_default_mail_tags();

		// Hook for individual email sending.
		add_action( 'stobokit_emailer_send_single', array( $this, 'send_scheduled_email' ), 10, 1 );
		add_action( 'wp_mail_failed', array( $this, 'mail_failed' ) );

		// Hook for daily failed email retry.
		add_action( 'stobokit_emailer_daily_retry', array( $this, 'retry_all_failed_emails' ) );

		// Schedule daily retry cron if not already scheduled.
		$this->schedule_daily_retry_cron();
	}

	/**
	 * Handle mail failed event.
	 *
	 * @param WP_Error $error Error object.
	 * @return void
	 */
	public function mail_failed( $error ) {
		if ( is_wp_error( $error ) ) {
			$this->logger->error( $error->get_error_message() );
		}
	}

	/**
	 * Send email immediately
	 *
	 * @param string $to Recipient email.
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @param array  $args Additional arguments.
	 * @return bool True if sent successfully.
	 */
	public function send_now( $to, $subject, $message, $args = array() ) {
		$args = wp_parse_args( $args, $this->defaults );

		$subject     = $this->process_mail_tags( $subject, $args );
		$message     = $this->process_mail_tags( $message, $args );
		$headers     = $this->build_headers( $args );
		$attachments = isset( $args['attachments'] ) ? $args['attachments'] : array();

		$sent = wp_mail( $to, $subject, $message, $headers, $attachments );

		$email_data = array(
			'to'      => $to,
			'subject' => $subject,
			'message' => $message,
			'args'    => $args,
		);

		// Log the send.
		$this->log_email( $email_data, $sent );

		return $sent;
	}

	/**
	 * Schedule single email with individual cron job and optional validation
	 *
	 * @param string   $to Recipient email.
	 * @param string   $subject Email subject.
	 * @param string   $message Email message.
	 * @param int      $send_time Days/Timestamp to wait before sending.
	 * @param array    $args Additional arguments.
	 * @param string   $name Email name for logging.
	 * @param callable $validation_callback Optional validation callback.
	 * @return int|bool Email ID (auto-increment) or false on failure.
	 */
	public function send_later( $to, $subject, $message, $send_time, $args = array(), $name = '', $validation_callback = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';
		$base_time  = time();

		if ( ! Utils::is_timestamp( $send_time ) ) {
			$send_time = $base_time + ( $send_time * DAY_IN_SECONDS );
		}

		// Prepare validation callback for storage.
		$validation_callback_data = null;
		if ( $validation_callback ) {
			if ( is_string( $validation_callback ) || ( is_array( $validation_callback ) && is_string( $validation_callback[0] ) ) ) {
				$validation_callback_data = maybe_serialize( $validation_callback );
			} else {
				error_log( 'Emailer: Validation callback must be a string or array reference, closures cannot be serialized.' );
			}
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Insert into database.
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'sequence_id'         => 'seq_' . Utils::uid(),
				'to_email'            => $to,
				'subject'             => $subject,
				'message'             => $message,
				'args'                => maybe_serialize( $args ),
				'validation_callback' => $validation_callback_data,
				'status'              => 'scheduled',
				'retry_count'         => 0,
				'daily_retry_count'   => 0,
				'max_retries'         => $this->max_retries,
				'scheduled_time'      => gmdate( 'Y-m-d H:i:s', $send_time ),
				'created_at'          => current_time( 'mysql', true ),
				'updated_at'          => current_time( 'mysql', true ),
			),
			array(
				'%s', // sequence_id.
				'%s', // to_email.
				'%s', // subject.
				'%s', // message.
				'%s', // args.
				'%s', // validation_callback.
				'%s', // status.
				'%d', // retry_count.
				'%d', // daily_retry_count.
				'%d', // max_retries.
				'%s', // scheduled_time.
				'%s', // created_at.
				'%s', // updated_at.
			)
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $inserted ) {
			$this->logger->error( 'Failed to insert email into queue: ' . $wpdb->last_error );
			return false;
		}

		// Get the auto-generated ID.
		$id = $wpdb->insert_id;

		// Schedule individual cron job using the auto-increment id.
		wp_schedule_single_event( $send_time, 'stobokit_emailer_send_single', array( $id ) );

		$log_name = ( $name ) ? 'stobokit_emailer_' . $name : 'stobokit_emailer';

		$this->scheduler->insert_log(
			$id,
			$log_name,
			$args,
			$send_time,
			null,
			'scheduled'
		);

		return $id;
	}

	/**
	 * Create follow-up sequence with individual cron jobs and optional validation
	 *
	 * @param string   $to Recipient email.
	 * @param array    $sequence Array of email configurations.
	 * @param array    $args Arguments for mail tags.
	 * @param string   $uid Optional unique identifier.
	 * @param string   $name Sequence name for logging.
	 * @param callable $global_validation_callback Optional global validation callback.
	 * @return string Sequence ID.
	 */
	public function create_followup_sequence( $to, $sequence = array(), $args = array(), $uid = '', $name = '', $global_validation_callback = null ) {
		global $wpdb;

		$table_name  = $wpdb->prefix . 'stobokit_email_queue';
		$sequence_id = 'seq_' . Utils::uid();
		$email_ids   = array();
		$base_time   = time();

		$sequence_id = ! empty( $uid ) ? $uid : $sequence_id;

		// Cancel existing sequence if it exists.
		$this->cancel_sequence( $sequence_id );

		// Prepare global validation callback.
		$global_validation_data = null;
		if ( $global_validation_callback ) {
			if ( is_string( $global_validation_callback ) || ( is_array( $global_validation_callback ) && is_string( $global_validation_callback[0] ) ) ) {
				$global_validation_data = maybe_serialize( $global_validation_callback );
			}
		}

		foreach ( $sequence as $index => $email ) {
			$send_time = false;

			if ( isset( $email['days'] ) ) {
				$send_time = $base_time + ( $email['days'] * DAY_IN_SECONDS );
			} elseif ( isset( $email['timestamp'] ) ) {
				$send_time = $email['timestamp'];
			}

			if ( ! $send_time ) {
				continue;
			}

			// Check if we need to cancel existing email by uid.
			if ( isset( $email['uid'] ) ) {
				$this->cancel_email( $email['uid'] );
			}

			// Determine validation callback (individual or global).
			$validation_callback_data = null;
			if ( isset( $email['validation_callback'] ) ) {
				if ( is_string( $email['validation_callback'] ) || ( is_array( $email['validation_callback'] ) && is_string( $email['validation_callback'][0] ) ) ) {
					$validation_callback_data = maybe_serialize( $email['validation_callback'] );
				}
			} elseif ( $global_validation_data ) {
				$validation_callback_data = $global_validation_data;
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Insert into database.
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'sequence_id'         => $sequence_id,
					'to_email'            => $to,
					'subject'             => $email['subject'],
					'message'             => $email['message'],
					'args'                => maybe_serialize( $args ),
					'validation_callback' => $validation_callback_data,
					'status'              => 'scheduled',
					'retry_count'         => 0,
					'daily_retry_count'   => 0,
					'max_retries'         => $this->max_retries,
					'scheduled_time'      => gmdate( 'Y-m-d H:i:s', $send_time ),
					'created_at'          => current_time( 'mysql', true ),
					'updated_at'          => current_time( 'mysql', true ),
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				)
			);

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			do_action( 'stobokit_email_queue_created', $wpdb->insert_id, $args );

			if ( ! $inserted ) {
				$this->logger->error( 'Failed to insert email into sequence: ' . $wpdb->last_error );
				continue;
			}

			// Schedule individual cron job.
			// Get the auto-generated ID.
			$id = $wpdb->insert_id;

			// Schedule individual cron job using the auto-increment id.
			wp_schedule_single_event( $send_time, 'stobokit_emailer_send_single', array( $id ) );

			do_action( 'stobokit_email_queue_scheduled', $id, $args, $send_time, $id );

			$log_exists = $this->scheduler->get_log_by_uid( $id );
			$log_name   = ( $name ) ? 'stobokit_emailer_' . $name : 'stobokit_emailer';

			if ( ! $log_exists ) {
				$this->scheduler->insert_log(
					$id,
					$log_name,
					$args,
					$send_time,
					null,
					'scheduled'
				);
			} elseif ( $log_exists && $log_exists->id ) {
				$this->scheduler->update_log(
					$log_exists->id,
					array(
						'schedule' => $send_time,
						'status'   => 'scheduled',
					)
				);
			}

			$email_ids[] = $id;
		}

		return $sequence_id;
	}

	/**
	 * Send scheduled email with validation check
	 *
	 * @param int $id Email ID to send.
	 * @return bool True if sent successfully.
	 */
	public function send_scheduled_email( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		// Get email data from database.
		$email_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $email_data ) {
			return false;
		}

		// Unserialize data.
		$args                = maybe_unserialize( $email_data['args'] );
		$validation_callback = maybe_unserialize( $email_data['validation_callback'] );

		$args['has_notes'] = false;

		// Add notes and notes conditional block.
		if ( isset( $email_data['notes'] ) && ! empty( $email_data['notes'] ) ) {
			$args['has_notes'] = true;
			$args['notes']     = $email_data['notes'];
		}

		// Validation callback check.
		if ( isset( $email_data['validation_callback'] ) && $email_data['validation_callback'] ) {

			$callback_data = Utils::process_callback( $validation_callback );

			if ( $callback_data ) {
				try {
					$callback = Utils::reconstruct_callback( $callback_data );
					if ( $callback && is_callable( $callback ) ) {
						$validation_result = ! empty( $args ) ? call_user_func( $callback, $args ) : call_user_func( $callback );

						if ( ! $validation_result ) {
							// Update status to skipped.
							$wpdb->update(
								$table_name,
								array(
									'status'     => 'skipped',
									'updated_at' => current_time( 'mysql', true ),
								),
								array( 'id' => $id ),
								array( '%s', '%s' ),
								array( '%s' )
							);

							$this->scheduler->update_status_by_uid( $id, 'skipped' );
							$this->log_email( $email_data, -1 );

							return false;
						}
					}
				} catch ( Exception $e ) {
					$this->logger->error( 'Validation callback error: ' . $e->getMessage() );
				} catch ( Error $e ) {
					$this->logger->error( 'Validation callback error: ' . $e->getMessage() );
				}
			}
		}

		$log_exists = $this->scheduler->get_log_by_uid( $id );

		// Send the email.
		$sent = $this->send_now(
			$email_data['to_email'],
			$email_data['subject'],
			$email_data['message'],
			$args
		);

		// Handle failed send with retry logic.
		if ( ! $sent ) {
			$retry_count = (int) $email_data['retry_count'];
			$max_retries = (int) $email_data['max_retries'];

			if ( $retry_count < $max_retries ) {
				// Increment retry count.
				$retry_count++;

				// Calculate exponential backoff delay.
				$retry_delay = $this->retry_delay_multiplier * pow( 2, $retry_count - 1 );
				$retry_time  = time() + ( $retry_delay * MINUTE_IN_SECONDS );

				// Update database.
				$wpdb->update(
					$table_name,
					array(
						'status'            => 'retrying',
						'retry_count'       => $retry_count,
						'last_attempt_time' => current_time( 'mysql', true ),
						'scheduled_time'    => gmdate( 'Y-m-d H:i:s', $retry_time ),
						'updated_at'        => current_time( 'mysql', true ),
					),
					array( 'id' => $id ),
					array( '%s', '%d', '%s', '%s', '%s' ),
					array( '%s' )
				);

				// Schedule retry.
				wp_schedule_single_event( $retry_time, 'stobokit_emailer_send_single', array( $id ) );

				if ( $log_exists && $log_exists->id ) {
					$this->scheduler->update_log(
						$log_exists->id,
						array(
							'schedule' => $retry_time,
							'status'   => 'retrying',
						)
					);
				}

				$this->logger->warning(
					sprintf(
						'Email to %s failed. Retry %d/%d scheduled for %s',
						$email_data['to_email'],
						$retry_count,
						$max_retries,
						gmdate( 'Y-m-d H:i:s', $retry_time )
					)
				);

				return false;
			} else {
				// Max retries reached - mark as failed.
				$wpdb->update(
					$table_name,
					array(
						'status'             => 'failed',
						'last_attempt_time'  => current_time( 'mysql', true ),
						'last_error_message' => 'Failed after ' . $max_retries . ' retry attempts',
						'updated_at'         => current_time( 'mysql', true ),
					),
					array( 'id' => $id ),
					array( '%s', '%s', '%s', '%s' ),
					array( '%s' )
				);

				$this->scheduler->update_status_by_uid( $id, 'failed' );

				$email_data['skip_reason'] = sprintf( 'Failed after %d retry attempts', $max_retries );
				$this->log_email( $email_data, 0 );

				$this->logger->error(
					sprintf(
						'Email to %s failed permanently after %d retries',
						$email_data['to_email'],
						$max_retries
					)
				);

				if ( $log_exists && $log_exists->hook_name ) {
					$hook_args = ( $log_exists->args ) ? (array) json_decode( $log_exists->args ) : array();
					do_action( $log_exists->hook_name . '_failed', $hook_args, $email_data );
				}

				return false;
			}
		}

		// Email sent successfully.
		$wpdb->update(
			$table_name,
			array(
				'status'            => 'completed',
				'last_attempt_time' => current_time( 'mysql', true ),
				'updated_at'        => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( $log_exists && $log_exists->hook_name ) {
			$hook_args = ( $log_exists->args ) ? (array) json_decode( $log_exists->args ) : array();
			do_action( $log_exists->hook_name, $hook_args, $sent );
		}

		$this->scheduler->update_status_by_uid( $id, 'completed' );

		return $sent;
	}

	/**
	 * Cancel scheduled email
	 *
	 * @param int $id Email ID to cancel.
	 * @return bool True on success.
	 */
	public function cancel_email( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		// Remove the cron job.
		wp_clear_scheduled_hook( 'stobokit_emailer_send_single', array( $id ) );

		// Update status in database.
		$wpdb->update(
			$table_name,
			array(
				'status'     => 'canceled',
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->scheduler->update_status_by_uid( $id, 'canceled' );

		return true;
	}

	/**
	 * Cancel entire sequence
	 *
	 * @param string $sequence_id Sequence ID to cancel.
	 * @return bool True on success.
	 */
	public function cancel_sequence( $sequence_id = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		// Get all emails in the sequence.
		$email_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE sequence_id = %s",
				$sequence_id
			)
		);

		foreach ( $email_ids as $id ) {
			$this->cancel_email( $id );
		}

		return true;
	}

	/**
	 * Get all pending emails info
	 *
	 * @return array Pending emails.
	 */
	public function get_pending_emails() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$pending = $wpdb->get_results(
			"SELECT 
				id as email_id,
				to_email as `to`,
				subject,
				scheduled_time as send_time,
				CEIL((UNIX_TIMESTAMP(scheduled_time) - UNIX_TIMESTAMP()) / 86400) as days_remaining,
				validation_callback IS NOT NULL as has_validation,
				retry_count,
				status
			FROM {$table_name}
			WHERE status IN ('scheduled', 'retrying')
			ORDER BY scheduled_time ASC",
			ARRAY_A
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $pending ? $pending : array();
	}

	/**
	 * Schedule daily cron for retrying failed emails
	 *
	 * @return void
	 */
	private function schedule_daily_retry_cron() {
		if ( ! $this->enable_daily_retry ) {
			return;
		}

		// Check if cron is already scheduled.
		if ( ! wp_next_scheduled( 'stobokit_emailer_daily_retry' ) ) {
			// Schedule for 2 AM daily.
			$first_run = strtotime( 'tomorrow 2:00 AM' );
			wp_schedule_event( $first_run, 'daily', 'stobokit_emailer_daily_retry' );
		}
	}

	/**
	 * Set the threshold for daily retry in days
	 *
	 * @param int $days Number of days (only failed emails within this period will be retried).
	 * @return void
	 */
	public function set_daily_retry_threshold( $days ) {
		$this->daily_retry_threshold_days = absint( $days );
	}

	/**
	 * Get the current daily retry threshold
	 *
	 * @return int Number of days.
	 */
	public function get_daily_retry_threshold() {
		return $this->daily_retry_threshold_days;
	}

	/**
	 * Get all failed emails from database (within threshold)
	 *
	 * @return array Failed email information.
	 */
	private function get_failed_emails() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$failed_emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					id as email_id,
					to_email,
					subject,
					last_attempt_time,
					retry_count,
					daily_retry_count,
					last_error_message
				FROM {$table_name}
				WHERE status = 'failed'
				AND last_attempt_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
				ORDER BY last_attempt_time DESC",
				$this->daily_retry_threshold_days
			),
			ARRAY_A
		);

		return $failed_emails ? $failed_emails : array();
	}

	/**
	 * Retry all failed emails (runs daily via cron)
	 *
	 * @return array Results of retry operations.
	 */
	public function retry_all_failed_emails() {
		global $wpdb;

		$table_name    = $wpdb->prefix . 'stobokit_email_queue';
		$failed_emails = $this->get_failed_emails();

		$results = array(
			'total'   => count( $failed_emails ),
			'retried' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		if ( empty( $failed_emails ) ) {
			$this->logger->info(
				sprintf(
					'Daily retry: No failed emails to retry (within %d days threshold)',
					$this->daily_retry_threshold_days
				)
			);
			return $results;
		}

		$this->logger->info(
			sprintf(
				'Daily retry: Starting retry for %d failed email(s) within %d days threshold',
				count( $failed_emails ),
				$this->daily_retry_threshold_days
			)
		);

		foreach ( $failed_emails as $failed_email ) {
			$id = $failed_email['email_id'];

			// Check time since last error.
			$last_attempt     = strtotime( $failed_email['last_attempt_time'] );
			$time_since_error = time() - $last_attempt;

			if ( $time_since_error < HOUR_IN_SECONDS ) {
				$results['skipped']++;
				continue;
			}

			// Reset retry count and increment daily retry count.
			$updated = $wpdb->update(
				$table_name,
				array(
					'status'             => 'scheduled',
					'retry_count'        => 0,
					'daily_retry_count'  => $failed_email['daily_retry_count'] + 1,
					'scheduled_time'     => gmdate( 'Y-m-d H:i:s', time() + 60 ),
					'last_error_message' => null,
					'updated_at'         => current_time( 'mysql', true ),
				),
				array( 'email_id' => $email_id ),
				array( '%s', '%d', '%d', '%s', '%s', '%s' ),
				array( '%s' )
			);

			if ( $updated ) {
				// Schedule immediate retry.
				wp_schedule_single_event( time() + 60, 'stobokit_emailer_send_single', array( $id ) );
				$this->scheduler->update_status_by_uid( $id, 'scheduled' );

				$results['retried']++;

				$this->logger->info(
					sprintf(
						'Daily retry: Scheduled retry for email %s (to: %s, daily retry: %d)',
						$email_id,
						$failed_email['to_email'],
						$failed_email['daily_retry_count'] + 1
					)
				);
			} else {
				$results['errors'][] = $id;
			}
		}

		$this->logger->info(
			sprintf(
				'Daily retry completed: %d retried, %d skipped, %d errors (threshold: %d days)',
				$results['retried'],
				$results['skipped'],
				count( $results['errors'] ),
				$this->daily_retry_threshold_days
			)
		);

		return $results;
	}

	/**
	 * Set maximum retry attempts
	 *
	 * @param int $max_retries Maximum number of retry attempts.
	 * @return void
	 */
	public function set_max_retries( $max_retries ) {
		$this->max_retries = absint( $max_retries );
	}

	/**
	 * Set retry delay multiplier
	 *
	 * @param int $minutes Base delay in minutes for retry calculation.
	 * @return void
	 */
	public function set_retry_delay( $minutes ) {
		$this->retry_delay_multiplier = absint( $minutes );
	}

	/**
	 * Get retry information for a specific email
	 *
	 * @param int $id Email ID.
	 * @return array|false Retry information or false if not found.
	 */
	public function get_retry_info( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$email_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT retry_count, daily_retry_count, max_retries, last_attempt_time, last_error_message 
				FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $email_data ) {
			return false;
		}

		return array(
			'retry_count'        => (int) $email_data['retry_count'],
			'daily_retry_count'  => (int) $email_data['daily_retry_count'],
			'max_retries'        => (int) $email_data['max_retries'],
			'last_attempt_time'  => $email_data['last_attempt_time'],
			'last_error_message' => $email_data['last_error_message'],
		);
	}

	/**
	 * Manually retry a failed email
	 *
	 * @param int $id Email ID to retry.
	 * @return bool True if retry was scheduled, false otherwise.
	 */
	public function manual_retry( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$email_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $email_data ) {
			return false;
		}

		// Reset retry count for manual retry.
		$wpdb->update(
			$table_name,
			array(
				'status'             => 'scheduled',
				'retry_count'        => 0,
				'scheduled_time'     => gmdate( 'Y-m-d H:i:s', time() + 60 ),
				'last_error_message' => null,
				'updated_at'         => current_time( 'mysql', true ),
			),
			array( 'email_id' => $email_id ),
			array( '%s', '%d', '%s', '%s', '%s' ),
			array( '%s' )
		);

		// Schedule immediate retry.
		wp_schedule_single_event( time() + 60, 'stobokit_emailer_send_single', array( $id ) );

		$this->scheduler->update_status_by_uid( $id, 'scheduled' );

		return true;
	}

	/**
	 * Enable or disable daily retry cron
	 *
	 * @param bool $enable Whether to enable daily retry.
	 * @return void
	 */
	public function set_daily_retry( $enable ) {
		$this->enable_daily_retry = (bool) $enable;

		if ( $enable ) {
			$this->schedule_daily_retry_cron();
		} else {
			$this->unschedule_daily_retry_cron();
		}
	}

	/**
	 * Unschedule daily retry cron
	 *
	 * @return void
	 */
	private function unschedule_daily_retry_cron() {
		$timestamp = wp_next_scheduled( 'stobokit_emailer_daily_retry' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'stobokit_emailer_daily_retry' );
		}
	}

	/**
	 * Get the next scheduled time for daily retry
	 *
	 * @return string|false Next scheduled time or false if not scheduled.
	 */
	public function get_next_daily_retry() {
		$timestamp = wp_next_scheduled( 'stobokit_emailer_daily_retry' );

		if ( ! $timestamp ) {
			return false;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Get email queue statistics
	 *
	 * @return array Queue statistics.
	 */
	public function get_queue_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$stats = $wpdb->get_results(
			"SELECT 
				status,
				COUNT(*) as count
			FROM {$table_name}
			GROUP BY status",
			ARRAY_A
		);

		$result = array(
			'scheduled' => 0,
			'retrying'  => 0,
			'failed'    => 0,
			'completed' => 0,
			'skipped'   => 0,
			'canceled'  => 0,
			'total'     => 0,
		);

		foreach ( $stats as $stat ) {
			$result[ $stat['status'] ] = (int) $stat['count'];
			$result['total']          += (int) $stat['count'];
		}

		return $result;
	}

	/**
	 * Clean up old completed/canceled emails
	 *
	 * @param int $days_old Delete emails older than this many days.
	 * @return int Number of emails deleted.
	 */
	public function cleanup_old_emails( $days_old = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} 
				WHERE status IN ('completed', 'canceled', 'skipped') 
				AND updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days_old
			)
		);

		if ( $deleted ) {
			$this->logger->info( sprintf( 'Cleaned up %d old emails', $deleted ) );
		}

		return $deleted;
	}

	/**
	 * Get email details by ID
	 *
	 * @param int $id Email ID.
	 * @return array|false Email data or false if not found.
	 */
	public function get_email_by_id( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		$email = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( $email ) {
			$email['args']                = maybe_unserialize( $email['args'] );
			$email['validation_callback'] = maybe_unserialize( $email['validation_callback'] );
		}

		return $email;
	}

	/**
	 * Get emails by sequence ID
	 *
	 * @param string $sequence_id Sequence ID.
	 * @return array Emails in the sequence.
	 */
	public function get_sequence_emails( $sequence_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_queue';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE sequence_id = %s ORDER BY scheduled_time ASC",
				$sequence_id
			),
			ARRAY_A
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $emails ) {
			foreach ( $emails as &$email ) {
				$email['args']                = maybe_unserialize( $email['args'] );
				$email['validation_callback'] = maybe_unserialize( $email['validation_callback'] );
			}
		}

		return $emails ? $emails : array();
	}

	/**
	 * Helper methods
	 */
	private function build_headers( $args ) {
		$headers = array();

		if ( ! empty( $args['content_type'] ) ) {
			$headers[] = 'Content-Type: ' . $args['content_type'] . '; charset=' . $args['charset'];
		}

		if ( ! empty( $args['from_email'] ) ) {
			$from = $args['from_email'];
			if ( ! empty( $args['from_name'] ) ) {
				$from = $args['from_name'] . ' <' . $args['from_email'] . '>';
			}
			$headers[] = 'From: ' . $from;
		}

		return $headers;
	}

	/**
	 * Register default mail tags
	 *
	 * @return void
	 */
	private function register_default_mail_tags() {
		$this->register_shortcode(
			'site_name',
			function ( $args ) {
				return get_bloginfo( 'name' );
			}
		);

		$this->register_shortcode(
			'site_url',
			function ( $args ) {
				return get_site_url();
			}
		);

		$this->register_shortcode(
			'customer_name',
			function ( $args ) {
				$email = isset( $args['email'] ) ? $args['email'] : '';

				if ( $email ) {
					$user = get_user_by( 'email', $email );

					return ( $user ) ? esc_html( ' ' . $user->display_name ) : '';
				}

				return '';
			}
		);

		$this->register_shortcode(
			'notes',
			function ( $args ) {
				return isset( $args['notes'] ) ? wpautop( $args['notes'] ) : '';
			}
		);

		$this->register_shortcode(
			'has_notes',
			function ( $args ) {
				return isset( $args['notes'] ) && ! empty( $args['notes'] ) ? true : false;
			}
		);
	}

	/**
	 * Register a shortcode for email templates
	 *
	 * @param string   $name Shortcode name.
	 * @param callable $callback Callback function.
	 * @return void
	 */
	public function register_shortcode( $name, $callback ) {
		$this->mail_tags[ $name ] = $callback;
	}

	/**
	 * Process mail tags in content
	 *
	 * @param string $content Content with mail tags.
	 * @param array  $args Arguments for mail tags.
	 * @return string Processed content.
	 */
	private function process_mail_tags( $content, $args = array() ) {
		// Process conditional blocks.
		$content = $this->process_conditional_blocks( $content, $args );

		// Process regular mail tags.
		foreach ( $this->mail_tags as $name => $callback ) {
			$pattern = '/\{' . preg_quote( $name, '/' ) . '(?:\:([^}]*))?\}/';

			$content = preg_replace_callback(
				$pattern,
				function () use ( $callback, $args ) {
					return call_user_func( $callback, $args );
				},
				$content
			);
		}
		return $content;
	}

	/**
	 * Process conditional blocks in content
	 *
	 * @param string $content Content with conditional blocks.
	 * @param array  $args Arguments for conditions.
	 * @return string Processed content.
	 */
	private function process_conditional_blocks( $content, $args = array() ) {
		// Pattern to match conditional blocks: {% condition_name %}content{%} or {% !condition_name %}content{%}.
		$pattern = '/\{\%\s*(!?)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\%\}(.*?)\{\%\}/s';
		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $args ) {
				$negation       = $matches[1]; // Will be '!' or empty string.
				$condition_name = trim( $matches[2] );
				$block_content  = $matches[3];

				// Evaluate the condition.
				$condition_met = $this->evaluate_condition( $condition_name, $args );

				// If negation exists, invert the result.
				if ( $negation === '!' ) {
					$condition_met = ! $condition_met;
				}

				// Return content if condition is met, otherwise remove block.
				if ( $condition_met ) {
					return $block_content;
				}

				return '';
			},
			$content
		);
	}

	/**
	 * Evaluate a condition based on args
	 *
	 * @param string $condition_name Condition name.
	 * @param array  $args Arguments to check.
	 * @return bool True if condition is met.
	 */
	private function evaluate_condition( $condition_name, $args = array() ) {

		// Check if condition exists in args and is truthy.
		if ( isset( $args[ $condition_name ] ) ) {
			$value = $args[ $condition_name ];

			// Handle different types of truthy values.
			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( is_string( $value ) ) {
				return ! empty( trim( $value ) );
			}

			if ( is_numeric( $value ) ) {
				return $value > 0;
			}

			if ( is_array( $value ) ) {
				return ! empty( $value );
			}

			return ! empty( $value );
		}

		return false;
	}

	/**
	 * Log email send attempt
	 *
	 * @param array $email_data Email data.
	 * @param mixed $sent Send result.
	 * @return void
	 */
	private function log_email( $email_data, $sent ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_logs';

		$wpdb->insert(
			$table_name,
			array(
				'to_email'    => isset( $email_data['to'] ) ? $email_data['to'] : ( isset( $email_data['to_email'] ) ? $email_data['to_email'] : '' ),
				'subject'     => $email_data['subject'],
				'email_id'    => isset( $email_data['id'] ) ? $email_data['id'] : 0,
				'sent'        => $sent,
				'skip_reason' => isset( $email_data['skip_reason'] ) ? $email_data['skip_reason'] : null,
				'sent_at'     => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Get email logs
	 *
	 * @param int $limit Number of logs to retrieve.
	 * @return array Email logs.
	 */
	public function get_email_logs( $limit = 40 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'stobokit_email_logs';

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY sent_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $logs ? $logs : array();
	}
}
