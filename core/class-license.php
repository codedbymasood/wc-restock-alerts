<?php
/**
 * License class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

/**
 * License class.
 */
class License {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_stobokit_activate_license', array( $this, 'activate_license' ) );
		add_action( 'wp_ajax_stobokit_deactivate_license', array( $this, 'deactivate_license' ) );
	}

	/**
	 * Activate license
	 *
	 * @return void
	 */
	public function activate_license() {
		if ( ! isset( $_POST['nonce'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'stobokit_license' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry, not verified.', 'store-boost-kit' ),
				)
			);
		}

		$id      = isset( $_POST['id'] ) && ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$slug    = isset( $_POST['slug'] ) && ! empty( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$license = isset( $_POST['license'] ) && ! empty( $_POST['license'] ) ? sanitize_text_field( wp_unslash( $_POST['license'] ) ) : '';

		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => $id,
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			'https://storeboostkit.com/',
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

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
		} else {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $data->success ) {
				switch ( $data->error ) {
					case 'expired':
						$message = sprintf(
							/* translators: 1: Expiry date */
							esc_html__( 'Your license key expired on %s.', 'store-boost-kit' ),
							date_i18n( get_option( 'date_format' ), strtotime( $data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'store-boost-kit' );
						break;
					case 'missing':
						$message = esc_html__( 'Invalid license.', 'store-boost-kit' );
						break;
					case 'invalid':
					case 'site_inactive':
						$message = esc_html__( 'Your license is not active for this URL.', 'store-boost-kit' );
						break;
					case 'invalid_item_id':
						/* translators: 1: Plugin name */
						$message = esc_html__( 'This appears to be an invalid license key for this product.', 'store-boost-kit' );
						break;
					case 'no_activations_left':
						$message = esc_html__( 'Your license key has reached its activation limit.', 'store-boost-kit' );
						break;
					default:
						$message = esc_html__( 'An error occurred, please try again.', 'store-boost-kit' );
						break;
				}
			}
		}

		if ( ! empty( $message ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( $message ),
				)
			);
			exit();
		} else {
			update_option( $slug . '_license_status', 'active' );
			update_option( $slug . '_license_key', $license );
			update_option( $slug . '_expire_date', $data->expires );

			wp_send_json_success(
				array(
					'message'     => esc_html__( 'License activated succesfully!', 'store-boost-kit' ),
					'status'      => 'active',
					'expire_date' => $data->expires,
				)
			);

			exit();
		}
	}

	/**
	 * Deactivate license.
	 *
	 * @return void
	 */
	public function deactivate_license() {
		if ( ! isset( $_POST['nonce'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'stobokit_license' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry, not verified.', 'store-boost-kit' ),
				)
			);
		}

		$id      = isset( $_POST['id'] ) && ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$slug    = isset( $_POST['slug'] ) && ! empty( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$license = isset( $_POST['license'] ) && ! empty( $_POST['license'] ) ? sanitize_text_field( wp_unslash( $_POST['license'] ) ) : '';

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id'    => $id,
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			'https://storeboostkit.com/',
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

		// make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = esc_html__( 'An error occurred, please try again.', 'store-boost-kit' );
			}

			wp_send_json_error(
				array(
					'message' => esc_html( $message ),
				)
			);
			exit();

		} else {
			// Decode the license data.
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// Handle license deactivation or failure.
			if ( isset( $license_data->license ) && in_array( $license_data->license, array( 'deactivated', 'failed' ), true ) ) {
				// Remove the stored license status and key if deactivated.
				update_option( $slug . '_license_status', 'inactive' );
				update_option( $slug . '_license_key', '' );
				update_option( $slug . '_expire_date', '' );

				wp_send_json_success(
					array(
						'message' => esc_html__( 'License deactivated succesfully!', 'store-boost-kit' ),
						'status'  => 'inactive',
					)
				);
			}

			exit();
		}
	}
}

new License();
