<?php

class DLM_PA_Settings {

	/**
	 * setup
	 */
	public function setup() {
		add_filter( 'dlm_settings', array( $this, 'add_settings' ) );
		add_action( 'update_option_dlm_pa_search_results_page', array( $this, 'pa_page_shortcode_to_page' ), 20, 2 );
		$this->register_lazy_load_callbacks();
	}

	/**
	 * Add settings to DLM settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_settings( $settings ) {

		$settings['advanced']['sections']['page_setup']['fields'][] = array(
			'name'     => 'dlm_pa_search_results_page',
			'std'      => '',
			'label'    => __( 'Search -> Page Addon Page', 'dlm-page-addon' ),
			'cb_label' => __( 'Enable', 'dlm-page-addon' ),
			'desc'     => sprintf( __( 'Select a page to have downloads in <strong>WordPress</strong> search results link to your Page Addon page. Note that this page should have the %s shortcode. This is dependant on the %s setting.', 'dlm-page-addon' ), "<code>[download_page]</code>", "<code>Include in Search</code>" ),
			'type'     => 'lazy_select',
			'options'  => array()
		);

		$settings['advanced']['sections']['page_setup']['fields'][] = array(
			'name'     => 'dlm_pa_persist_content',
			'std'      => '',
			'label'    => __( 'Hide Page Content', 'dlm-page-addon' ),
			'cb_label' => '',
			'desc'     => sprintf( __( 'Hides the content present on the page containing the %s shortcode on the download\'s info/category/tags/search pages.', 'dlm-page-addon' ), "<code>[download_page]</code>" ),
			'type'     => 'checkbox',
			'default'  => '1',
		);

		return $settings;
	}

	/**
	 * Register lazy load setting fields callbacks
	 */
	public function register_lazy_load_callbacks() {
		add_filter( 'dlm_settings_lazy_select_dlm_pa_search_results_page', array( $this, 'lazy_select_dlm_pa_search_results_page' ) );
	}

	/**
	 * Fetch and returns pages on lazy select for dlm_no_access_page option
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function lazy_select_dlm_pa_search_results_page( $options ) {
		return $this->get_pages();
	}

	/**
	 * Return pages with ID => Page title format
	 *
	 * @return array
	 */
	private function get_pages() {

		// pages
		$pages = array( array( 'key' => 0, 'lbl' => __( 'No Page / Disable functionality', 'download-monitor' ) ) );

		// get pages from db
		$db_pages = get_pages();

		// check and loop
		if ( count( $db_pages ) > 0 ) {
			foreach ( $db_pages as $db_page ) {
				$pages[] = array( 'key' => $db_page->ID, 'lbl' => $db_page->post_title );
			}
		}

		// return pages
		return $pages;
	}


	/**
	 * Add [download_page] shortcode to the selected page if it does not have it.
	 *
	 * @param string $old
	 *
	 * @param string $new
	 *
	 * @return void
	 *
	 * @since 4.2.0
	 */
	public function pa_page_shortcode_to_page( $old, $new ) {

		$page_id = absint( $new );

		// 1. Get the unformatted post(page) content.
		$page = get_post( $page_id );
		// 2. Just checking to be sure we got content.
		if ( ! isset( $page->post_content ) ) {
			return;
		}
		// 3. Search the content for the existance of our [download_page] shortcode.
		if ( false !== strpos( $page->post_content, '[download_page]' ) ) {
			// The page has the no access shortcode, return;
			return;
		}
		// 4. If we got here it means we need to add our shortcode to the page's content.
		$page->post_content .= '[download_page]';
		// 5. Finally, we update the post.
		wp_update_post( $page );
	}
}
