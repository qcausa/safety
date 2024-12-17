<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_Page_Addon_Modal
 * used to handle the modal functionality for the Page Addon extension.
 *
 * @since 4.2.0
 */
class DLM_Page_Addon_Modal {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'add_footer_scripts' ) );
		add_action( 'parse_request', array( $this, 'handler' ), 0 );
		add_action( 'wp_ajax_nopriv_dlm_pa_modal', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_dlm_pa_modal', array( $this, 'xhr_no_access_modal' ), 15 );

	}

	/**
	 * Add required scripts to footer.
	 *
	 * @return void
	 * @since 4.2.0
	 */
	public function add_footer_scripts() {
		global $dlm_page_addon;
		// Only add the script if the modal template exists.
		// Failsafe, in case the Modal template is non-existent, for example prior to DLM 4.9.0
		if ( ! class_exists( 'DLM_Constants' ) || ! defined( 'DLM_Constants::DLM_MODAL_TEMPLATE' ) ) {
			return;
		}
		?>
		<script>
			jQuery(document).on('dlm-xhr-modal-data', function (e, data, headers) {
				if ('undefined' != typeof headers['x-dlm-pa-redirect'] && 'undefined' != typeof headers['x-dlm-pa-dl-slug']) {
					data['action']               = 'dlm_pa_modal';
					data['dlm_modal_response']   = 'true';
					data['dlm_pa_download_slug'] = headers['x-dlm-pa-dl-slug'];
					data['dlm_pa_page_id']       = <?php echo absint( $dlm_page_addon->get_page_id() ); ?>;
				}
			});
		</script>
		<?php
	}

	/**
	 * Handles download-info query arg.
	 *
	 * @return void
	 * @since 4.2.0
	 */
	public function handler() {

		// If this is not a DLM XHR request than we should do nothing.
		if ( ! isset( $_SERVER['HTTP_DLM_XHR_REQUEST'] ) || 'dlm_XMLHttpRequest' !== $_SERVER['HTTP_DLM_XHR_REQUEST'] || ! defined( 'DLM_DOING_XHR' ) || ! DLM_DOING_XHR ) {
			return;
		}
		global $wp, $wpdb;

		$restriction_type = 'dlm-page-addon-modal';

		if ( ! empty( $wp->query_vars['download-info'] ) ) {
			$slug        = $wp->query_vars['download-info'];
			$download_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download' AND post_status = 'publish';", sanitize_title( $slug ) ) );

			try {
				$dlm_download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

			} catch ( Exception $e ) {

			}
			
			$no_access_modal = apply_filters( 'do_dlm_xhr_access_modal', true, $dlm_download );
			if ( $no_access_modal ) {
				header_remove( 'X-dlm-no-waypoints' );
			}
			header( 'X-DLM-PA-redirect: true' );
			header( 'X-DLM-No-Access: true' );
			header( 'X-DLM-No-Access-Modal: ' . $no_access_modal );
			header( 'X-DLM-No-Access-Restriction: ' . $restriction_type );
			header( 'X-DLM-Nonce: ' . wp_create_nonce( 'dlm_ajax_nonce' ) );
			header( 'X-DLM-PA-DL-slug: ' . sanitize_title( $slug ) );
			header( 'X-DLM-Download-ID: ' . absint( $download_id ) );

			exit;
		}
	}

	/**
	 * Renders the modal contents.
	 *
	 * @return void
	 * @since 4.2.0
	 */
	public function xhr_no_access_modal() {
		// Check nonce.
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );
		if ( isset( $_POST['download_id'] ) && isset( $_POST['dlm_pa_download_slug'] ) ) {
			// Scripts and styles already enqueued in the shortcode action.
			$title   = get_the_title( absint( $_POST['download_id'] ) );
			$content = $this->modal_content( absint( $_POST['download_id'] ), $_POST['dlm_pa_download_slug'] );
			DLM_Modal::display_modal_template(
				array(
					'title'    => $title,
					'content'  => '<div id="dlm_page_addon_display">' . $content . '</div>',
					'tailwind' => true
				)
			);
		}

		wp_die();
	}

	/**
	 * The modal content for the Email Lock extension.
	 *
	 * @param int $download_id The download ID.
	 *
	 * @return false|string
	 * @since 4.2.0
	 */
	private function modal_content( $download_id, $slug ) {

		// Action to allow the addition of extra scripts and code related to the shortcode.

		global $dlm_page_addon;
		global $wpdb;

		$dlm_page_addon->set_page_id( isset( $_POST['dlm_pa_page_id'] ) ? absint( $_POST['dlm_pa_page_id'] ) : 0 );
		// Let's get the download information that we need to output.
		ob_start();

		$template_handler = new DLM_Template_Handler();

		try {

			// fetch download
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

			// fitler download
			$download = apply_filters( 'dlm_page_addon_download_info', $download );

			$template_handler->get_template_part( 'content-download', 'pa-single-modal', $dlm_page_addon->plugin_path() . 'templates/', array( 'dlm_download' => $download ) );
		} catch ( Exception $exception ) {
			$template_handler->get_template_part( 'no-downloads-found', '', $dlm_page_addon->plugin_path() . 'templates/' );
		}

		return ob_get_clean();
	}
}

new DLM_Page_Addon_Modal();
