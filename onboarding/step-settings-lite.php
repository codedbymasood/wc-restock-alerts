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
				<label><?php esc_html_e( 'From Name', 'plugin-slug' ); ?></label>
				<input type="text" min="1" name="stobokit_email_from_name">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'From Email Address', 'plugin-slug' ); ?></label>				
				<input type="text" min="1" name="stobokit_email_from_email">
			</div>
			<span class="save-general-settings btn btn-green"><?php esc_html_e( 'Save', 'plugin-slug' ); ?></span>

			<span class="settings-notice below"></span>
		</form>
	</div>
</div>
