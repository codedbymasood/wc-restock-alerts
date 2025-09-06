<?php
/**
 * Update handler class.
 *
 * @package plugin-slug\core\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * License update handler.
 */
class Update_Handler {

	/**
	 * Plugin file.
	 *
	 * @var string
	 */
	private $file = '';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * License key.
	 *
	 * @var string
	 */
	private $license = '';

	/**
	 * Product name.
	 *
	 * @var string
	 */
	private $item_name = '';

	/**
	 * Product ID.
	 *
	 * @var integer
	 */
	private $item_id = 0;

	/**
	 * Constructor.
	 */
	public function __construct( $args = array() ) {

		$this->file      = $args['file'];
		$this->slug      = $args['slug'];
		$this->version   = $args['version'];
		$this->license   = $args['license'];
		$this->item_name = $args['item_name'];
		$this->item_id   = $args['item_id'];

		$this->define_constants();

		$status = get_option( $this->slug . '_license_status', 'inactive' );

		if ( 'active' === $status ) {
			$this->setup_update();
		}

		add_action( 'admin_init', array( $this, 'check_license_status' ), 9 );
	}

	/**
	 * Define Constants.
	 *
	 * @return void
	 */
	private function define_constants() {
		if ( ! defined( 'STOBOKIT_SITE_URL' ) ) {
			define( 'STOBOKIT_SITE_URL', 'https://storeboostkit.com' );
		}
	}

	/**
	 * Setup update handler.
	 *
	 * @return void
	 */
	private function setup_update() {

		// Retrieve plugin license key.
		$license_key = trim( $this->license );

		// Setup updater.
		$updater = new Plugin_Updater(
			STOBOKIT_SITE_URL,
			$this->file,
			$this->slug,
			array(
				'version' => $this->version,    // Plugin version.
				'license' => $license_key,      // License key.
				'item_id' => $this->item_id,    // Product ID in EDD.
				'author'  => 'Store Boost Kit', // Plugin author.
				'url'     => home_url(),
			)
		);
	}

	/**
	 * Check license status.
	 *
	 * @return void
	 */
	public function check_license_status() {
		// Check the license status once a day.
		if ( get_transient( $this->slug . '_checked_license_status' ) ) {
			return;
		}

		if ( ! $this->is_license_valid() ) {
			update_option( $this->slug . '_license_status', 'invalid' );
			delete_option( $this->slug . '_license_key' );
		}

		set_transient( $this->slug . '_checked_license_status', true, DAY_IN_SECONDS );
	}

	/**
	 * Checks if the license key is valid.
	 *
	 * This function retrieves the license key from the database and sends an API request
	 * to validate the license key. It returns true if the license key is valid, otherwise false.
	 *
	 * @return bool True if the license key is valid, false otherwise.
	 */
	public function is_license_valid() {
		// Retrieve the license from the database.
		$license = trim( $this->license );

		if ( empty( $license ) ) {
			return false;
		}

		// Data to send in our API request.
		$api_params = array(
			'edd_action'  => 'check_license',
			'license'     => $license,
			'item_id'     => $this->item_id,
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Call the custom API.
		$response = wp_remote_post(
			STOBOKIT_SITE_URL,
			array(
				'method'    => 'GET',
				'timeout'   => 45,
				'sslverify' => false,
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'body'      => $api_params,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			return 'valid' === $license_data->license;
		}

		return false;
	}
}
