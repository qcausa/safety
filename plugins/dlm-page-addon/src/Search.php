<?php

class DLM_PA_Search {

	/**
	 * Setup support for PA search results
	 */
	public function setup() {
		add_filter( 'dlm_search_download_url', array( $this, 'filter_search_link' ), 11, 2 );
	}

	/**
	 * Filter the search URL
	 *
	 * @param string       $link
	 * @param DLM_Download $download
	 *
	 * @return string
	 */
	public function filter_search_link( $link, $download ) {
		return WP_DLM_Page_Addon::instance()->get_download_info_link( $download, intval( get_option( 'dlm_pa_search_results_page', 0 ) ), apply_filters( 'dlm_pa_direct_search_download', false, $download ) );
	}
}
