<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Check if the class is not defined in another Download Monitor extension.
if ( ! class_exists( 'DLM_PRO_Install' ) ) {
	/**
	 * Class DLM_PRO_Install
	 *
	 * This class handles the installation of the DLM PRO plugin,
	 * based on the DLM 5.0.0 LITE update. It should be passed through each of
	 * the DLM extensions to ensure that the PRO plugin is installed.
	 *
	 */
	class DLM_PRO_Install {

		/**
		 * Holds the class object.
		 *
		 * @var object
		 */
		public static $instance;


		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_PRO_Install object.
		 *
		 */
		public static function get_instance() {
			if ( ! isset( DLM_PRO_Install::$instance ) && ! ( DLM_PRO_Install::$instance instanceof DLM_PRO_Install ) ) {
				DLM_PRO_Install::$instance = new DLM_PRO_Install();
			}

			return DLM_PRO_Install::$instance;
		}

		/**
		 * Constructor.
		 *
		 */
		private function __construct() {
			// Add the action to install the plugin.
			add_action( 'admin_init', array( $this, 'install' ), 60 );
			add_action( 'admin_notices', array( $this, 'pro_install_notices' ) );
		}

		/**
		 * Install the plugin.
		 *
		 */
		public function install() {
			// If AJAX request, return.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			if ( ! $this->requires_install() ) {
				return;
			}
			$this->do_install();
		}

		/**
		 * Check if the plugin requires installation.
		 *
		 * @return bool True if the plugin requires installation, false otherwise.
		 *
		 */
		public function requires_install() {
			// Check if plugin is already active.
			if ( class_exists( 'DLM_PRO' ) ) {
				return false;
			}
			// Check if plugin is already installed.
			$plugin_path = WP_PLUGIN_DIR . '/dlm-pro/dlm-pro.php';

			if ( file_exists( $plugin_path ) ) {
				return false;
			}
			global $wpdb;
			// Let's retrieve extensions that have a license key.
			$extensions = $wpdb->get_results( $wpdb->prepare( "SELECT `option_name`, `option_value` FROM {$wpdb->prefix}options WHERE `option_name` LIKE %s AND `option_name` LIKE %s;", $wpdb->esc_like( 'dlm-' ) . '%', '%' . $wpdb->esc_like( '-license' ) ), ARRAY_A );
			$licensed_extensions = array();

			foreach ( $extensions as $extension ) {
				$extension_name = str_replace( '-license', '', $extension['option_name'] );
				$value          = unserialize( $extension['option_value'] );
				// Extension must have an active status in order to be regitered.
				if ( isset( $value['status'] ) && 'active' === $value['status'] ) {
					$licensed_extensions[] = $extension_name;
				}
			}
			// If not installed, return false.
			if ( empty( $licensed_extensions ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Perform the installation of the plugin.
		 *
		 */
		public function do_install() {
			// Get the license data.
			$product    = new DLM_Product( 'dlm-csv-importer' );
			$license    = $product->get_license();
			// Create the download URL based on the license data and the product ID.
			$download_url = add_query_arg(
				array(
					'download_api_product' => '560006', // this should be the DLM PRO product ID.
					'license_key'          => urlencode( $license->get_key() ),
					'activation_email'     => urlencode( $license->get_email() ),
				), 'https://download-monitor.com/'
			);
			// Prepare variables.
			$method = '';
			$url    = add_query_arg(
				array(
					'page'      => 'dlm-extensions',
					'post_type' => 'dlm_download',
				),
				admin_url( 'edit.php' )
			);
			$url    = esc_url( $url );
			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
				set_transient( 'dlm_pro_install', 'credentials_failed', 60 * 60 );

				return;
			}

			// If we are not authenticated, make it happen now.
			if ( ! WP_Filesystem( $creds ) ) {
				set_transient( 'dlm_pro_install', 'credentials_failed', 60 * 60 );

				return;
			}

			// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			if ( ! class_exists( 'DLM_Upgrader_Skin' ) ) {
				require_once __DIR__ . '/class-dlm-upgrader-skin.php';
			}
			// Reload the extensions, to make sure we have the latest data.
			delete_transient( 'dlm_extension_json' );
			delete_transient( 'dlm_extension_json_error' );
			delete_transient( 'dlm_pro_extensions' );

			// Create the plugin upgrader with our custom skin.
			$installer = new Plugin_Upgrader( new DLM_Upgrader_Skin() );
			// Install the plugin.
			$installer->install( htmlspecialchars_decode( $download_url ) );
			// Activate the plugin.
			activate_plugin( 'dlm-pro/dlm-pro.php' );
		}

		/**
		 * Display the admin notices that the credentials for FPT install are missing.
		 *
		 */
		public function pro_install_notices() {
			// Check if we need to display the notice.
			if ( ! get_transient( 'dlm_pro_install' ) ) {
				return;
			}
			echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'The credentials for the FTP installation are missing. Please try to manual install the DLM Pro extension from your account at %sdownload-monitor.com%s.', 'download-monitor' ), '<a href="https://download-monitor.com/my-account" target="_blank">', '</a>' ) . '</p></div>';
		}
	}

	DLM_PRO_Install::get_instance();
}