<?php
/**
 * Status page html.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;


global $wpdb;
$theme = wp_get_theme();

$status_data = array(
	'WordPress Environment' => array(
		'WordPress Version' => get_bloginfo( 'version' ),
		'Site URL'          => site_url(),
		'Home URL'          => home_url(),
		'Active Theme'      => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
		'WP Debug Mode'     => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Enabled' : 'Disabled',
		'WP Memory Limit'   => size_format( wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ) ),
	),
	'Server Environment'    => array(
		'PHP Version'        => phpversion(),
		'MySQL Version'      => $wpdb->db_version(),
		'Server Software'    => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'N/A',
		'PHP Memory Limit'   => size_format( wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ) ),
		'Max Execution Time' => ini_get( 'max_execution_time' ) . 's',
		'Max Upload Size'    => size_format( wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) ) ),
		'Post Max Size'      => size_format( wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ) ),
	),
);

?>
<div class="stobokit-wrapper no-spacing">
	<div class="status-table wrap">
		<h1><?php esc_html_e( 'Plugin Status', 'store-boost-kit' ); ?></h1>
		<?php
		foreach ( $status_data as $table_title => $data ) {
			?>
			<h3><?php echo esc_html( $table_title ); ?></h3>
			<table class="widefat striped">
				<tbody>
				<?php
				foreach ( $data as $label => $value ) {
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td><?php echo esc_html( $value ); ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<?php
		}
		?>
	</div>
</div>
