<?php
/**
 * Plugin Name: Download Monitor - Elementor
 * Plugin URI: https://qcausa.com/
 * Description: A custom plugin to modify the post type arguments for 'dlm_download', including Elementor support, taxonomies, and Gutenberg enablement.
 * Version: 1.0
 * Author: QCAUSA
 * Author URI: https://qcausa.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modify the 'dlm_download' custom post type arguments.
 *
 * @param array $args The original arguments for the post type.
 * @param string $post_type The post type slug.
 * @return array Modified post type arguments.
 */
function modify_custom_post_type_args( $args, $post_type ) {
    if ( 'dlm_download' === $post_type ) { 
        // Enable REST API and show in navigation menus
        $args['show_in_rest'] = true; 
        $args['show_in_nav_menus'] = true;
        $args['public'] = true;

        // Add Elementor and Page Attributes support
        $args['supports'] = array_merge( (array) $args['supports'], array( 'elementor', 'page-attributes' ) );

        // Add 'dlm_download_category' to taxonomies
        if ( isset( $args['taxonomies'] ) && is_array( $args['taxonomies'] ) ) {
            $args['taxonomies'][] = 'dlm_download_category';
        } else {
            $args['taxonomies'] = array( 'dlm_download_category' );
        }
    }
    return $args;
}
add_filter( 'register_post_type_args', 'modify_custom_post_type_args', 10, 2 );
