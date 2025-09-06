<?php
/**
 * Core classes.
 * It is a core class for all the plugins that holds the essential classes.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'STOBOKIT_PATH' ) ) {
	return;
}

define( 'STOBOKIT_VERSION', '1.0' );
define( 'STOBOKIT_PATH', __DIR__ );
define( 'STOBOKIT_URL', plugin_dir_url( __FILE__ ) );

require_once STOBOKIT_PATH . '/class-utils.php';
require_once STOBOKIT_PATH . '/class-license.php';
require_once STOBOKIT_PATH . '/class-list-table.php';
require_once STOBOKIT_PATH . '/class-schedule-logger.php';
require_once STOBOKIT_PATH . '/class-cron-logs-table.php';
require_once STOBOKIT_PATH . '/class-admin.php';
require_once STOBOKIT_PATH . '/class-emailer.php';
require_once STOBOKIT_PATH . '/class-logger.php';
require_once STOBOKIT_PATH . '/class-settings.php';
require_once STOBOKIT_PATH . '/class-metabox.php';
require_once STOBOKIT_PATH . '/class-plugin-updater.php';
require_once STOBOKIT_PATH . '/class-update-handler.php';
require_once STOBOKIT_PATH . '/class-frontend-template.php';
require_once STOBOKIT_PATH . '/class-template-factory.php';
