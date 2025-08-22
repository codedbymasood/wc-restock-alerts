<?php
/**
 * Metabox register class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox class.
 */
class Metabox {

	private $metabox_id;
	private $metabox_title;
	private $post_types;
	private $fields;
	private $context;
	private $priority;

	public function __construct( $metabox_id, $metabox_title, $post_types, $fields, $context = 'normal', $priority = 'default' ) {
		$this->metabox_id    = $metabox_id;
		$this->metabox_title = $metabox_title;
		$this->post_types    = (array) $post_types;
		$this->fields        = $fields;
		$this->context       = $context;
		$this->priority      = $priority;

		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function add_metabox() {
		foreach ( $this->post_types as $post_type ) {
			add_meta_box(
				$this->metabox_id,
				$this->metabox_title,
				array( $this, 'render_metabox' ),
				$post_type,
				$this->context,
				$this->priority
			);
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
				return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'metabox', STOBOKIT_URL . '/assets/css/metabox.css', array(), '1.0' );
		wp_enqueue_script( 'admin', STOBOKIT_URL . '/assets/js/metabox.js', array(), '1.0', true );
	}

	public function render_metabox( $post ) {
		wp_nonce_field( $this->metabox_id . '_nonce', $this->metabox_id . '_nonce' );

		if ( count( $this->fields ) > 1 ) {
			echo '<div class="stobokit-wrapper no-spacing">';
			echo '<div class="nav-tab-wrapper vertical">';
			// Render tabs if multiple field groups.
			echo '<div class="nav-tabs">';
			$first = true;
			foreach ( $this->fields as $tab_name => $tab_fields ) {
				$active_class = $first ? 'nav-tab-active' : '';
				$tab_id       = sanitize_title( $tab_name );

				echo '<span class="nav-tab ' . esc_attr( $active_class ) . '" data-target="' . esc_attr( $tab_id ) . '">' . esc_html( $tab_name ) . '</span>';
				$first = false;
			}
			echo '</div>';

			// Render tab content.
			$first = true;
			echo '<div class="nav-tab-content-wrapper">';
			foreach ( $this->fields as $tab_name => $tab_fields ) {
				$active_class = $first ? 'active' : '';
				$tab_id       = sanitize_title( $tab_name );
				echo '<div id="' . esc_attr( $tab_id ) . '" class="nav-tab-content ' . esc_attr( $active_class ) . '">';
				$this->render_fields( $tab_fields, $post );
				echo '</div>';
				$first = false;
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
		} else {
			echo '<div class="stobokit-wrapper spacing">';
			// Render single group.
			$this->render_fields( reset( $this->fields ), $post );
			echo '</div>';
		}
	}

	private function render_fields( $fields, $post ) {
		foreach ( $fields as $field ) {
			$this->render_field( $field, $post );
		}
	}

	private function render_field( $field, $post, $is_repeater = false, $repeater_index = null, $parent_field_id = null ) {
		if ( $is_repeater ) {
			$field_id   = $parent_field_id . '_' . $repeater_index . '_' . $field['id'];
			$field_name = $parent_field_id . '[' . $repeater_index . '][' . $field['id'] . ']';

			// Get repeater field value - using direct field ID.
			$parent_value = get_post_meta( $post->ID, $parent_field_id, true );
			$field_value  = isset( $parent_value[ $repeater_index ][ $field['id'] ] ) ? $parent_value[ $repeater_index ][ $field['id'] ] : '';
		} else {
			$field_id    = $field['id'];
			$field_name  = $field['id'];
			$field_value = get_post_meta( $post->ID, $field['id'], true );
		}

		$condition_attr = '';
		if ( isset( $field['condition'] ) && ! $is_repeater ) {
			$condition_attr = 'data-condition="' . esc_attr( wp_json_encode( $field['condition'] ) ) . '"';
		}

		echo '<div class="field-wrap field-' . esc_attr( $field['type'] ) . '"' . $condition_attr . '>';

		if ( isset( $field['label'] ) ) {
			echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label>';
		}

		// Handle repeater field type.
		if ( 'repeater' === $field['type'] && ! $is_repeater ) {
			$this->render_repeater_field( $field, $post, $field_value );
		} else {
			// Render all other field types using callback.
			$this->render_field_input( $field, $field_id, $field_name, $field_value, $is_repeater );
		}

		if ( isset( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}

		echo '</div>';
	}

	private function render_repeater_field( $field, $post, $field_value ) {
		$repeater_values = (array) $field_value;
		echo '<div class="repeater-container" data-field-id="' . esc_attr( $field['id'] ) . '">';

		// Render existing items.
		if ( ! empty( $repeater_values ) && is_array( $repeater_values ) ) {
			foreach ( $repeater_values as $index => $repeater_value ) {
				echo '<div class="repeater-item" data-index="' . esc_attr( $index ) . '">';
				echo '<div class="repeater-header">';
				echo '<span class="repeater-title">' . esc_html__( 'Item: ', 'store-boost-kit' ) . ' ' . esc_html( ( (int) $index + 1 ) ) . '</span>';
				echo '<span class="remove-item"><span class="dashicons dashicons-no-alt"></span></span>';
				echo '</div>';
				echo '<div class="repeater-content">';

				foreach ( $field['fields'] as $repeater_field ) {
					$this->render_field( $repeater_field, $post, true, $index, $field['id'] );
				}

				echo '</div>';
				echo '</div>';
			}
		}

		// Template for new items.
		echo '<script type="text/template" class="repeater-template">';
		echo '<div class="repeater-item" data-index="{INDEX}">';
		echo '<div class="repeater-header">';
		echo '<span class="repeater-title">' . esc_html__( 'Item: ', 'store-boost-kit' ) . ' {INDEX_DISPLAY}</span>';
		echo '<span class="remove-item"><span class="dashicons dashicons-no-alt"></span></span>';
		echo '</div>';
		echo '<div class="repeater-content">';

		foreach ( $field['fields'] as $repeater_field ) {
			$template_field_id = $field['id'] . '_{INDEX}_' . $repeater_field['id'];
			$template_field_name = $field['id'] . '[{INDEX}][' . $repeater_field['id'] . ']';

			echo '<div class="metabox-field">';
			if ( isset( $repeater_field['label'] ) ) {
				echo '<label for="' . esc_attr( $template_field_id ) . '">' . esc_html( $repeater_field['label'] ) . '</label>';
			}

			// Render field without value and with template placeholders.
			$this->render_field_input( $repeater_field, $template_field_id, $template_field_name, '', true );

			if ( isset( $repeater_field['description'] ) ) {
				echo '<p class="description">' . esc_html( $repeater_field['description'] ) . '</p>';
			}
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
		echo '</script>';

		echo '</div>';
		echo '<button type="button" class="button add-repeater-item">' . esc_html__( 'Add Item', 'store-boost-kit' ) . '</button>';
	}

	private function render_field_input( $field, $field_id, $field_name, $field_value, $is_repeater = false ) {
		switch ( $field['type'] ) {
			case 'text':
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" />';
				break;

			case 'textarea':
				echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">' . esc_textarea( $field_value ) . '</textarea>';
				break;

			case 'select':
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">';
				if ( isset( $field['options'] ) ) {
					foreach ( $field['options'] as $option_value => $option_label ) {
						$selected = selected( $field_value, $option_value, false );
						echo '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_label ) . '</option>';
					}
				}
				echo '</select>';
				break;

			case 'radio':
				if ( isset( $field['options'] ) ) {
					echo '<div class="radio-group">';
					foreach ( $field['options'] as $option_value => $option_label ) {
						$checked  = checked( $field_value, $option_value, false );
						$radio_id = $field_id . '_' . $option_value;
						echo '<label for="' . esc_attr( $radio_id ) . '"><input type="radio" id="' . esc_attr( $radio_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $option_value ) . '" ' . esc_attr( $checked ) . '/>' . esc_html( $option_label ) . '</label>';
					}
					echo '</div>';
				}
				break;

			case 'checkbox':
				$checked = checked( $field_value, '1', false );
				echo '<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . $checked . '/>';
				break;

			case 'switch':
				$checked = checked( $field_value, '1', false );
				echo '<label class="switch-control">';
				echo '<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . $checked . ' />';
				echo '<span class="slider round"></span>';
				echo '</label>';
				break;

			case 'color':
				$color_class = $is_repeater ? 'color-picker-repeater' : 'color-picker';
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="' . esc_attr( $color_class ) . '" />';
				break;

			case 'media':
				$media_url      = '';
				$media_filename = '';
				if ( $field_value ) {
					$attachment = wp_get_attachment_url( $field_value );
					if ( $attachment ) {
						$media_url = $attachment;
						$media_filename = basename( $attachment );
					}
				}
				echo '<input type="hidden" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" />';
				echo '<button type="button" class="btn upload-media-button">' . esc_html__( 'Select Media', 'store-boost-kit' ) . '</button>';
				echo '<button type="button" class="btn btn-red remove-media-button">' . esc_html__( 'Remove', 'store-boost-kit' ) . '</button>';
				echo '<div class="media-preview">';
				if ( $media_url ) {
					$file_type = wp_check_filetype( $media_url );
					if ( strpos( $file_type['type'], 'image') !== false ) {
						echo '<img src="' . esc_url( $media_url ) . '" style="max-width: 150px; height: auto;" />';
					} else {
						echo '<p>' . esc_html( $media_filename ) . '</p>';
					}
				}
				echo '</div>';
				break;
		}
	}

	public function save_metabox( $post_id ) {
		if ( ! isset( $_POST[ $this->metabox_id . '_nonce' ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ $this->metabox_id . '_nonce' ] ), $this->metabox_id . '_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Get all field IDs.
		$all_field_ids = $this->get_all_field_ids( $this->fields );

		foreach ( $all_field_ids as $field_id ) {
			if ( isset( $_POST[ $field_id ] ) ) {
				$sanitized_value = $this->sanitize_field_value( $_POST[ $field_id ] );
				update_post_meta( $post_id, $field_id, $sanitized_value );
			} else {
				// For non-repeater fields, delete meta if field is not present (for checkboxes, etc.)
				// For repeater fields, we need to check if it's a repeater to avoid deleting valid empty arrays.
				$is_repeater_field = $this->is_repeater_field( $field_id );
				if ( ! $is_repeater_field ) {
					delete_post_meta( $post_id, $field_id );
				}
			}
		}
	}

	/**
	 * Get all field IDs from the fields configuration.
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	private function get_all_field_ids( $fields ) {
		$field_ids = array();

		foreach ( $fields as $tab_fields ) {
			if ( is_array( $tab_fields ) ) {
				foreach ( $tab_fields as $field ) {
					$field_ids[] = $field['id'];
				}
			}
		}
		return $field_ids;
	}

	/**
	 * Check if a field ID belongs to a repeater field.
	 *
	 * @param string $field_id Field ID.
	 * @return boolean
	 */
	private function is_repeater_field( $field_id ) {
		foreach ( $this->fields as $tab_fields ) {
			if ( is_array( $tab_fields ) ) {
				foreach ( $tab_fields as $field ) {
					if ( $field['id'] === $field_id && isset( $field['type'] ) && $field['type'] === 'repeater' ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private function sanitize_field_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_field_value' ), $value );
		}

		return sanitize_text_field( $value );
	}

	public static function get_field( $post_id, $field_id ) {
		return get_post_meta( $post_id, $field_id, true );
	}
}
