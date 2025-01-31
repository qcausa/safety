<?php

/**
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages()
{
    /**
     * Makes child theme available for translation.
     * Translations can be added into the /languages/ directory.
     */

    // Translate text from the PARENT theme.
    load_theme_textdomain('buddyboss-theme', get_stylesheet_directory() . '/languages');

    // Translate text from the CHILD theme only.
    // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
    // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action('after_setup_theme', 'buddyboss_theme_child_languages');

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles()
{
    /**
     * Scripts and Styles loaded by the parent theme can be unloaded if needed
     * using wp_deregister_script or wp_deregister_style.
     *
     * See the WordPress Codex for more information about those functions:
     * http://codex.wordpress.org/Function_Reference/wp_deregister_script
     * http://codex.wordpress.org/Function_Reference/wp_deregister_style
     **/

    // Styles
    wp_enqueue_style('buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css');

    // Javascript
    wp_enqueue_script('buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js');
}
add_action('wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999);


/****************************** CUSTOM FUNCTIONS ******************************/

define('CHILD_THEME_INC', get_stylesheet_directory() . '/inc/');

/**
 * Include required files
 */


require_once CHILD_THEME_INC . 'woocommerce-customizations.php';
require_once CHILD_THEME_INC . 'buddypress-customizations.php';
require_once CHILD_THEME_INC . 'elementor-queries.php';
require_once CHILD_THEME_INC . 'shortcodes.php';

/**
 * Redirect to Latest Post in Category if 'latest' parameter is present
 */
function wpa_latest_in_category_redirect($request)
{
    if (isset($_GET['latest']) && isset($request->query_vars['category_name'])) {
        $latest = new WP_Query(array(
            'category_name'  => $request->query_vars['category_name'],
            'posts_per_page' => 1
        ));
        if ($latest->have_posts()) {
            wp_redirect(get_permalink($latest->post->ID));
            exit;
        }
    }
}
add_action('parse_request', 'wpa_latest_in_category_redirect');

/**
 * Rename Tribe Event Labels to "Campaign"
 */
add_filter('tribe_event_label_singular', function () {
    return 'Campaign';
});
add_filter('tribe_event_label_singular_lowercase', function () {
    return 'campaign';
});
add_filter('tribe_event_label_plural', function () {
    return 'Campaigns';
});
add_filter('tribe_event_label_plural_lowercase', function () {
    return 'campaigns';
});

/**
 * (Optional) Redirect Subadministrator to Home Page After Login
 */
# /*
function redirect_subadministrator_to_home($redirect_to, $request, $user)
{
    // Check if user is logged in and has the 'subadministrator' role
    if (isset($user->roles) && in_array('subadministrator', $user->roles)) {
        return home_url('/'); // Redirect to the frontend home page
    }

    // Default redirect for all other users
    return $redirect_to;
}
add_filter('login_redirect', 'redirect_subadministrator_to_home', 10, 3);
# */

/**
 * (Optional) Remove Admin Bar Items for Subadministrators
 */
# /*
function remove_admin_bar_items_for_subadministrator($wp_admin_bar)
{
    // Check if the user is logged in and has the 'subadministrator' role
    if (!current_user_can('subadministrator')) {
        return;
    }

    // List of admin bar nodes to remove
    $items_to_remove = array(
        'wp-logo',                  // WordPress logo
        // 'site-name',              // Site name link
        'updates',                  // Updates
        'comments',                 // Comments
        'customize',                // Customize
        'new-content',              // Add New
        'elementor_edit_page',      // Edit Page (Elementor)
        'bugfu-console-debugger',   // BugFu Console Debugger
        'tribe-events',             // Tribe Events
        'dsh-bar-top',              // User account menu
        'wpforms-menu',             // WPForms Menu
        'search-filter-debug',      // Search Filter Debug
    );

    // Remove each item
    foreach ($items_to_remove as $item) {
        $wp_admin_bar->remove_node($item);
    }
}
add_action('admin_bar_menu', 'remove_admin_bar_items_for_subadministrator', 999);
