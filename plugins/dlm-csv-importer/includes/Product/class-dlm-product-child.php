<?php

if ( ! class_exists( 'DLM_Extension' ) ) {

	class DLM_Extension {

		/**
		 * product
		 *
		 * @var mixed
		 */
		private $product;

		/**
		 * version
		 *
		 * @var mixed
		 */
		private $version;

		/**
		 * Class constructor
		 *
		 * @param  mixed $extension
		 * @param  mixed $product
		 * @return void
		 */
		public function __construct( $extension, $product ) {

			$this->product = $product;

			$this->version = $extension['version'];

			add_action( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
			add_action( 'after_plugin_row_' . $this->product->get_plugin_name(), array( $this, 'after_plugin_row', ), 10, 2 );
			add_filter( 'dlm_extension_inline_action_' . $this->product->get_plugin_name(), array( $this, 'extension_update_link' ), 15 );
		}

		/**
		 * Extension update link set in main plugin inline notificationa
		 *
		 * @return void
		 */
		public function extension_update_link(){

			return wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $this->product->get_plugin_name() ) ), 'upgrade-plugin_' . $this->product->get_plugin_name() );

		}

		/**
		 * Display notice after extension plugin row
		 *
		 * @param string $file
		 * @param array  $plugin_data
		 */
		public function after_plugin_row( $file, $plugin_data ) {

			// Don't show if license is activated.
			if ( $this->product->get_license()->is_active() ) {
				return;
			}

			// Output row with message telling people to activate their license.
			$id = sanitize_title( $plugin_data['Name'] );
			echo '<tr class="plugin-update-tr active">';
			echo '<td colspan="3" class="plugin-update colspanchange">';
			echo '<div style="padding: 6px 12px; margin: 0 10px 8px 31px; background: lightYellow;">';
			$link = admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' );
			if ( class_exists( 'DLM_PRO' ) ) {
				$link = admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=license' );
			}
			printf( __( '<a href="%1$s">Register your copy</a> of the <strong>%2$s</strong> extension to receive access to automatic upgrades and support. Need a license key? <a href="%3$s" target="_blank">Purchase one now</a>.', 'download-monitor' ), esc_url( $link ), $this->product->get_product_name(), $this->product->get_tracking_url( 'plugins_page' ) );
			echo '</div></td></tr>';

			// Disable bottom border on parent row.
			echo '<style scoped="scoped">';
			echo sprintf( '#%s td, #%s th { box-shadow: none !important; }', $id, $id );
			echo '</style>';
		}

		/**
		 * Plugins API
		 *
		 * @param bool   $false
		 * @param string $action
		 * @param array  $args
		 *
		 * @return mixed
		 */
		public function plugins_api( $false, $action, $args ) {

			// License.
			$license = $this->product->get_license();

			// Only take over plugin info screen if license is activated.
			if ( true !== $license->is_active() ) {
				return $false;
			}

			// Check if this request if for this product.
			if ( ! isset( $args->slug ) || ( $args->slug !== $this->product->get_product_id() ) ) {
				return $false;
			}

			// Get the current version
			$plugin_info = get_site_transient( 'update_plugins' );
			$current_ver = ( $this->version !== false ) ? $this->version : ( isset( $plugin_info->checked[ $this->product->get_plugin_name() ] ) ? $plugin_info->checked[ $this->product->get_plugin_name() ] : '' );

			$request = wp_remote_get(
				DLM_Product::STORE_URL . DLM_Product::ENDPOINT_UPDATE . '&' . http_build_query(
					array(
						'request'        => 'plugininformation',
						'plugin_name'    => $this->product->get_plugin_name(),
						'version'        => $current_ver,
						'api_product_id' => $this->product->get_product_id(),
						'licence_key'    => $license->get_key(),
						'email'          => $license->get_email(),
						'instance'       => site_url(),
					),
					'',
					'&'
				)
			);

			// Check if request is correct
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				return $false;
			}

			// Check for a plugin update
			$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );

			// $response must be an object
			if ( ! is_object( $response ) ) {
				return $false;
			}

			// Handle errors
			if ( isset( $response->errors ) ) {
				$this->product->handle_errors( $response->errors );

				return $false;
			}

			// If everything is okay return the $response
			if ( isset( $response ) && is_object( $response ) && false !== $response ) {
				return $response;
			}
		}

		/**
		 * Check for plugin updates
		 *
		 * @return array
		 * @var $check_for_updates_data
		 */
		public function check_for_updates( $check_for_updates_data ) {

			// Get license
			$license = $this->product->get_license();

			// Check if checked is set
			if ( empty( $check_for_updates_data->checked ) ) {
				return $check_for_updates_data;
			}

			// Only check for data if license is activated
			if ( true !== $license->is_active() ) {
				return $check_for_updates_data;
			}

			// Get current version
			$current_ver = ( $this->version !== false ) ? $this->version : $check_for_updates_data->checked[ $this->product->get_plugin_name() ];

			// The request
			$request = wp_remote_get(
				DLM_Product::STORE_URL . DLM_Product::ENDPOINT_UPDATE . '&' . http_build_query(
					array(
						'request'        => 'pluginupdatecheck',
						'plugin_name'    => $this->product->get_plugin_name(),
						'version'        => $current_ver,
						'api_product_id' => $this->product->get_product_id(),
						'licence_key'    => $license->get_key(),
						'email'          => $license->get_email(),
						'instance'       => site_url(),
					),
					'',
					'&'
				)
			);

			// Check if request is correct
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				return $check_for_updates_data;
			}

			// Check for a plugin update
			$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );

			// $response must be an object
			if ( ! is_object( $response ) ) {
				return $check_for_updates_data;
			}

			if ( isset( $response->errors ) ) {
				$this->product->handle_errors( $response->errors );

				return $check_for_updates_data;
			}

			// Set version variables
			if ( is_object( $response ) && false !== $response && isset( $response->new_version ) ) {

				// Check if there's a new version
				if ( version_compare( $response->new_version, $current_ver, '>' ) ) {
					$check_for_updates_data->response[ $this->product->get_plugin_name() ] = $response;
				}
			}

			return $check_for_updates_data;
		}
	}
}

add_action( 'dlm_extensions_action_dlm-csv-importer', 'dlm_csv_importer_extension', 15, 2 );

/**
 * Extension notification
 *
 * @param  mixed $extension
 * @param  mixed $product
 * @return void
 */
function dlm_csv_importer_extension( $extension, $product ) {
	new DLM_Extension( $extension, $product );

}
