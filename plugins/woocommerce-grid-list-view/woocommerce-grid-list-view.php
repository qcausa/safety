<?php
/**
 * Plugin Name: WooCommerce Grid and List View Toggle
 * Description: Adds a grid and list view toggle to WooCommerce shop and archive pages.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: woocommerce-grid-list-toggle
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue CSS and JS
add_action( 'wp_enqueue_scripts', 'wc_grid_list_enqueue_assets' );
function wc_grid_list_enqueue_assets() {
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        wp_enqueue_style( 'wc-grid-list-styles', plugins_url( 'assets/css/grid-list.css', __FILE__ ) );
        wp_enqueue_script( 'wc-grid-list-script', plugins_url( 'assets/js/grid-list.js', __FILE__ ), [ 'jquery' ], '1.0', true );
    }
}

// Add Grid/List Toggle Buttons
add_action( 'woocommerce_before_shop_loop', 'wc_grid_list_toggle_buttons', 30 );
function wc_grid_list_toggle_buttons() {
    echo '<div class="wc-view-toggle">
            <button class="wc-toggle wc-grid-view active" data-view="grid">' . __( 'Grid View', 'woocommerce-grid-list-toggle' ) . '</button>
            <button class="wc-toggle wc-list-view" data-view="list">' . __( 'List View', 'woocommerce-grid-list-toggle' ) . '</button>
          </div>';
}

// Add List View Wrapper Class to Products
add_filter( 'body_class', 'wc_add_view_mode_class' );
function wc_add_view_mode_class( $classes ) {
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        $classes[] = 'wc-view-grid'; // Default view
    }
    return $classes;
}
