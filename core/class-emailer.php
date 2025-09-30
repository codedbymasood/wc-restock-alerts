<?php
/**
 * Emailer class with validation callback support.
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
 * Emailer class with validation support.
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
	 * Constructor.
	 */
	private function __construct() {
		$this->logger = new Logger();

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
	}

	public function mail_failed( $error ) {
		if ( is_wp_error( $error ) ) {
			$this->logger->error( $error->get_error_message() );
		}
	}

	/**
	 * Send email immediately
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
	 */
	public function send_later( $to, $subject, $message, $days_later, $args = array(), $name = '', $validation_callback = null ) {
		$base_time = time();
		$send_time = $base_time + ( $days_later * DAY_IN_SECONDS );
		$email_id  = 'email_' . Utils::uid();

		// Create email data.
		$email_data = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'args'        => $args,
			'email_id'    => $email_id,
			'sequence_id' => $email_id,
		);

		if ( $validation_callback ) {
			if ( is_string( $validation_callback ) || ( is_array( $validation_callback ) && is_string( $validation_callback[0] ) ) ) {
				$email_data['validation_callback'] = $validation_callback;
			} else {
				error_log( 'Emailer: Validation callback must be a string or array reference, closures cannot be serialized.' );
			}
		}

		// Store email data.
		update_option( 'stobokit_emailer_data_' . $email_id, $email_data );

		// Schedule individual cron job for this specific email.
		wp_schedule_single_event( $send_time, 'stobokit_emailer_send_single', array( $email_id ) );

		$log_name = ( $name ) ? 'stobokit_emailer_' . $name : 'stobokit_emailer';

		$this->scheduler->insert_log(
			$email_id,
			$log_name,
			$args,
			$send_time,
			null,
			'scheduled'
		);

		return $email_id;
	}

	/**
	 * Create follow-up sequence with individual cron jobs and optional validation
	 */
	public function create_followup_sequence( $to, $sequence = array(), $args = array(), $uid = '', $name = '', $global_validation_callback = null ) {
		$sequence_id = 'seq_' . Utils::uid();
		$email_ids   = array();
		$base_time   = time();

		// Check if sequence has a custom uid.
		$sequence_id = ! empty( $uid ) ? $uid : $sequence_id;

		// Cancel existing sequence if it exists.
		$existing_sequence = get_option( 'stobokit_emailer_sequence_' . $sequence_id, array() );
		if ( ! empty( $existing_sequence ) ) {
			$this->cancel_sequence( $sequence_id );
		}

		foreach ( $sequence as $index => $email ) {
			$send_time = $base_time + ( $email['days'] * DAY_IN_SECONDS );

			// Check if individual email has a uid - if so, cancel that specific email.
			if ( isset( $email['uid'] ) ) {
				$individual_email_id = $email['uid'];

				// Cancel the individual email if it exists.
				$individual_email_data = get_option( 'stobokit_emailer_data_' . $individual_email_id );
				if ( $individual_email_data ) {
					$this->cancel_email( $individual_email_id );
				}

				$email_id = $individual_email_id;
			} else {
				$email_id = $sequence_id . '_' . $index;
			}

			// Create email data.
			$email_data = array(
				'to'          => $to,
				'subject'     => $email['subject'],
				'message'     => $email['message'],
				'args'        => $args,
				'email_id'    => $email_id,
				'sequence_id' => $sequence_id,
			);

			if ( isset( $email['validation_callback'] ) ) {
				if ( is_string( $email['validation_callback'] ) || ( is_array( $email['validation_callback'] ) && is_string( $email['validation_callback'][0] ) ) ) {
					$email_data['validation_callback'] = $email['validation_callback'];
				}
			} elseif ( $global_validation_callback ) {
				if ( is_string( $global_validation_callback ) || ( is_array( $global_validation_callback ) && is_string( $global_validation_callback[0] ) ) ) {
					$email_data['validation_callback'] = $global_validation_callback;
				}
			}

			// Store email data.
			update_option( 'stobokit_emailer_data_' . $email_id, $email_data );

			// Schedule individual cron job.
			wp_schedule_single_event( $send_time, 'stobokit_emailer_send_single', array( $email_id ) );

			$log_exists = $this->scheduler->get_log_by_uid( $email_id );

			$log_name = ( $name ) ? 'stobokit_emailer_' . $name : 'stobokit_emailer';

			if ( ! $log_exists ) {
				$this->scheduler->insert_log(
					$email_id,
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

			$email_ids[] = $email_id;
		}

		// Store sequence info.
		update_option( 'stobokit_emailer_sequence_' . $sequence_id, $email_ids );

		return $sequence_id;
	}

	/**
	 * Send scheduled email with validation check
	 */
	public function send_scheduled_email( $email_id ) {
		// Get email data.
		$email_data = get_option( 'stobokit_emailer_data_' . $email_id );

		if ( ! $email_data ) {
			return false;
		}

		if ( isset( $email_data['validation_callback'] ) && $email_data['validation_callback'] ) {
			// Process the callback for storage.
			$callback_data = Utils::process_callback( $email_data['validation_callback'] );
			if ( ! $callback_data ) {
				return false;
			}

			try {
				$callback = Utils::reconstruct_callback( $callback_data );

				if ( $callback && is_callable( $callback ) ) {
					if ( ! empty( $email_data['args'] ) ) {
						$validation_result = call_user_func( $callback, $email_data, $email_data['args'] );
					} else {
						$validation_result = call_user_func( $callback );
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

			if ( ! $validation_result ) {
				$this->scheduler->update_status_by_uid( $email_id, 'skipped' );

				$this->log_email( $email_data, -1 );

				// Clean up - delete the email data.
				delete_option( 'stobokit_emailer_data_' . $email_id );

				return false;
			}
		}

		$log_exists = $this->scheduler->get_log_by_uid( $email_id );

		// Send the email.
		$sent = $this->send_now(
			$email_data['to'],
			$email_data['subject'],
			$email_data['message'],
			$email_data['args']
		);

		if ( $log_exists && $log_exists->hook_name ) {
			$args = ( $log_exists->args ) ? (array) json_decode( $log_exists->args ) : array();
			do_action( $log_exists->hook_name, $args, $sent );
		}

		$this->scheduler->update_status_by_uid( $email_id, 'completed' );

		// Clean up - delete the email data.
		delete_option( 'stobokit_emailer_data_' . $email_id );

		return $sent;
	}

	/**
	 * Cancel scheduled email
	 */
	public function cancel_email( $email_id ) {
		// Remove the cron job.
		wp_clear_scheduled_hook( 'stobokit_emailer_send_single', array( $email_id ) );

		$this->scheduler->update_status_by_uid( $email_id, 'canceled' );

		// Remove email data.
		delete_option( 'stobokit_emailer_data_' . $email_id );

		return true;
	}

	/**
	 * Cancel entire sequence
	 */
	public function cancel_sequence( $sequence_id = '' ) {
		$email_ids = get_option( 'stobokit_emailer_sequence_' . $sequence_id, array() );

		foreach ( $email_ids as $email_id ) {
			$this->cancel_email( $email_id );
			$this->scheduler->delete_log_by_uid( $email_id );
		}

		// Remove sequence info.
		delete_option( 'stobokit_emailer_sequence_' . $sequence_id );

		return true;
	}

	/**
	 * Get all pending emails info
	 */
	public function get_pending_emails() {
		global $wpdb;

		// Get all scheduled single events for our action.
		$crons   = _get_cron_array();
		$pending = array();

		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron['stobokit_emailer_send_single'] ) ) {
				foreach ( $cron['stobokit_emailer_send_single'] as $job ) {
					$email_id   = $job['args'][0];
					$email_data = get_option( 'stobokit_emailer_data_' . $email_id );

					if ( $email_data ) {
						$pending[] = array(
							'email_id'       => $email_id,
							'to'             => $email_data['to'],
							'subject'        => $email_data['subject'],
							'send_time'      => gmdate( 'Y-m-d H:i:s', $timestamp ),
							'days_remaining' => ceil( ( $timestamp - time() ) / DAY_IN_SECONDS ),
							'has_validation' => isset( $email_data['validation_callback'] ),
						);
					}
				}
			}
		}

		return $pending;
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
	}

	public function register_shortcode( $name, $callback ) {
		$this->mail_tags[ $name ] = $callback;
	}

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

	private function process_conditional_blocks( $content, $args = array() ) {
		// Pattern to match conditional blocks: {% condition_name %}content{%}.
		$pattern = '/\{\%\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\%\}(.*?)\{\%\}/s';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $args ) {
				$condition_name = trim( $matches[1] );
				$block_content  = $matches[2];

				// Check if condition is met.
				if ( $this->evaluate_condition( $condition_name, $args ) ) {
					return $block_content;
				}

				return ''; // Remove block if condition not met.
			},
			$content
		);
	}

	/**
	 * Evaluate a condition based on args
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
	}

	private function log_email( $email_data, $sent ) {
		$logs = get_option( 'stobokit_emailer_logs', array() );

		$log_entry = array(
			'to'       => $email_data['to'],
			'subject'  => $email_data['subject'],
			'email_id' => isset( $email_data['email_id'] ) ? $email_data['email_id'] : '',
			'sent'     => $sent,
			'sent_at'  => current_time( 'mysql' ),
		);

		// Add skip reason if provided.
		if ( isset( $email_data['skip_reason'] ) ) {
			$log_entry['skip_reason'] = $email_data['skip_reason'];
		}

		$logs[] = $log_entry;

		// Keep only last 100 logs.
		if ( count( $logs ) > 40 ) {
			$logs = array_slice( $logs, -40 );
		}

		update_option( 'stobokit_emailer_logs', $logs );
	}
}
