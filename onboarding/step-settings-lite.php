<?php
/**
 * Onboarding settings page.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="settings">
	<h2><?php esc_html_e( 'Configure General Settings', 'plugin-slug' ); ?></h2>
	<p><?php esc_html_e( 'Set your default preferences for product expiry. You can always change these later in the plugin settings.', 'plugin-slug' ); ?></p>
	<div class="section setting-fields">
		<form>
			<?php wp_nonce_field( 'stobokit_save_settings', 'stobokit_save_settings_nonce' ); ?>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Show Signup Message', 'plugin-slug' ); ?></label>				
				<input type="checkbox" value="1" name="restaler_show_signup_message">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Signup Message', 'plugin-slug' ); ?></label>
				<input type="text" name="restaler_signup_message" value="<?php esc_attr_e( 'Want a reminder when more arrives? Sign up below.', 'plugin-slug' ); ?>">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Stock Threshold', 'plugin-slug' ); ?></label>				
				<input type="checkbox" value="1" name="restaler_enable_stock_threshold">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Stock Threshold Count', 'plugin-slug' ); ?></label>				
				<input type="text" min="1" name="restaler_stock_threshold_count">
			</div>
			<span class="save-general-settings btn btn-green"><?php esc_html_e( 'Save', 'plugin-slug' ); ?></span>

			<span class="settings-notice below"></span>
		</form>
	</div>
</div>