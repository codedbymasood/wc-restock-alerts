<?php
/**
 * Frontend Template Override Class.
 *
 * Simple template loading with override functionality for WooCommerce plugins
 * Users can override templates by placing them in theme/stobokit/plugin-slug/
 *
 * @package StoboKit Core
 * @version 1.0.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'STOBOKIT\Frontend_Template' ) ) {

	/**
	 * Frontend Template Override Class.
	 */
	class Frontend_Template {

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * Plugin templates directory path.
		 *
		 * @var string
		 */
		private $plugin_templates_path;

		/**
		 * Theme override directory name.
		 *
		 * @var string
		 */
		private $theme_override_dir = 'stobokit';

		/**
		 * Constructor
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $plugin_templates_path Full path to plugin templates directory.
		 */
		public function __construct( $plugin_slug, $plugin_templates_path ) {
			$this->plugin_slug           = sanitize_key( $plugin_slug );
			$this->plugin_templates_path = trailingslashit( $plugin_templates_path );
		}

		/**
		 * Include template with override support
		 *
		 * @param string $template_name Template filename.
		 * @param array  $args Variables to extract in template.
		 * @param string $template_path Optional. Subdirectory within templates folder.
		 */
		public function include_template( $template_name, $args = array(), $template_path = '' ) {
			$template_file = $this->locate_template( $template_name, $template_path );

			$this->include_template_file( $template_file, $args );
		}

		/**
		 * Get template with override support (returns HTML as string)
		 *
		 * @param string $template_name Template filename.
		 * @param array  $args Variables to extract in template.
		 * @param string $template_path Optional. Subdirectory within templates folder.
		 * @return string Template output.
		 */
		public function get_template( $template_name, $args = array(), $template_path = '' ) {
			$template_file = $this->locate_template( $template_name, $template_path );

			ob_start();
			$this->include_template_file( $template_file, $args );
			return ob_get_clean();
		}

		/**
		 * Get template part with numbering support
		 *
		 * @param string $slug Template slug (e.g., 'header').
		 * @param string $name Template name/variation (e.g., '1', 'product').
		 * @param array  $args Variables to extract in template.
		 * @param string $template_path Subdirectory within templates folder.
		 */
		public function get_template_part( $slug, $name = null, $args = array(), $template_path = '' ) {
			$templates = array();

			if ( $name ) {
				$templates[] = "{$slug}-{$name}.php";
			}
			$templates[] = "{$slug}.php";

			foreach ( $templates as $template_name ) {
				if ( $this->template_exists( $template_name, $template_path ) ) {
					$this->include_template( $template_name, $args, $template_path );
					return;
				}
			}
		}

		/**
		 * Locate template file with override hierarchy.
		 *
		 * @param string $template_name Template filename.
		 * @param string $template_path Subdirectory within templates folder.
		 * @return string Template file path.
		 */
		public function locate_template( $template_name, $template_path = '' ) {
			// Clean template path.
			$template_path      = trim( $template_path, '/' );
			$template_file_path = $template_path ? $template_path . '/' . $template_name : $template_name;

			// Theme override paths (in order of priority).
			$theme_locations = array(
				// Child theme override.
				get_stylesheet_directory() . '/' . $this->theme_override_dir . '/' . $this->plugin_slug . '/' . $template_file_path,

				// Parent theme override.
				get_template_directory() . '/' . $this->theme_override_dir . '/' . $this->plugin_slug . '/' . $template_file_path,
			);

			// Check theme overrides first.
			foreach ( $theme_locations as $theme_file ) {
				if ( file_exists( $theme_file ) ) {
					return $theme_file;
				}
			}

			// Plugin default template.
			return $this->plugin_templates_path . $template_file_path;
		}

		/**
		 * Include template file with variable extraction.
		 *
		 * @param string $template_file Full path to template file.
		 * @param array  $args Variables to extract in template.
		 */
		private function include_template_file( $template_file, $args = array() ) {

			// Allow plugins to modify template args.
			$args = apply_filters( 'stobokit_frontend_template_args', $args, $template_file, $this->plugin_slug );

			$template_file = apply_filters( 'stobokit_frontend_template_file', $template_file, $args, $this->plugin_slug );

			// Include the template.
			include $template_file;
		}

		/**
		 * Check if template exists (with override support).
		 *
		 * @param string $template_name Template filename.
		 * @param string $template_path Subdirectory within templates folder.
		 * @return bool True if template exists.
		 */
		public function template_exists( $template_name, $template_path = '' ) {
			$template_path      = trim( $template_path, '/' );
			$template_file_path = $template_path ? $template_path . '/' . $template_name : $template_name;

			// Check theme overrides.
			$theme_locations = array(
				get_stylesheet_directory() . '/' . $this->theme_override_dir . '/' . $this->plugin_slug . '/' . $template_file_path,
				get_template_directory() . '/' . $this->theme_override_dir . '/' . $this->plugin_slug . '/' . $template_file_path,
			);

			foreach ( $theme_locations as $theme_file ) {
				if ( file_exists( $theme_file ) ) {
					return true;
				}
			}

			// Check plugin template.
			return file_exists( $this->plugin_templates_path . $template_file_path );
		}

		/**
		 * Get plugin slug
		 *
		 * @return string Plugin slug
		 */
		public function get_plugin_slug() {
			return $this->plugin_slug;
		}

		/**
		 * Set theme override directory name
		 *
		 * @param string $dir_name Directory name.
		 */
		public function set_theme_override_dir( $dir_name ) {
			$this->theme_override_dir = sanitize_key( $dir_name );
		}
	}
}
