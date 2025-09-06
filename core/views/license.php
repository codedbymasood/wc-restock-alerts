<?php
/**
 * License page html.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

$product_lists = apply_filters( 'stobokit_product_lists', array() );
?>
<div class="stobokit-wrapper">
	<span class="license-notice"></span>
	<h2><?php esc_html_e( 'License', 'plugin-slug' ); ?></h2>
	<?php wp_nonce_field( 'stobokit_license', 'stobokit_license_nonce' ); ?>
	<table>
		<thead>
			<tr>
				<th class="product"><?php esc_html_e( 'Product', 'plugin-slug' ); ?></th>
				<th class="status"><?php esc_html_e( 'Status', 'plugin-slug' ); ?></th>
				<th class="license"><?php esc_html_e( 'License', 'plugin-slug' ); ?></th>
				<th class="action"><?php esc_html_e( 'Action', 'plugin-slug' ); ?></th>
				<th class="expires"><?php esc_html_e( 'Expires At', 'plugin-slug' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( ! empty( $product_lists ) ) {
				foreach ( $product_lists as $key => $product ) {
					$expires_at     = get_option( $key . '_expire_date' );
					$license_key    = get_option( $key . '_license_key' );
					$license_status = get_option( $key . '_license_status', 'inactive' );
					?>
					<tr data-id="<?php echo esc_attr( $product['id'] ); ?>" data-slug="<?php echo esc_attr( $key ); ?>">
						<td class="product"><?php echo esc_html( $product['name'] ); ?></td>
						<td class="status"><span class="<?php echo esc_attr( $license_status ); ?>"><?php echo esc_html( $license_status ); ?></span></td>
						<td class="license"><input name="<?php echo esc_attr( $key . '_license_key' ); ?>" type="text" value="<?php echo esc_attr( $license_key ); ?>" placeholder="<?php esc_html_e( 'Enter license key', 'plugin-slug' ); ?>"></td>
						<td class="action">
							<span class="activate-license btn"><?php esc_html_e( 'Activate', 'plugin-slug' ); ?></span>
							<span class="deactivate-license btn btn-red"><?php esc_html_e( 'Deactivate', 'plugin-slug' ); ?></span>
						</td>
						<td class="expires"><?php echo esc_html( $expires_at ); ?></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
</div>
