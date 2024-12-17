<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'DLM_Extension_Notices' ) && class_exists( 'DLM_Product_Manager' ) ) {


	/**
	 * Class DLM_Extension_Notices
	 *
	 * @since 4.0.1
	 */
	class DLM_Extension_Notices {

		/**
		 * Holds the class object.
		 *
		 * @since 4.0.1
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * DLM_Upsells constructor.
		 *
		 * @since 4.0.1
		 */
		public function __construct() {

			add_action( 'admin_notices', array( $this, 'display_admin_notices' ), 8 );

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Extension_Notices object.
		 *
		 * @since 4.0.1
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Extension_Notices ) ) {
				self::$instance = new DLM_Extension_Notices();
			}

			return self::$instance;

		}

		/**
		 * Display admin notices.
		 *
		 * @return void
		 */
		public function display_admin_notices() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$products = DLM_Product_Manager::get()->get_products();

			$extensions            = '';
			$nr                    = 0;
			$expired_licenses      = array();
			$expired_licenses_text = '';
			// loop products.
			if ( count( $products ) > 0 ) {
				foreach ( $products as $product ) {

					// check if product is correctly activated.
					if ( true !== $product->get_license()->is_active() ) {
						$nr ++;

						$notice_id = 'extension-' . esc_attr( $product->get_product_id() );

						$extensions .= '<a href="' . esc_url( $product->get_tracking_url( 'activate-license-notice' ) ) . '" target="_blank">' . esc_html( $product->get_product_name() ) . '</a>, ';
						if ( '' !== $product->get_license()->get_key() && 'expired' === $product->get_license()->get_license_status() ) {
							if ( ! isset( $expired_licenses[ $product->get_license()->get_key() ] ) ) {
								$expired_licenses[ $product->get_license()->get_key() ] = $product->get_license()->get_email();
							}
						}
					}
				}
			}

			$expired_licenses_text .= '<a href="https://www.download-monitor.com/my-account" target="_blank">' . esc_html( 'Renew your license' ) . '</a> ';

			$user_license   = get_option( 'dlm_master_license', false );
			$license_status = 'inactive';
			if ( $user_license ) {
				$user_license = json_decode( $user_license, true );
				if ( isset( $user_license['license_status'] ) && '' !== trim( $user_license['license_status'] ) && isset( $user_license['license_key'] ) && '' !== trim( $user_license['license_key'] ) ) {
					$license_status = $user_license['license_status'];
				}
			} else if ( ! empty( $expired_licenses ) ) {
				$license_status = 'expired';
			}
			$message_head = '<b>Warning!</b> Your license for: ';
			$message_end  = ' is ' . $license_status . ' which means you\'re missing out on updates and support!';
			$link         = admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' );
			if ( class_exists( 'DLM_PRO' ) ) {
				$link = admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=license' );
			}
			$action = '<a href="' . esc_url( $link ) . '">Activate your license</a>.';

			if ( 'expired' === $license_status ) {
				$action = $expired_licenses_text . ' or, if you already renewed, please <a href="' . esc_url( $link ) . '">activate the license</a>.';
			}

			$message_end .= ' ' . $action;

			$admin_helper = DLM_Admin_Helper::get_instance();
			if ( method_exists( $admin_helper, 'check_if_dlm_page' ) ) {
				$is_dlm_page = DLM_Admin_Helper::check_if_dlm_page();
			} else {
				$is_dlm_page = ( isset( $_GET['post_type'] ) && 'dlm_download' == $_GET['post_type'] );
			}

			if ( ! empty( $extensions ) && ( 1 != get_option( 'dlm_hide_notice-' . $notice_id, 0 ) && $is_dlm_page ) ) {

				?>
				<div class="notice notice-warning dlm-notice is-dismissible"
				     id="<?php echo esc_attr( $notice_id ); ?>"
				     data-nonce="<?php echo esc_attr( wp_create_nonce( 'dlm_hide_notice-' . $notice_id ) ); ?>">
					<p><?php echo wp_kses_post( $message_head . $extensions . $message_end ); ?></p>
				</div>
				<?php
			}

		}

	}

	DLM_Extension_Notices::get_instance();
}
