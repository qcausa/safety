<?php

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

if ( ! function_exists( 'learndash_get_thumb_path' ) ) {

	/**
	 * Get featured image of certificate post
	 *
	 * @param  int 		$post_id
	 *
	 * @return string 	full image path
	 */
	function learndash_get_thumb_path( $post_id ) {
		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( $thumbnail_id ) {
			return wp_get_attachment_image_url($thumbnail_id, 'full');
		}
	}
}
