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
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Modify the 'dlm_download' custom post type arguments.
 *
 * @param array $args The original arguments for the post type.
 * @param string $post_type The post type slug.
 * @return array Modified post type arguments.
 */
function modify_custom_post_type_args($args, $post_type)
{
    if ('dlm_download' === $post_type) {
        // Enable REST API and show in navigation menus
        $args['show_in_rest'] = true;
        $args['show_in_nav_menus'] = true;
        $args['public'] = true;
        $args['publicly_queryable'] = true;
        $args['show_ui'] = true;
        $args['capability_type'] = 'page';
        $args['query_var'] = true;
        $args['has_archive'] = true;
        $args['rewrite'] = array('slug' => 'downloads', 'with_front' => true, 'pages' => true, 'feeds' => true);

        // Add Elementor, Page Attributes, and Post Tags support
        $args['supports'] = array_merge(
            (array) $args['supports'],
            array('elementor', 'page-attributes', 'post-tags')
        );

        // Add taxonomies
        if (isset($args['taxonomies']) && is_array($args['taxonomies'])) {
            $args['taxonomies'] = array_merge(
                $args['taxonomies'],
                array('dlm_download_category', 'dlm_download_tag', 'post_tag')
            );
        } else {
            $args['taxonomies'] = array('dlm_download_category', 'dlm_download_tag', 'post_tag');
        }
    }
    return $args;
}
add_filter('register_post_type_args', 'modify_custom_post_type_args', 100, 2);

/**
 * Modify the 'dlm_download_tag' taxonomy arguments
 */
function modify_download_tag_args($args, $taxonomy)
{
    if ($taxonomy === 'dlm_download_tag') {
        $args['public'] = true;
        $args['publicly_queryable'] = true;
        $args['show_in_nav_menus'] = true;
        $args['show_in_rest'] = true;
        $args['show_admin_column'] = true;
        $args['hierarchical'] = false;
        $args['rewrite'] = array('slug' => 'download-tag');
    }
    return $args;
}
add_filter('register_taxonomy_args', 'modify_download_tag_args', 999, 2);

/**
 * Register post tags for downloads
 */
function register_download_post_tags()
{
    register_taxonomy_for_object_type('post_tag', 'dlm_download');
}
add_action('init', 'register_download_post_tags', 999);
