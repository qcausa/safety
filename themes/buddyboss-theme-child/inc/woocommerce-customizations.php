<?php

/**
 * WooCommerce Customizations
 *
 * @package BuddyBoss Child
 */

/**
 * Allow WooCommerce products and posts to use the same 'post_tag' taxonomy.
 */
function custom_share_tags_between_posts_and_products()
{
    // Unregister WooCommerce's default 'product_tag' taxonomy
    unregister_taxonomy('product_tag');

    // Register 'post_tag' taxonomy for WooCommerce products
    register_taxonomy_for_object_type('post_tag', 'product');
}
add_action('init', 'custom_share_tags_between_posts_and_products', 11);

/**
 * Register 'post_tag' taxonomy for WooCommerce products early.
 */
function custom_register_post_tag_for_products()
{
    global $wp_taxonomies;

    // Ensure 'post_tag' is associated with 'product'
    if (isset($wp_taxonomies['post_tag'])) {
        $wp_taxonomies['post_tag']->object_type[] = 'product';
        register_taxonomy_for_object_type('post_tag', 'product');
    }
}
add_action('after_setup_theme', 'custom_register_post_tag_for_products', 0);
