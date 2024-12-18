<?php
namespace DynamicShortcodes\Core\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class License {
	const LICENSE_STATUS_OPTION = '_license_status';
	const LICENSE_ERROR_OPTION  = '_license_error';
	const LICENSE_DOMAIN_OPTION = '_license_domain';
	const LICENSE_KEY_OPTION    = '_license_key';
	/**
	 * @var array
	 */
	private $plugin;

	/**
	 * @var bool
	 */
	private $should_attempt_auto_activation = false;

	/**
	 * @var bool
	 */
	private $is_staging = false;

	/**
	 * Mock license key
	 */
	private $mock_license_key = '1415b451be1a13c283ba771ea52d38bb';

	public function __construct( array $plugin ) {
		$this->plugin = $plugin;
		$this->set_license_key($this->mock_license_key); // Set the mock license key during initialization
	}

	/**
	 * @param bool $fresh false gets cache version, true checks remote status
	 * @return bool
	 */
	public function is_license_active( $fresh = true ) {
		return true; // Always return true for active license
	}

	/**
	 * Summary
	 *
	 * @param string $status either 'active' or 'inactive'
	 * @return void
	 */
	private function set_license_status( $status ) {
		update_option($this->plugin['prefix'] . self::LICENSE_STATUS_OPTION, 'active'); // Always set status to active
	}

	/**
	 * Get error message from last failed status check.
	 *
	 * @return string
	 */
	public function get_license_error() {
		return ''; // No error as the license is always active
	}

	/**
	 * Set license status to inactive and save error message.
	 *
	 * @param string $error
	 */
	private function set_license_error( $error ) {
		$this->set_license_status( 'active' ); // Always set status to active
		update_option( $this->plugin['prefix'] . self::LICENSE_ERROR_OPTION, '' ); // Clear any error
	}

	/**
	 * Set License Key
	 *
	 * @param string $key
	 * @return void
	 */
	private function set_license_key( $key ) {
		update_option( $this->plugin['prefix'] . self::LICENSE_KEY_OPTION, trim( $key ) );
	}

	/**
	 * Get License Key
	 *
	 * @return string
	 */
	public function get_license_key() {
		return get_option( $this->plugin['prefix'] . self::LICENSE_KEY_OPTION, $this->mock_license_key ); // Return the mock license key
	}

	/**
	 * Get last 4 digits of License Key
	 *
	 * @return string
	 */
	public function get_license_key_last_4_digits() {
		return substr( $this->get_license_key(), -4 );
	}

	/**
	 * Activate new License Key
	 *
	 * @param string $key
	 * @return array
	 */
	public function activate_new_license_key( $key ) {
		$this->set_license_key( $key );
		return $this->activate_license();
	}

	/**
	 * Get License Domain
	 *
	 * @return string|bool
	 */
	public function get_last_active_domain() {
		return $this->get_current_domain(); // Always return the current domain
	}

	/**
	 * Set License Domain
	 *
	 * @param string $domain
	 * @return void
	 */
	public function set_last_active_domain( $domain ) {
		update_option( $this->plugin['prefix'] . self::LICENSE_DOMAIN_OPTION, $domain );
	}

	/**
	 * Get current domain without protocol
	 *
	 * @return string
	 */
	public function get_current_domain() {
		$domain = get_bloginfo( 'wpurl' );
		$domain = str_replace( 'https://', '', $domain );
		$domain = str_replace( 'http://', '', $domain );
		return $domain;
	}

	/**
	 * Update the license system options and variables based on the server response.
	 *
	 * @param array $response
	 * @return void
	 */
	public function handle_status_check_response( $response, $domain ) {
		$this->should_attempt_auto_activation = false;
		$this->is_staging                     = false;
		if ( ! $response ) {
			// trouble contacting the server. No changes:
			return;
		}
		if ( ( $response['staging'] ?? '' ) === 'yes' ) {
			$this->is_staging = true;
		}
		$status_code = $response['status_code'] ?? '';
		if ( 'e002' === $status_code ) {
			// key is invalid:
			$this->set_license_error( $response['message'] );
			return;
		}
		if ( in_array( $status_code, [ 's203', 'e204' ], true ) ) {
			// key is not active for current domain, we should not attempt activation:
			$this->set_license_error( $response['message'] . " (domain: $domain)" );
			return;
		}
		if ( in_array( $status_code, [ 's205', 's215' ], true ) ) {
			// if license is valid and active for domain:
			if ( ( $response['license_status'] ?? '' ) === 'expired' ) {
				// But expired:
				$this->set_license_error( $response['message'] );
				$this->should_attempt_auto_activation = true;
				return;
			}
			$this->set_license_status( 'active' );
			$this->set_last_active_domain( $this->get_current_domain() );
			return;
		}
		// other cases, just set the error with message:
		$this->set_license_error( $response['message'] ?? esc_html__( 'Unknown', 'dynamic-content-for-elementor' ) );
	}

	/**
	 * @return bool|array
	 */
	public function remote_status_check( $domain ) {
		return [
			'status_code' => 's215',
			'license_status' => 'active',
			'message' => 'License is active'
		]; // Mock response for always active
	}

	/**
	 * Refresh license status.
	 *
	 * @return void
	 */
	public function refresh_license_status() {
		$this->set_license_status('active'); // Always set status to active
	}

	/**
	 * Refresh license status. If license was not deliberately deactivated try
	 * to reactivate the license for this domain.
	 *
	 * @return void
	 */
	public function refresh_and_repair_license_status() {
		$this->refresh_license_status();
		if ( $this->should_attempt_auto_activation ) {
			$this->activate_license();
		}
	}

	/**
	 * Ask to the server to activate the license
	 *
	 * @return string activation message
	 */
	private function activate_license_request() {
		return 'License activated'; // Mock activation message
	}

	/**
	 * Ask the server to deactivate the license
	 *
	 * @return string activation message
	 */
	private function deactivate_license_request() {
		return 'License deactivated'; // Mock deactivation message
	}

	/**
	 * Ask the server to deactivate the license. Refresh license status.
	 * Delete the key for staging sites.
	 *
	 * @return array [success, msg]
	 */
	public function deactivate_license() {
		$msg     = $this->deactivate_license_request();
		$success = ! $this->is_license_active( true );
		if ( $this->is_staging ) {
			$this->set_license_key( '' );
			$this->refresh_license_status();
			return [ true, esc_html__( 'Success', 'dynamic-content-for-elementor' ) ];
		}
		return [ $success, $msg ];
	}

	/**
	 * Ask the server to activate the license. Refresh license status.
	 *
	 * @return array [success, msg]
	 */
	public function activate_license() {
		$msg     = $this->activate_license_request();
		$success = true; // Always successful activation
		return [ $success, $msg ];
	}

	/**
	 * Active beta releases
	 *
	 * @return void
	 */
	public function activate_beta_releases() {
		update_option( $this->plugin['beta_option'], true );
	}

	/**
	 * Deactivate beta releases
	 *
	 * @return void
	 */
	public function deactivate_beta_releases() {
		update_option( $this->plugin['beta_option'], false );
	}

	/**
	 * Check if beta releases are activated
	 *
	 * @return boolean
	 */
	public function is_beta_releases_activated() {
		return get_option( $this->plugin['beta_option'] );
	}

	/**
	 * Make a request to license server to activate, deactivate or check the status of the license
	 *
	 * @param string $action
	 * @param string $license_key
	 * @param string $domain
	 * @return bool|array
	 */
	public function call_api( string $action, string $license_key, string $domain ) {
		return [
			'status_code' => 's215',
			'license_status' => 'active',
			'message' => 'License is active'
		]; // Mock response for always active
	}

	/**
	 * return true if there is a license mismatch
	 */
	public function domain_mismatch_check() {
		return false; // No mismatch as the license is always active
	}
}
?>
