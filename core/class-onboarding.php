<?php
/**
 * Onboarding class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\STOBOKIT\Onboarding' ) ) {
	return;
}

/**
 * Onboarding class.
 */
class Onboarding {
	/**
	 * Current step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Steps list.
	 *
	 * @var array
	 */
	protected $steps = array();

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Textdomain for translations.
	 *
	 * @var string
	 */
	protected $textdomain;

	/**
	 * Redirect page slug after onboarding.
	 *
	 * @var string
	 */
	protected $redirect_page;

	/**
	 * Onboarding page slug.
	 *
	 * @var string
	 */
	protected $page_slug;

	/**
	 * Option/transient prefix.
	 *
	 * @var string
	 */
	protected $option_prefix;

	/**
	 * Constructor.
	 *
	 * @param array $args Config array.
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'path'          => '',
			'plugin_slug'   => '',
			'textdomain'    => '',
			'steps'         => array(),
			'page_slug'     => 'text-domain',
			'option_prefix' => 'text-domain',
		);

		$args = wp_parse_args( $args, $defaults );

		$this->path          = $args['path'];
		$this->plugin_slug   = $args['plugin_slug'];
		$this->steps         = $args['steps'];
		$this->redirect_page = admin_url( 'admin.php?page=stobokit-dashboard' );
		$this->page_slug     = $args['page_slug'];
		$this->option_prefix = $args['option_prefix'];

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_onboarding_page' ) );
		add_action( 'admin_init', array( $this, 'set_current_step' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ), 1 );
		add_action( 'admin_init', array( $this, 'handle_onboarding_completion' ) );
	}

	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( 'metabox', STOBOKIT_URL . '/assets/css/onboarding.css', array(), '1.0' );
	}

	/**
	 * Register Onboarding Page.
	 */
	public function register_onboarding_page() {
		add_submenu_page(
			'stobokit',
			esc_html__( 'Onboarding', 'text-domain' ),
			esc_html__( 'Onboarding', 'text-domain' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Set current step.
	 */
	public function set_current_step() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested_step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : '';

		// Only allow valid steps, default to first step if invalid.
		$this->step = array_key_exists( $requested_step, $this->steps ) ? $requested_step : key( $this->steps );
	}

	/**
	 * Check if step file exists.
	 *
	 * @param string $step Step identifier.
	 * @return bool
	 */
	protected function step_file_exists( $step ) {

		$step_file = $this->path . 'onboarding/step-' . $step . '.php';
		return file_exists( $step_file );
	}

	/**
	 * Get step file path.
	 *
	 * @param string $step Step identifier.
	 * @return string
	 */
	protected function get_step_file_path( $step ) {
		return $this->path . '/onboarding/step-' . $step . '.php';
	}

	/**
	 * Render step navigation.
	 */
	protected function render_step_navigation() {
		?>
		<ol class="stobokit-steps">
			<?php foreach ( $this->steps as $step_id => $label ) : ?>
				<li class="stobokit-step <?php echo $this->step === $step_id ? 'stobokit-step--active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Render step content.
	 */
	protected function render_step_content() {
		?>
		<div class="stobokit-step-content">
			<?php
			if ( $this->step_file_exists( $this->step ) ) {
				$plugin_slug = $this->plugin_slug;
				include_once $this->get_step_file_path( $this->step );
			} else {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Step file missing. Please contact support.', 'text-domain' ); ?></p>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render step actions (navigation buttons).
	 */
	protected function render_step_actions() {
		$step_keys   = array_keys( $this->steps );
		$current_pos = array_search( $this->step, $step_keys, true );
		?>
		<div class="stobokit-step-actions">
			<?php if ( $current_pos > 0 ) : ?>
				<?php $prev_step = $step_keys[ $current_pos - 1 ]; ?>
				<a class="btn stobokit-button--prev" href="<?php echo esc_url( add_query_arg( 'step', $prev_step ) ); ?>">
					&larr; <?php esc_html_e( 'Back', 'text-domain' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $current_pos < count( $step_keys ) - 1 ) : ?>
				<?php $next_step = $step_keys[ $current_pos + 1 ]; ?>
				<a class="btn stobokit-button--next" href="<?php echo esc_url( add_query_arg( 'step', $next_step ) ); ?>">
					<?php esc_html_e( 'Continue', 'text-domain' ); ?> &rarr;
				</a>
			<?php else : ?>
				<a class="btn stobokit-button--finish" href="<?php echo esc_url( $this->redirect_page . '&onboarding=complete' ); ?>">
					<?php esc_html_e( 'Finish', 'text-domain' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get current step progress percentage.
	 *
	 * @return int
	 */
	public function get_progress_percentage() {
		$step_keys   = array_keys( $this->steps );
		$current_pos = array_search( $this->step, $step_keys, true );
		$total_steps = count( $step_keys );

		return $total_steps > 0 ? round( ( ( $current_pos + 1 ) / $total_steps ) * 100 ) : 0;
	}

	/**
	 * Render progress bar.
	 */
	protected function render_progress_bar() {
		$progress = $this->get_progress_percentage();
		?>
		<div class="stobokit-progress-bar-wrapper">
			<div class="stobokit-progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
		</div>
		<?php
	}

	/**
	 * Render the onboarding page.
	 */
	public function render_page() {
		?>
		<div class="stobokit-wrapper stobokit-onboarding">
			<h1><?php esc_html_e( 'Getting Things Ready', 'text-domain' ); ?></h1>
			<?php $this->render_progress_bar(); ?>
			<?php $this->render_step_navigation(); ?>
			<?php $this->render_step_content(); ?>
			<?php $this->render_step_actions(); ?>
		</div>
		<?php
	}

	/**
	 * Get current step.
	 *
	 * @return string
	 */
	public function get_current_step() {
		return $this->step;
	}

	/**
	 * Get all steps.
	 *
	 * @return array
	 */
	public function get_all_steps() {
		return $this->steps;
	}

	/**
	 * Check if current step is the last step.
	 *
	 * @return bool
	 */
	public function is_last_step() {
		$step_keys   = array_keys( $this->steps );
		$current_pos = array_search( $this->step, $step_keys, true );

		return ( count( $step_keys ) - 1 ) === $current_pos;
	}

	/**
	 * Check if current step is the first step.
	 *
	 * @return bool
	 */
	public function is_first_step() {
		$step_keys   = array_keys( $this->steps );
		$current_pos = array_search( $this->step, $step_keys, true );

		return 0 === $current_pos;
	}

	/**
	 * Check if onboarding should be triggered and redirect.
	 */
	public function maybe_redirect_to_onboarding() {
		if ( ! get_transient( $this->option_prefix . '_activation_redirect' ) ) {
			return;
		}

		delete_transient( $this->option_prefix . '_activation_redirect' );

		if ( wp_doing_ajax() || wp_doing_cron() || headers_sent() ) {
			return;
		}

		if ( $this->is_onboarding_completed() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
		exit;
	}

	/**
	 * Check if onboarding has been completed.
	 *
	 * @return bool
	 */
	public function is_onboarding_completed() {
		return (bool) get_option( $this->option_prefix . '_completed', false );
	}

	/**
	 * Reset onboarding.
	 */
	public function reset_onboarding() {
		delete_option( $this->option_prefix . '_completed' );
		delete_option( $this->option_prefix . '_completed_date' );
		delete_option( $this->option_prefix . '_current_step' );
		delete_transient( $this->option_prefix . '_activation_redirect' );
	}

	/**
	 * Handle onboarding completion.
	 */
	public function handle_onboarding_completion() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['onboarding'] ) && 'complete' === sanitize_key( $_GET['onboarding'] ) ) {
			$this->complete_onboarding();
			wp_safe_redirect( $this->redirect_page );
			exit;
		}
	}

	/**
	 * Mark onboarding as completed.
	 */
	public function complete_onboarding() {
		update_option( $this->option_prefix . '_completed', true );
		update_option( $this->option_prefix . '_completed_date', current_time( 'timestamp' ) );
		delete_option( $this->option_prefix . '_current_step' );
		delete_transient( $this->option_prefix . '_activation_redirect' );
	}
}
