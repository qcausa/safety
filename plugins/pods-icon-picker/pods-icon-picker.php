<?php
/*
Plugin Name: Pods Icon Picker
Description: Adds a custom field type to Pods for selecting an icon associated with a post.
Version: 1.0.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants
define( 'PODS_ICON_PICKER_VERSION', '1.0.0' );
define( 'PODS_ICON_URL', plugin_dir_url( __FILE__ ) );
define( 'PODS_ICON_PICKER_DIR', plugin_dir_path( __FILE__ ) );


// Load the field class
require_once PODS_ICON_PICKER_DIR . 'includes/class-pods-icon-picker-field.php';

// Initialize the field
add_action( 'pods_init', function() {
    require_once PODS_ICON_PICKER_DIR . 'includes/class-pods-icon-picker-field.php';
    require_once plugin_dir_path( __FILE__ ) . 'classes/fields/icon-picker.php';
    new PodsField_Icon_Picker();
});



// Register the field type with the filter
add_filter( 'pods_api_field_types', function( $field_types ) {
    $field_types[] = 'icon_picker';
    return $field_types;
});

// Helper function to display the icon
function pods_get_icon( $post_id = null, $field_name = 'icon_picker' ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $icon_class = pods_field( $post_id, $field_name );

    if ( ! empty( $icon_class ) ) {
        echo '<i class="' . esc_attr( $icon_class ) . '"></i>';
    }
}
