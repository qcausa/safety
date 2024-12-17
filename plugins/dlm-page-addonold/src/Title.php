<?php

class DLM_PA_Title {

	/**
	 * Setup filters
	 */
	public function setup() {
		add_filter( 'the_title', array( $this, 'change_the_title' ) );
	}

	/**
	 * Change the post title
	 *
	 * @param  string $title
	 *
	 * @return string
	 */
	public function change_the_title( $title ) {
		global $post, $wp, $wpdb;

		if ( is_main_query() && in_the_loop() && is_page() && strstr( $post->post_content, '[download_page' ) && $title == $post->post_title ) {

			if ( ! empty( $wp->query_vars['download-category'] ) ) {
				$term = get_term_by( 'slug', sanitize_title( $wp->query_vars['download-category'] ), 'dlm_download_category' );

				$title = '<a href="' . get_permalink() . '">' . $title . '</a>';

				if ( ! is_wp_error( $term ) ) {
					$titles[] = ' &gt; ' . $term->name . ' (' . $term->count . ')';
					while ( $term->parent > 0 ) {
						$term     = get_term_by( 'id', $term->parent, 'dlm_download_category' );
						$titles[] = ' &gt; <a href="' . WP_DLM_Page_Addon::instance()->get_category_link( $term ) . '">' . $term->name . '</a> (' . $term->count . ')';
					}
					$titles = array_reverse( $titles );
					$title  .= implode( '', $titles );
				}
			} elseif ( ! empty( $wp->query_vars['download-tag'] ) ) {
				$term = get_term_by( 'slug', sanitize_title( $wp->query_vars['download-tag'] ), 'dlm_download_tag' );

				$title = '<a href="' . get_permalink() . '">' . $title . '</a>';

				if ( ! is_wp_error( $term ) ) {
					$title .= ' &gt; ' . $term->name . ' (' . $term->count . ')';
				}
			} elseif ( ! empty( $wp->query_vars['download-info'] ) ) {
				$download_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download' AND post_status = 'publish';", sanitize_title( $wp->query_vars['download-info'] ) ) );

				try {
					$dlm_download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
					$title        = $dlm_download->get_title();
				} catch ( Exception $e ) {

				}

			} elseif ( ! empty( $_GET['download_search'] ) ) {
				$title = '<a href="' . get_permalink( WP_DLM_Page_Addon::instance()->get_page_id() ) . '">' . $title . '</a>';
				$title .= ' &gt; ' . sprintf( __( 'Searching for "%s"', 'dlm_page_addon' ), sanitize_text_field( $_GET['download_search'] ) );
			}
		}

		return $title;
	}

}