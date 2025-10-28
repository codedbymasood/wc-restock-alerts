<?php
/**
 * License class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Add deactivation feedback form
 */
class Plugin_Deactivation_Feedback {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'deactivation_feedback_form' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_stobokit_plugin_deactivation_feedback', array( $this, 'handle_feedback' ) );
	}

	/**
	 * Display the feedback modal
	 */
	public function deactivation_feedback_form() {
		$screen = get_current_screen();
		if ( 'plugins' !== $screen->id ) {
			return;
		}
		?>
		<div class="hidden stobokit-wrapper" id="plugin-deactivation-modal">
			<div class="plugin-deactivation-overlay"></div>
			<div class="plugin-deactivation-content">
					<h2><?php esc_html_e( 'Quick Feedback', 'plugin-slug' ); ?></h2>
					<p><?php esc_html_e( 'If you have a moment, please share why you\'re deactivating the plugin:', 'plugin-slug' ); ?></p>
					
					<form id="plugin-deactivation-form">
						<ul class="deactivation-reasons">
							<li>
								<label>
									<input type="radio" name="reason" value="temporary_deactivation" />
									<span><?php esc_html_e( 'It\'s a temporary deactivation', 'plugin-slug' ); ?></span>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="reason" value="stopped_working" />
									<span><?php esc_html_e( 'The plugin stopped working', 'plugin-slug' ); ?></span>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="reason" value="found_better_plugin" />
									<span><?php esc_html_e( 'I found a better plugin', 'plugin-slug' ); ?></span>
								</label>
								<input type="text" class="hidden reason-detail" placeholder="Which plugin?" />
							</li>
							<li>
								<label>
									<input type="radio" name="reason" value="lacks_feature" />
									<span><?php esc_html_e( 'The plugin lacks a feature I need', 'plugin-slug' ); ?></span>
								</label>
								<input type="text" class="hidden reason-detail" placeholder="What feature?" />
							</li>
							<li>
								<label>
									<input type="radio" name="reason" value="difficult_to_use" />
									<span><?php esc_html_e( 'The plugin is difficult to use', 'plugin-slug' ); ?></span>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="reason" value="other" />
									<span><?php esc_html_e( 'Other reason', 'plugin-slug' ); ?></span>
								</label>
								<textarea class="hidden reason-detail" placeholder="Please share your reason..." ></textarea>
							</li>
						</ul>
						<input type="hidden" name="plugin" />
						<div class="deactivation-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Submit & Deactivate', 'plugin-slug' ); ?></button>
							<button type="button" class="button button-tertiary" id="skip-deactivation"><?php esc_html_e( 'Skip & Deactivate', 'plugin-slug' ); ?></button>
						</div>
					</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'plugins.php' !== $hook ) {
			return;
		}

		wp_localize_script(
			'jquery',
			'stobokit',
			array(
				'plugins' => apply_filters( 'stobokit_plugins', array() ),
				'nonce'   => wp_create_nonce( 'deactivation_feedback' ),
			)
		);

		wp_enqueue_script( 'stobokit-feedback', STOBOKIT_URL . '/assets/js/feedback.js', array( 'jquery' ), '1.0', true );
	}

	/**
	 * Handle the feedback submission
	 */
	public function handle_feedback() {
		check_ajax_referer( 'deactivation_feedback', 'nonce' );

		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'other';
		$detail = isset( $_POST['detail'] ) ? sanitize_text_field( wp_unslash( $_POST['detail'] ) ) : '';
		$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';

		$hubspot_sent = $this->send_to_hubspot(
			array(
				'reason'        => $reason,
				'reason_detail' => $detail,
				'plugin'        => $plugin,
			)
		);

		if ( $hubspot_sent ) {
			wp_send_json_success(
				array(
					'message' => esc_html__( 'Feedback sent successfully', 'plugin-slug' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to send', 'plugin-slug' ),
				)
			);
		}

		die();
	}

	/**
	 * Send data to HubSpot form
	 */
	private function send_to_hubspot( $data ) {
		$hubspot_url = sprintf(
			'https://api.hsforms.com/submissions/v3/integration/submit/%s/%s',
			'244093007',
			'29cb7127-b0c0-40f4-aeaf-06c876f8d354'
		);

		$fields = array(
			array(
				'name'  => 'reason',
				'value' => $data['reason'],
			),
			array(
				'name'  => 'reason_detail',
				'value' => $data['reason_detail'],
			),
			array(
				'name'  => 'plugin',
				'value' => $data['plugin'],
			),
		);

		$body = array(
			'fields'  => $fields,
			'context' => array(
				'pageUri'  => get_site_url(),
				'pageName' => 'Visit Site',
			),
		);

		$response = wp_remote_post(
			$hubspot_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'HubSpot API Error', 'plugin-slug' ),
				)
			);
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Success codes: 200 or 204.
		if ( 200 === $response_code || 204 === $response_code ) {
			return true;
		}

		return false;
	}
}

// Initialize.
new Plugin_Deactivation_Feedback();