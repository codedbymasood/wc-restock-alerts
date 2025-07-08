<?php
/**
 * Settings class.
 *
 * @package product-availability-notifier-for-woocommerce\admin\
 * @author Masood Mohamed <iam.masoodmohd@gmail.com>
 * @version 1.0
 */

namespace PAW;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Settings {

	/**
	 * Parent slug.
	 *
	 * @var string
	 */
	private $parent_slug;

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	private $menu_slug;

	/**
	 * Page title.
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	private $menu_title;

	/**
	 * Capability.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * Setting fields.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Plugin constructor.
	 *
	 * @param string $parent_slug Parent slug.
	 * @param string $menu_slug Menu slug.
	 * @param string $page_title Page title.
	 * @param string $menu_title Menu title.
	 * @param string $capability Capability.
	 * @param array  $fields Setting fields.
	 */
	public function __construct( $parent_slug, $menu_slug, $page_title, $menu_title, $capability, $fields ) {
		$this->parent_slug = $parent_slug;
		$this->menu_slug   = $menu_slug;
		$this->page_title  = $page_title;
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->fields      = $fields;

		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add menu page hook callback.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			array( $this, 'render_settings_page' )
		);
	}
	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook Menu hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		// Dynamic hook detection based on parent and menu slug.
		$expected_hook = $this->parent_slug . '_page_' . $this->menu_slug;

		// Handle different parent menu types.
		if ( 'options-general.php' === $this->parent_slug ) {
			$expected_hook = 'settings_page_' . $this->menu_slug;
		}

		if ( $expected_hook !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'settings-style', PAW_URL . 'admin/assets/css/settings.css', array(), '1.0' );
		wp_enqueue_script( 'settings-script', PAW_URL . 'admin/assets/js/settings.js', array( 'jquery' ), '1.0', true );
	}

	/**
	 * Save settings callback.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! isset( $_POST['paw_settings_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['paw_settings_nonce'] ), 'paw_settings_action' ) ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		foreach ( $this->fields as $tab_fields ) {
			foreach ( $tab_fields as $field ) {
				$id    = $field['id'];
				$type  = isset( $field['type'] ) ? $field['type'] : 'text';
				$value = isset( $_POST[ $id ] ) ? wp_unslash( $_POST[ $id ] ) : '';

				switch ( $type ) {
					case 'checkbox':
					case 'switch':
						update_option( $id, '1' === $value ? '1' : '' );
						break;
					case 'color':
						update_option( $id, sanitize_hex_color( $value ) );
						break;
					case 'textarea':
						update_option( $id, sanitize_textarea_field( $value ) );
						break;
					default:
						update_option( $id, sanitize_text_field( $value ) );
						break;
				}
			}
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		}
		exit;
	}

	/**
	 * Setting page content.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$tabs = array_keys( $this->fields );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>	
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Settings saved successfully!</p>
				</div>
			<?php endif; ?>	
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $i => $tab ) : ?>
					<a href="#" class="nav-tab<?php echo 0 === $i ? ' nav-tab-active' : ''; ?>" data-tab="tab-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $tab ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form method="post">
				<?php wp_nonce_field( 'paw_settings_action', 'paw_settings_nonce' ); ?>
				<?php foreach ( $tabs as $i => $tab ) : ?>
					<div class="tab-content tab-<?php echo esc_attr( $i ); ?>"<?php echo 0 === $i ? '' : ' style="display:none"'; ?>>
						<?php foreach ( $this->fields[ $tab ] as $field ) : ?>
							<?php $this->render_field( $field ); ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Field content.
	 *
	 * @param array $field Setting field.
	 * @return void
	 */
	private function render_field( $field ) {
		$id    = $field['id'];
		$name  = $id;
		$value = get_option( $id, '' );
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		$label = isset( $field['label'] ) ? $field['label'] : '';
		?>
		<div class="field-wrap field-<?php echo esc_attr( $type ); ?>">
			<?php if ( $label ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php endif; ?>

			<?php
			switch ( $type ) {
				case 'textarea':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" cols="50">' . esc_textarea( $value ) . '</textarea>';
					break;

				case 'select':
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( $field['options'] as $opt_val => $opt_label ) {
						$selected = selected( $value, $opt_val, false );
						echo '<option value="' . esc_attr( $opt_val ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
					}
					echo '</select>';
					break;

				case 'radio':
					foreach ( $field['options'] as $opt_val => $opt_label ) {
						$checked = checked( $value, $opt_val, false );
						echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opt_val ) . '"' . $checked . '> ' . esc_html( $opt_label ) . '</label><br>';
					}
					break;

				case 'checkbox':
					echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, '1', false ) . '>';
					break;

				case 'switch':
					echo '<label class="switch">';
					echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, '1', false ) . '>';
					echo '<span class="slider round"></span></label>';
					break;

				case 'color':
					echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="color-picker">';
					break;

				case 'text':
				default:
					echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
					break;
			}
			?>
		</div>
		<?php
	}
}
