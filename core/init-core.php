<?php
/**
 * Core classes.
 * It is a core class for all the plugins that holds the essential classes.
 *
 * @package store-boost-kit
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'STOBOKIT_PATH' ) ) {
	return;
}

define( 'STOBOKIT_VERSION', '1.0' );
define( 'STOBOKIT_PATH', dirname( __FILE__ ) );
define( 'STOBOKIT_URL', plugin_dir_url( __FILE__ ) );

require_once STOBOKIT_PATH . '/class-utils.php';
require_once STOBOKIT_PATH . '/class-license.php';
require_once STOBOKIT_PATH . '/class-admin.php';
require_once STOBOKIT_PATH . '/class-settings.php';
require_once STOBOKIT_PATH . '/class-metabox.php';
require_once STOBOKIT_PATH . '/class-plugin-updater.php';
require_once STOBOKIT_PATH . '/update-handler.php';
