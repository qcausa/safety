<?php

class DLM_PA_Rewrite {

	/**
	 * add_endpoint function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'download-tag', EP_PAGES );
		add_rewrite_endpoint( 'download-category', EP_PAGES );
		add_rewrite_endpoint( 'download-info', EP_PAGES );
	}

	/**
	 * Flushes rewrite rules. Only called once.
	 */
	public function flush() {
		flush_rewrite_rules();
	}

}