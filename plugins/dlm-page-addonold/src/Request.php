<?php

class DLM_PA_Request {

	/**
	 * Setup filters
	 */
	public function setup() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	/**
	 * add_query_vars function.
	 *
	 * @access public
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'download-tag';
		$vars[] = 'download-category';
		$vars[] = 'download-info';

		return $vars;
	}

	/**
	 * Listen for download page requests.
	 *
	 * @access public
	 * @return void
	 */
	public function parse_request() {
		global $wp;

		if ( ! empty( $_GET['download-tag'] ) ) {
			$wp->query_vars['download-tag'] = $_GET['download-tag'];
		}
		if ( ! empty( $_GET['download-category'] ) ) {
			$wp->query_vars['download-category'] = $_GET['download-category'];
		}
		if ( ! empty( $_GET['download-info'] ) ) {
			$wp->query_vars['download-info'] = $_GET['download-info'];
		}
	}
}
