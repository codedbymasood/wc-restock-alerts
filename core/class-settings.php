<?php
/**
 * Settings class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

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
	private $direct;

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
	 * Menu icon.
	 *
	 * @var string
	 */
	private $icon;

	/**
	 * Setting fields.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	private $nonce_name;

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private $nonce_action;

	/**
	 * Pro status.
	 *
	 * @var boolean
	 */
	private $pro;

	/**
	 * Plugin constructor.
	 *
	 * @param string  $plugin_slug Plugin_slug.
	 * @param string  $parent_slug Parent slug.
	 * @param string  $menu_slug Menu slug.
	 * @param string  $page_title Page title.
	 * @param string  $menu_title Menu title.
	 * @param string  $capability Capability.
	 * @param string  $icon Menu icon.
	 * @param boolean $direct Load directly or separated.
	 * @param array   $fields Setting fields.
	 */
	public function __construct( $plugin_slug, $parent_slug, $menu_slug, $page_title, $menu_title, $capability, $icon, $direct, $fields ) {
		$this->plugin_slug = $plugin_slug;
		$this->parent_slug = $parent_slug;
		$this->menu_slug   = $menu_slug;
		$this->page_title  = $page_title;
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->icon        = $icon;
		$this->direct      = $direct;
		$this->fields      = $fields;

		// Make nonce unique per page.
		$this->nonce_name   = $menu_slug . '_nonce';
		$this->nonce_action = $menu_slug . '_action';

		// Check if pro version is active.
		$this->pro = apply_filters( $plugin_slug . '_is_pro_active', false );

		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10 );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
	}

	/**
	 * Add menu page hook callback.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		if ( $this->direct ) {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->menu_slug,
				array( $this, 'render_settings_page' ),
				$this->icon,
				50
			);
		} else {
			add_submenu_page(
				$this->parent_slug,
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->menu_slug,
				array( $this, 'render_settings_page' )
			);
		}
	}

	/**
	 * Save settings callback.
	 *
	 * @return void
	 */
	public function save_settings() {
		// Check if we're on the current page.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug ) {
			return;
		}

		if ( ! isset( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->nonce_name ] ) ), $this->nonce_action ) ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		foreach ( $this->fields as $tab_fields ) {
			foreach ( $tab_fields as $field ) {
				$id     = $field['id'];
				$type   = isset( $field['type'] ) ? $field['type'] : 'text';
				$is_pro = isset( $field['pro'] ) ? $field['pro'] : false;

				// Skip saving pro fields if pro is not active.
				if ( $is_pro && ! $this->pro ) {
					continue;
				}

				if ( 'richtext_editor' === $type ) {
					if ( isset( $_POST[ $id ] ) && is_array( $_POST[ $id ] ) ) {
							$raw_value = sanitize_text_field( wp_unslash( $_POST[ $id ] ) );

							$value = array(
								'html' => isset( $raw_value['html'] )
									? wp_kses_post( $raw_value['html'] )
									: '',
								'css'  => isset( $raw_value['css'] )
									? sanitize_textarea_field( $raw_value['css'] )
									: '',
							);
					} else {
						$value = isset( $field['default'] )
							? $field['default']
							: array(
								'html' => '',
								'css'  => '',
							);
					}
				} else {
					$value = isset( $_POST[ $id ] )
						? sanitize_text_field( wp_unslash( $_POST[ $id ] ) )
						: ( isset( $field['default'] ) ? sanitize_text_field( wp_unslash( $field['default'] ) ) : '' );
				}

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
					case 'richtext_editor':
						update_option( $id, $value );
						break;
					default:
						update_option( $id, sanitize_text_field( $value ) );
						break;
				}
			}
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
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

		$admin_url = admin_url( 'admin.php?page=' . $this->menu_slug );

		$current_tab = isset( $_GET['tab'] ) && ! empty( isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : Utils::convert_case( $tabs[0] );
		?>
		<div class="stobokit-wrapper wrap">
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="stobokit-notice">
					<p><?php esc_html_e( 'Settings saved successfully!', 'store-boost-kit' ); ?></p>
				</div>
			<?php endif; ?>	

			<h1><?php echo esc_html( $this->page_title ); ?></h1>	
			<div class="nav-tab-wrapper horizontal">
				<div class="nav-tabs">
					<?php
					foreach ( $tabs as $i => $tab ) :
						$tab_key = Utils::convert_case( $tab );
						?>
						<a href="<?php echo esc_url( $admin_url . '&tab=' . $tab_key ); ?>" class="nav-tab<?php echo $tab_key === $current_tab ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $tab ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="nav-tab-content-wrapper">
					<form method="post">
						<?php wp_nonce_field( $this->nonce_action, $this->nonce_name ); ?>
						<?php
						foreach ( $tabs as $i => $tab ) :
							$tab_key = Utils::convert_case( $tab );
							?>
							<div class="nav-tab-content tab-<?php echo esc_attr( $i ); ?> <?php echo $tab_key === $current_tab ? 'active' : ''; ?>">
								<?php foreach ( $this->fields[ $tab ] as $field ) : ?>
									<?php $this->render_field( $field ); ?>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
						<?php submit_button( esc_html__( 'Save Settings', 'store-boost-kit' ) ); ?>
					</form>
				</div>
			</div>
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
		$id    = isset( $field['id'] ) ? $field['id'] : '';
		$name  = $id;
		$value = get_option( $id, '' );

		if ( isset( $field['default'] ) && empty( $value ) ) {
			$value = $field['default'];
		}

		$title       = isset( $field['title'] ) ? $field['title'] : '';
		$type        = isset( $field['type'] ) ? $field['type'] : 'text';
		$label       = isset( $field['label'] ) ? $field['label'] : '';
		$description = isset( $field['description'] ) ? $field['description'] : '';
		$is_pro      = isset( $field['pro'] ) ? $field['pro'] : false;

		if ( 'richtext_editor' === $type ) {
			$default_editor = isset( $field['default_editor'] ) ? $field['default_editor'] : 'html';
			$html_value     = isset( $value['html'] ) ? $value['html'] : '';
			$css_value      = isset( $value['css'] ) ? $value['css'] : '';
		}
		?>

		<?php if ( 'group_start' !== $type && 'group_end' !== $type ) { ?>
			<div class="field-wrap field-<?php echo esc_attr( $type ); ?><?php echo $is_pro ? ' field-pro' : ''; ?>">
				<?php if ( $label ) : ?>
					<label for="<?php echo esc_attr( $id ); ?>">
						<?php echo esc_html( $label ); ?>
						<?php if ( $is_pro && ! $this->pro ) : ?>
							<span class="pro-tag">PRO</span>
						<?php endif; ?>
					</label>
				<?php endif; ?>
		<?php } ?>
			<?php
			switch ( $type ) {
				case 'group_start':
					echo '<div class="field-group">';
					echo '<p class="field-title">' . esc_html( $title ) . '</p>';
					echo '<div class="field-content">';
					break;
				case 'group_end':
					echo '</div>';
					echo '</div>';
					break;
				case 'textarea':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" cols="50"' . esc_attr( $disabled ) . '>' . esc_textarea( $value ) . '</textarea>';
					break;

				case 'select':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . esc_attr( $disabled ) . '>';
					foreach ( $field['options'] as $opt_val => $opt_label ) {
						$selected = selected( $value, $opt_val, false );
						echo '<option value="' . esc_attr( $opt_val ) . '"' . esc_attr( $selected ) . '>' . esc_html( $opt_label ) . '</option>';
					}
					echo '</select>';
					break;

				case 'radio':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<div class="radio-group">';
					foreach ( $field['options'] as $opt_val => $opt_label ) {
						$checked = checked( $value, $opt_val, false );
						echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opt_val ) . '"' . esc_attr( $checked ) . esc_attr( $disabled ) . '> ' . esc_html( $opt_label ) . '</label>';
					}
					echo '</div>';
					break;

				case 'checkbox':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, '1', false ) . esc_attr( $disabled ) . '>';
					break;

				case 'switch':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<label class="switch-control">';
					echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, '1', false ) . esc_attr( $disabled ) . '>';
					echo '<span class="slider round"></span></label>';
					break;

				case 'color':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="color-picker"' . esc_attr( $disabled ) . '>';
					break;

				case 'richtext_editor':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<div class="richtext-editor" data-default-editor="' . esc_attr( $default_editor ) . '">';

					if ( in_array( array( 'html', 'css' ), array( $field['options'] ), true ) ) {
						echo '<ul class="tab-nav">';
							echo '<li data-type="html" class="' . ( ( 'html' === $default_editor ) ? 'active' : '' ) . '">' . esc_html__( 'HTML', 'store-boost-kit' ) . '</li>';
							echo '<li data-type="css" class="' . ( ( 'css' === $default_editor ) ? 'active' : '' ) . '">' . esc_html__( 'CSS', 'store-boost-kit' ) . '</li>';
						echo '</ul>';
					}

					echo '<textarea class="html" name="' . esc_attr( $name ) . '[html]"' . esc_attr( $disabled ) . '>' . esc_textarea( $html_value ) . '</textarea>';
					echo '<textarea class="css" name="' . esc_attr( $name ) . '[css]" style="display:none;"' . esc_attr( $disabled ) . '>' . esc_textarea( $css_value ) . '</textarea>';

					if ( $description ) {
						echo '<p>* ' . esc_html( $description ) . '</p>';
					}
					echo '</div>';
					break;

				case 'number':
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text"' . esc_attr( $disabled ) . '>';
					break;

				case 'text':
				default:
					$disabled = $is_pro && ! $this->pro ? ' disabled' : '';
					echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text"' . esc_attr( $disabled ) . '>';
					break;
			}
			?>
		<?php if ( 'group_start' !== $type && 'group_end' !== $type ) { ?>
		</div>
		<?php } ?>
		<?php
	}
}