<?php
/**
 * Onboarding welcome page.
 *
 * @package restock-alerts-for-woocommerce\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace PRODEXMA;

defined( 'ABSPATH' ) || exit;

?>

<div class="settings">
	<h2><?php esc_html_e( 'Configure General Settings', 'restock-alerts-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Set your default preferences for product expiry. You can always change these later in the plugin settings.', 'restock-alerts-for-woocommerce' ); ?></p>
	<div class="section setting-fields">
		<form>
			<?php wp_nonce_field( 'stobokit_save_settings', 'stobokit_save_settings_nonce' ); ?>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Default Expiry Period', 'restock-alerts-for-woocommerce' ); ?></label>
				<input type="number" min="1" name="prodexma_default_expiry_period">
			</div>
			<div class="field-wrap">
				<label><?php esc_html_e( 'Action on Expiry', 'restock-alerts-for-woocommerce' ); ?></label>
				<select name="prodexma_action_on_expiry">
					<option value=""><?php esc_html_e( 'Select an action', 'restock-alerts-for-woocommerce' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Move to draft', 'restock-alerts-for-woocommerce' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Move to trash', 'restock-alerts-for-woocommerce' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete permanently', 'restock-alerts-for-woocommerce' ); ?></option>
				</select>
			</div>
			<span class="save-general-settings btn btn-green"><?php esc_html_e( 'Save', 'restock-alerts-for-woocommerce' ); ?></span>

			<span class="settings-notice below"></span>
		</form>
	</div>
</div>
