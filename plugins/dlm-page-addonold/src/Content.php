<?php

/**
 * DLM_PA_Content class that handles the content
 *
 * @since       4.2.1
 */
class DLM_PA_Content {

	/**
	 * Setup filters
	 */
	public function setup() {
		add_filter( 'the_content', array( $this, 'change_the_content' ) );
	}

	/**
	 * Change the post content
	 *
	 * @param  string  $content
	 *
	 * @return string
	 * @since 4.2.1
	 */
	public function change_the_content( $content ) {
		global $post, $wp;
		$persist = get_option( 'dlm_pa_persist_content', '' );

		if ( '1' !== $persist ) {
			return $content;
		}
		if ( is_main_query() && in_the_loop() && is_page() && strstr( $post->post_content, '[download_page' ) ) {
			if ( ! empty( $wp->query_vars['download-category'] ) || ! empty( $wp->query_vars['download-tag'] ) || ! empty( $wp->query_vars['download-info'] ) || ! empty( $_GET['download_search'] ) ) {
				$content = $this->find_shortcode( $content, 'download_page' );
			}
		}

		return $content;
	}

	/**
	 * Finds a specific shortcode in content
	 *
	 * @param  string  $content
	 *
	 * @return string $return
	 * @since 4.2.1
	 */
	private function find_shortcode( $content, $shortcode = 'download_page' ) {
		$pattern = get_shortcode_regex( array( $shortcode ) );
		$return  = '';
		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches )
		     && array_key_exists( 2, $matches )
		     && in_array( $shortcode, $matches[2] ) ) {
			// Shortcodes found, process them
			foreach ( $matches[0] as $match ) {
				// $match contains the full shortcode, including attributes
				$return .= htmlspecialchars( $match );
			}
		}

		return $return;
	}
}
