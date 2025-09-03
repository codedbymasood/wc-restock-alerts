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

$current_tab = isset( $_GET['tab'] ) && ! empty( isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'status';

global $wpdb;
$theme = wp_get_theme();
?>

<div class="stobokit-wrapper">
	<div class="nav-tab-wrapper horizontal">
		<div class="nav-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=stobokit-status&tab=status' ) ); ?>" class="nav-tab<?php echo 'status' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Status', 'store-boost-kit' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=stobokit-status&tab=logs' ) ); ?>" class="nav-tab<?php echo 'logs' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'store-boost-kit' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=stobokit-status&tab=>email-logs' ) ); ?>" class="nav-tab<?php echo 'email-logs' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Email Logs', 'store-boost-kit' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=stobokit-status&tab=scheduled-actions' ) ); ?>" class="nav-tab<?php echo 'scheduled-actions' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Scheduled Actions', 'store-boost-kit' ); ?></a
		</div>
	</div>

	<div class="nav-tab-content-wrapper full-width">		
		<!-- Status -->
		<div class="nav-tab-content <?php echo 'status' === $current_tab ? 'active' : ''; ?>">
			<div class="stobokit-wrapper no-spacing">
				<div class="status-table wrap">
					<h1><?php esc_html_e( 'Plugin Status', 'store-boost-kit' ); ?></h1>
					<?php
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
		</div>

		<!-- Logs -->
		<div class="nav-tab-content <?php echo 'logs' === $current_tab ? 'active' : ''; ?>">
			<?php
			$logger = new Logger();

			$logs = $logger->get_logs();

			if ( isset( $_POST['action'] ) && 'download' === $_POST['action'] && wp_verify_nonce( $_POST['_wpnonce'], 'download_logs' ) ) {
				$filename = 'debug-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.txt';
				$content  = $logger->export_as_text();

				if ( ! empty( $content ) ) {
					// Clean any previous output.
					if ( ob_get_level() ) {
						ob_end_clean();
					}

					header( 'Content-Type: text/plain; charset=utf-8' );
					header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
					header( 'Content-Length: ' . (int) strlen( $content ) );
					header( 'Cache-Control: no-cache, no-store, must-revalidate' );
					header( 'Pragma: no-cache' );
					header( 'Expires: 0' );

					echo esc_html( $content );
					exit;
				}
			}

			if ( isset( $_POST['action'] ) && 'clear_logs' === $_POST['action'] && wp_verify_nonce( $_POST['_wpnonce'], 'clear_logs' ) ) {
				$logger->clear_logs();
				wp_safe_redirect( admin_url( 'admin.php?page=stobokit-status&tab=logs' ) );
			}
			?>
			<div class="stobokit-wrapper no-spacing">
				<?php if ( empty( $logs ) ) : ?>
					<div class="log-container">
						<div class="log-entry">
							<p><?php esc_html_e( 'No debug logs have been recorded yet.', 'store-boost-kit' ); ?></p>
						</div>
					</div>
				<?php else : ?>
					<div class="log-container">
						<?php foreach ( $logs as $index => $log ) : ?>
							<div class="log-entry log-level-<?php echo esc_attr( strtolower( $log['level'] ) ); ?>">
								<div class="log-header">
									<span class="log-time"><?php echo esc_html( gmdate( 'M j, Y H:i:s', strtotime( $log['timestamp'] ) ) ); ?></span>
									<span class="log-level-badge log-<?php echo esc_attr( strtolower( $log['level'] ) ); ?>">
											<?php echo esc_html( $log['level'] ); ?>
									</span>
									<?php if ( $log['file'] ) : ?>
										<span class="log-source">
											<?php echo esc_html( $log['file'] ); ?><?php echo $log['line'] ? ':' . esc_html( $log['line'] ) : ''; ?>
										</span>
									<?php endif; ?>
								</div>
								
								<div class="log-message">
										<?php echo esc_html( $log['message'] ); ?>
								</div>
									
								<?php if ( ! empty( $log['context'] ) || $log['user_id'] || $log['ip'] ) : ?>
									<div class="log-details">
										<?php if ( ! empty( $log['context'] ) ) : ?>
											<div class="log-context">
												<strong>Context:</strong> <code><?php echo esc_html( wp_json_encode( $log['context'], JSON_PRETTY_PRINT ) ); ?></code>
											</div>
										<?php endif; ?>
											
										<?php if ( $log['user_id'] ) : ?>
											<div class="log-user">
												<?php $user = get_user_by( 'ID', $log['user_id'] ); ?>
												<strong>User:</strong> 
												<?php if ( $user ) : ?>
													<?php echo esc_html( $user->user_login ); ?> (ID: <?php echo esc_html( $log['user_id'] ); ?>)
												<?php else : ?>
													User ID: <?php echo esc_html( $log['user_id'] ); ?>
												<?php endif; ?>
											</div>
										<?php endif; ?>
										
										<?php if ( $log['ip'] ) : ?>
											<div class="log-ip">
												<strong>IP:</strong> <?php echo esc_html( $log['ip'] ); ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<form method="post">
					<?php wp_nonce_field( 'download_logs' ); ?>
					<input type="hidden" name="action" value="download">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Download Logs', 'store-boost-kit' ); ?></button>
				</form>

				<form method="post">
					<?php wp_nonce_field( 'clear_logs' ); ?>
					<input type="hidden" name="action" value="clear_logs">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Clear Logs', 'store-boost-kit' ); ?></button>
				</form>
			</div>
		</div>

		<!-- Email Logs -->
		<div class="nav-tab-content <?php echo 'email-logs' === $current_tab ? 'active' : ''; ?>">
			
			<div class="stobokit-wrapper no-spacing">
				<?php
				$logs = array_reverse( get_option( 'stobokit_emailer_logs', array() ) );

				if ( isset( $_POST['action'] ) && 'clear_email_logs' === $_POST['action'] && wp_verify_nonce( $_POST['_wpnonce'], 'clear_email_logs' ) ) {
					update_option( 'stobokit_emailer_logs', array() );
					wp_safe_redirect( admin_url( 'admin.php?page=stobokit-status&tab=email-logs' ) );
				}
				?>
				<div class="wrap">
					<h1><?php esc_html_e( 'Email Logs', 'store-boost-kit' ); ?></h1>
					<table class="wp-list-table widefat fixed striped table-view-list email-logs">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Email', 'store-boost-kit' ); ?></th>
								<th><?php esc_html_e( 'Subject', 'store-boost-kit' ); ?></th>
								<th><?php esc_html_e( 'Sent', 'store-boost-kit' ); ?></th>
								<th><?php esc_html_e( 'Sent At', 'store-boost-kit' ); ?></th>
							</tr>
						</thead>

						<tbody id="the-list">
							<?php
							if ( ! empty( $logs ) ) {
								foreach ( $logs as $log ) {
									?>
									<tr>
										<td><?php echo esc_html( $log['to'] ); ?></td>
										<td><?php echo esc_html( $log['subject'] ); ?></td>
										<td><?php echo esc_html( $log['sent'] ); ?></td>
										<td><?php echo esc_html( $log['sent_at'] ); ?></td>
									</tr>
									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="4"><?php esc_html_e( 'No email logs found.', 'store-boost-kit' ); ?></td>
								</tr>
								<?php
							}
							?>
						</tbody>

					</table>					

					<form method="post">
						<?php wp_nonce_field( 'clear_email_logs' ); ?>
						<input type="hidden" name="action" value="clear_email_logs">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Clear Logs', 'store-boost-kit' ); ?></button>
					</form>
				</div>
			</div>
		</div>


		<!-- Scheduled Actions -->
		<div class="nav-tab-content <?php echo 'scheduled-actions' === $current_tab ? 'active' : ''; ?>">
			<div class="status-table wrap">
				<?php
				$args = array(
					'title'      => esc_html__( 'Scheduled Actions', 'restock-alerts-for-woocommerce' ),
					'singular'   => 'scheduler_log',
					'plural'     => 'scheduler_logs',
					'table_name' => 'stobokit_scheduler_logs',
					'id'         => 'scheduler_logs',
				);

				$notify_table = new \STOBOKIT\Cron_Table( $args );
				$notify_table->display_table();
				?>
			</div>
		</div>

	</div>
</div>
