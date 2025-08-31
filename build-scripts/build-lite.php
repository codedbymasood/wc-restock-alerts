<?php
$version = '1.0.0';

$source_dir = dirname( __DIR__ );
$build_dir  = $source_dir . '/builds/lite';

if ( ! is_dir( $build_dir ) ) {
	mkdir( $build_dir, 0755, true );
}

// Copy files.
copy_directory( $source_dir . '/core', $build_dir . '/core' );
copy_directory( $source_dir . '/lite', $build_dir . '/includes' );
copy_directory( $source_dir . '/common', $build_dir . '/common' );
copy_directory( $source_dir . '/onboarding', $build_dir . '/onboarding' );
copy_directory( $source_dir . '/templates', $build_dir . '/templates' );
copy_directory( $source_dir . '/languages', $build_dir . '/languages' );
copy( $source_dir . '/CHANGELOG-LITE.md', $build_dir . '/CHANGELOG.md' );
copy( $source_dir . '/README.md', $build_dir . '/README.md' );
copy( $source_dir . '/readme.txt', $build_dir . '/readme.txt' );

$plugin_header = '<?php
/**
 * Plugin Name: Restock Alerts for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://wordpress.org/plugins/search/restock-alerts-for-woocommerce/
 * Description: Add a Notify Me button for out-of-stock products (Lite Version)
 * Version: ' . $version . '
 * Author: Store Boost Kit
 * Author URI: https://storeboostkit.com/
 * Text Domain: restock-alerts-for-woocommerce
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Domain Path: /languages/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.6
 *
 * @package restock-alerts-for-woocommerce
 */

defined( \'ABSPATH\' ) || exit;

if ( ! defined( \'RESTALER_PLUGIN_FILE\' ) ) {
	define( \'RESTALER_PLUGIN_FILE\', __FILE__ );
}

if ( ! defined( \'RESTALER_VERSION\' ) ) {
	define( \'RESTALER_VERSION\', \'' . $version . '\' );
}

require_once __DIR__ . \'/includes/class-restaler.php\';

/**
 * Returns the main instance of RESTALER.
 *
 * @since  1.0
 * @return RESTALER
 */
function restaler() {
	return \\RESTALER\\RESTALER::instance();
}

// Global for backwards compatibility.
$GLOBALS[\'restaler\'] = restaler();

/**
 * ==========================
 *  Onborading
 * ==========================
 */

// Include the onboarding class.
if ( ! class_exists( \'\\STOBOKIT\\Onboarding\' ) ) {
	include_once dirname( RESTALER_PLUGIN_FILE ) . \'/core/class-onboarding.php\';
}

register_activation_hook( __FILE__, \'restaler_on_plugin_activation\' );

/**
 * Handle plugin activation.
 */
function restaler_on_plugin_activation() {
	// Set flag that plugin was just activated.
	set_transient( \'restaler_onboarding_activation_redirect\', true, 60 );

	// Set onboarding as pending.
	update_option( \'restaler_onboarding_completed\', false );
	update_option( \'restaler_onboarding_started\', current_time( \'timestamp\' ) );

	// Clear any existing onboarding progress.
	delete_option( \'restaler_onboarding_current_step\' );
}
';

file_put_contents( $build_dir . '/restock-alerts-for-woocommerce.php', $plugin_header );

$zip_file = $source_dir . '/builds/restock-alerts-lite-for-woocommerce-' . $version . '.zip';
create_zip_archive( $build_dir, $zip_file );

echo 'Lite version built: ' . $zip_file . "\n";

function copy_directory( $src, $dst ) {
	if ( ! is_dir( $src ) ) {
		return;
	}
	if ( ! is_dir( $dst ) ) {
		mkdir( $dst, 0755, true );
	}

	$files = scandir( $src );
	foreach ( $files as $file ) {
		if ( $file != '.' && $file != '..' ) {
			$src_file = $src . '/' . $file;
			$dst_file = $dst . '/' . $file;

			if ( is_dir( $src_file ) ) {
				copy_directory( $src_file, $dst_file );
			} else {
				copy( $src_file, $dst_file );
			}
		}
	}
}

/**
 * Create archive file
 *
 * @param string $source Source.
 * @param string $destination Destination.
 * @return void
 */
function create_zip_archive( $source, $destination ) {
	$zip = new ZipArchive();
	$zip->open( $destination, ZipArchive::CREATE | ZipArchive::OVERWRITE );

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $source ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $files as $file ) {
		if ( ! $file->isDir() ) {
			$file_path = $file->getRealPath();
			$relative_path = substr( $file_path, strlen( $source ) + 1 );
			$zip->addFile( $file_path, $relative_path );
		}
	}

	$zip->close();
}
