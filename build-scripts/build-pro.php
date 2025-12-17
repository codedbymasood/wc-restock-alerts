<?php
$version = '1.3.1';

$entry_file  = 'restock-alerts-pro-for-woocommerce';
$plugin_slug = 'restock-alerts-for-woocommerce';
$plugin_name = 'Restock Alerts Pro for WooCommerce';

$source_dir = dirname( __DIR__ );
$build_dir  = $source_dir . '/builds/pro';

if ( ! is_dir( $build_dir ) ) {
	mkdir( $build_dir, 0755, true );
}

// Copy files.
copy_directory( $source_dir . '/core', $build_dir . '/core' );
copy_directory( $source_dir . '/pro', $build_dir . '/includes' );
copy_directory( $source_dir . '/common', $build_dir . '/common' );
copy_directory(
	$source_dir . '/onboarding',
	$build_dir . '/onboarding',
	array(
		'*lite.php',
	)
);
copy_directory( $source_dir . '/templates/pro', $build_dir . '/templates' );
copy_directory( $source_dir . '/languages', $build_dir . '/languages' );
copy( $source_dir . '/CHANGELOG-PRO.md', $build_dir . '/CHANGELOG.md' );
copy( $source_dir . '/readme-pro.txt', $build_dir . '/readme.txt' );
copy( $source_dir . '/install.php', $build_dir . '/install.php' );

$replacements = array(
	'plugin-slug' => $plugin_slug,
	'Plugin Name' => $plugin_name,
);

replace_multiple_strings_in_directory( $build_dir, $replacements );

$plugin_header = '<?php
/**
 * Plugin Name: ' . $plugin_name . ' | Back In Stock Notify
 * Requires Plugins: woocommerce
 * Plugin URI: https://storeboostkit.com/product/' . $plugin_slug . '/
 * Description: Add a Notify Me button for out of stock items. Store owner gets the list, user gets email when back in stock.
 * Version: ' . $version . '
 * Author: Store Boost Kit
 * Author URI: https://storeboostkit.com/
 * Text Domain: ' . $plugin_slug . '
 * Domain Path: /languages/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 9.6
 * WC tested up to: 10.4.0
 *
 * @package ' . $plugin_slug . '
 */

defined( \'ABSPATH\' ) || exit;

if ( did_action( \'restaler_initialized\' ) ) {
	deactivate_plugins( \'restock-alerts-for-woocommerce/restock-alerts-for-woocommerce.php\' );
	register_activation_hook( __FILE__, \'restaler_on_plugin_activation\' );
	return;
} else {
	if ( ! defined( \'RESTALER_PLUGIN_FILE\' ) ) {
		define( \'RESTALER_PLUGIN_FILE\', __FILE__ );
	}

	if ( ! defined( \'RESTALER_VERSION\' ) ) {
		define( \'RESTALER_VERSION\', \'' . $version . '\' );
	}

	if ( ! defined( \'RESTALER_PATH\' ) ) {
		define( \'RESTALER_PATH\', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( \'RESTALER_URL\' ) ) {
		define( \'RESTALER_URL\', plugin_dir_url( __FILE__ ) );
	}

	if ( ! class_exists( \'\\RESTALER\\RESTALER\' ) ) {
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

		require_once dirname( RESTALER_PLUGIN_FILE ) . \'/install.php\';

		register_activation_hook( RESTALER_PLUGIN_FILE, array( \'RESTALER\\Install\', \'init\' ) );
	}
}
';

file_put_contents( $build_dir . '/' . $entry_file . '.php', $plugin_header );

$zip_file = $source_dir . '/builds/' . $entry_file . '-' . $version . '.zip';
create_zip_archive( $build_dir, $zip_file );

echo 'Pro version built: ' . $zip_file . "\n";

function replace_multiple_strings_in_directory( $directory, $replacements ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory )
	);

	foreach ( $iterator as $file ) {
		if ( $file->getExtension() === 'php' ) {
			$content = file_get_contents( $file->getPathname() );

			foreach ( $replacements as $search => $replace ) {
				$content = str_replace( $search, $replace, $content );
			}

			file_put_contents( $file->getPathname(), $content );
		}
	}
}

function copy_directory( $src, $dst, $exclude = array() ) {
	if ( ! is_dir( $src ) ) {
		return;
	}
	if ( ! is_dir( $dst ) ) {
		mkdir( $dst, 0755, true );
	}

	$files = scandir( $src );
	foreach ( $files as $file ) {
		if ( '.' !== $file && '..' !== $file ) {
			// Check if file should be excluded.
			$should_exclude = false;
			foreach ( $exclude as $exclude_item ) {
				// Check exact match first.
				if ( $exclude_item === $file ) {
					$should_exclude = true;
					break;
				}
				// Check pattern match.
				if ( strpos( $exclude_item, '*' ) !== false || strpos( $exclude_item, '?' ) !== false ) {
					if ( fnmatch( $exclude_item, $file ) ) {
						$should_exclude = true;
						break;
					}
				}
			}

			if ( $should_exclude ) {
				continue;
			}

			$src_file = $src . '/' . $file;
			$dst_file = $dst . '/' . $file;

			if ( is_dir( $src_file ) ) {
				copy_directory( $src_file, $dst_file, $exclude );
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

function remove_lines_containing( $file_path, $search_strings = array() ) {
	if ( ! file_exists( $file_path ) ) {
		return false;
	}

	// Keep newlines in the lines.
	$lines = file( $file_path );
	$filtered_lines = array();

	foreach ( $lines as $line ) {
		$should_remove = false;

		foreach ( $search_strings as $search_string ) {
			if ( strpos( $line, $search_string ) !== false ) {
					$should_remove = true;
					break;
			}
		}

		if ( ! $should_remove ) {
			$filtered_lines[] = $line;
		}
	}

	// No need to add newlines since they're already preserved.
	return file_put_contents( $file_path, implode( '', $filtered_lines ) );
}
