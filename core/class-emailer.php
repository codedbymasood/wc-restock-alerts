<?php
/**
 * Emailer class.
 *
 * Usage Examples:
 * ==============
 * // See what's scheduled
 * $pending = $emailer->get_pending_emails();

 * // Cancel specific email
 * $emailer->cancel_email($email_id);

 * // Cancel entire sequence
 * $emailer->cancel_sequence($sequence_id);
 *
 * // Create a follow up sequence
 * $sequence_id = $emailer->create_followup_sequence(
 *   $customer_email,
 *   array(
 *     array('days' => 1, 'subject' => 'Thank you!', 'message' => '...'),
 *     array('days' => 3, 'subject' => 'How is it?', 'message' => '...'),
 *     array('days' => 7, 'subject' => 'Review?', 'message' => '...'),
 *     array('days' => 14, 'subject' => 'Special offer', 'message' => '...')
 *   ),
 *   array('customer_name' => $customer_name, 'order_id' => $order_id)
 * );
 *
 * // Thank you email in 1 day
 * $emailer->send_later(
 *   $customer_email,
 *   'Thanks for your purchase!',
 *   '<h1>Thank you {customer_name}!</h1>',
 *   1,
 *   array('customer_name' => $customer_name)
 * );
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Emailer class.
 */
class Emailer {

	/**
	 * Instance
	 *
	 * @var Emailer
	 */
	private static $instance = null;

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
	 * Schedule single email with individual cron job
	 */
	public function send_later( $to, $subject, $message, $days_later, $args = array(), $name = '' ) {
		$base_time   = time();
		// $send_time = $base_time + ( $days_later * DAY_IN_SECONDS );
		$send_time = $base_time + ( 2 * MINUTE_IN_SECONDS );
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
	 * Create follow-up sequence with individual cron jobs
	 */
	public function create_followup_sequence( $to, $sequence = array(), $args = array(), $uid = '', $name = '' ) {
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
			// $send_time = $base_time + ( $email['days'] * DAY_IN_SECONDS );
			$send_time = $base_time + ( ( $index + 1 ) * 2 * MINUTE_IN_SECONDS );

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
	 * Send scheduled email (called by individual cron job)
	 */
	public function send_scheduled_email( $email_id ) {
		// Get email data.
		$email_data = get_option( 'stobokit_emailer_data_' . $email_id );

		if ( ! $email_data ) {
			return false;
		}

		// Send the email.
		$sent = $this->send_now(
			$email_data['to'],
			$email_data['subject'],
			$email_data['message'],
			$email_data['args']
		);

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

		// update log to cancelled.

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

	private function log_email( $email_data, $sent ) {
		$logs = get_option( 'stobokit_emailer_logs', array() );

		$logs[] = array(
			'to'       => $email_data['to'],
			'subject'  => $email_data['subject'],
			'email_id' => isset( $email_data['email_id'] ) ? $email_data['email_id'] : '',
			'sent'     => $sent,
			'sent_at'  => current_time( 'mysql' ),
		);

		// Keep only last 100 logs.
		if ( count( $logs ) > 40 ) {
			$logs = array_slice( $logs, -40 );
		}

		update_option( 'stobokit_emailer_logs', $logs );
	}
}
