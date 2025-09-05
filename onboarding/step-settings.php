<?php
/**
 * Onboarding welcome page.
 *
 * @package plugin-slug\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace PRODEXMA;

defined( 'ABSPATH' ) || exit;

?>

<div class="settings">
	<h2><?php esc_html_e( 'Configure General Settings', 'plugin-slug' ); ?></h2>
	<p><?php esc_html_e( 'Set your default preferences for product expiry. You can always change these later in the plugin settings.', 'plugin-slug' ); ?></p>
	<div class="section setting-fields">
		<form>
			<?php wp_nonce_field( 'stobokit_save_settings', 'stobokit_save_settings_nonce' ); ?>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Default Expiry Period', 'plugin-slug' ); ?></label>
				<input type="number" min="1" name="prodexma_default_expiry_period">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Action on Expiry', 'plugin-slug' ); ?></label>
				<select name="prodexma_action_on_expiry">
					<option value=""><?php esc_html_e( 'Select an action', 'plugin-slug' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Move to draft', 'plugin-slug' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Move to trash', 'plugin-slug' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete permanently', 'plugin-slug' ); ?></option>
				</select>
			</div>
			<span class="save-general-settings btn btn-green"><?php esc_html_e( 'Save', 'plugin-slug' ); ?></span>

			<span class="settings-notice below"></span>
		</form>
	</div>
</div>
