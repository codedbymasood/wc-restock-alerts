<?php
/**
 * Dashboard page html.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

$product_lists = apply_filters( 'stobokit_product_lists', array() );

$cached_posts = get_transient( 'stobokit_recent_posts' );

if ( false === $cached_posts ) {
	$response = wp_remote_get( 'https://storeboostkit.com/wp-json/wp/v2/posts?per_page=4&_fields=title,link' );

	if ( is_wp_error( $response ) ) {
		$recent_posts = false;
		return;
	}

	$recent_posts = json_decode( wp_remote_retrieve_body( $response ) );

	// Save result for 1 week.
	set_transient( 'stobokit_recent_posts', $recent_posts, WEEK_IN_SECONDS );
} else {
	$recent_posts = $cached_posts;
}

$announcement_cached_posts = get_transient( 'stobokit_announcement_posts' );

if ( false === $announcement_cached_posts ) {
	$response = wp_remote_get( 'https://storeboostkit.com/wp-json/storeboostkit/v1/announcements?per_page=3' );

	if ( is_wp_error( $response ) ) {
		$announcement_posts = false;
		return;
	}

	$announcement_posts = json_decode( wp_remote_retrieve_body( $response ) );

	// Save result for 1 week.
	set_transient( 'stobokit_announcement_posts', $announcement_posts, DAY_IN_SECONDS );
} else {
	$announcement_posts = $announcement_cached_posts;
}

?>

<div class="stobokit-wrapper no-spacing">
	<div class="wrap">
		<h2><?php esc_html_e( 'Welcome to Store Boost Kit', 'plugin-slug' ); ?></h2>

		<div class="stobokit-widgets">

			<!-- Quick Links Widget -->
			<div class="stobokit-widget">
					<h3><?php esc_html_e( 'Quick Links', 'plugin-slug' ); ?></h3>
					<ul>
							<li><a href="https://storeboostkit.com/support-form" target="_blank"><?php esc_html_e( 'Support', 'plugin-slug' ); ?></a></li>
							<li><a href="https://help.storeboostkit.com" target="_blank"><?php esc_html_e( 'Help Desk', 'plugin-slug' ); ?></a></li>
							<li><a href="https://storeboostkit.com/frequently-asked-questions" target="_blank"><?php esc_html_e( 'FAQ', 'plugin-slug' ); ?></a></li>
							<li><a href="https://storeboostkit.com/account" target="_blank"><?php esc_html_e( 'My Account', 'plugin-slug' ); ?></a></li>
					</ul>
			</div>

			
			
			<?php
			$product_lists = apply_filters( 'stobokit_product_lists', array() );

			if ( ! empty( $product_lists ) ) {
				?>
				<!-- Licenses Widget -->
				<div class="stobokit-widget">
					<h3><?php esc_html_e( 'Licenses', 'plugin-slug' ); ?></h3>
					<?php
					$inactive_count = 0;
					$expired_count  = 0;
					foreach ( $product_lists as $key => $product ) {
						$license_status = get_option( $key . '_license_status', 'inactive' );

						if ( 'inactive' === $license_status ) {
							$inactive_count++;
						} elseif ( 'expired' === $license_status ) {
							$expired_count++;
						}
					}
					?>
					<p>
						<?php printf( '<span>You have <strong>%1$s</strong> inactive licenses</span>', esc_html( $inactive_count ) ); ?>
						<?php
						if ( $expired_count ) {
							printf( '<span>and<strong>%1$s</strong> expired</span>', esc_html( $expired_count ) );
						}
						?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=stobokit-license' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Licenses', 'plugin-slug' ); ?></a>
				</div>
				<?php
			}
			?>

			<?php if ( ! empty( $recent_posts ) ) : ?>
				<div class="stobokit-widget">
					<h3><?php esc_html_e( 'News & Updates', 'plugin-slug' ); ?></h3>
					<ul>
						<?php foreach ( $recent_posts as $item ) : ?>
							<li>
								<a href="<?php echo esc_url( $item->link ); ?>" target="_blank">
									<?php echo esc_html( $item->title->rendered ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $announcement_posts ) ) : ?>
				<div class="stobokit-widget">
					<h3><?php esc_html_e( 'Announcements & Promotions', 'plugin-slug' ); ?></h3>
					<ul>
						<?php
						foreach ( $announcement_posts as $announcement_item ) :
							$timestamp = strtotime( $announcement_item->date );
							$date      = date_i18n( get_option( 'date_format' ), $timestamp );
							?>
							<li>
								<span class="small"><?php echo esc_html( $date ); ?></span>
								<p><strong><?php echo esc_html( $announcement_item->title ); ?></strong></p>
								<?php echo wp_kses_post( $announcement_item->description ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
