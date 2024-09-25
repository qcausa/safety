<?php

/*
  Plugin Name: QCAUSA React Framework
  Version: 1.0
  Author: Brad
  Author URI: https://github.com/LearnWebCode
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function unadorned_announcement_bar_menu() {
  add_menu_page(
      __( 'Custom React Framework', 'unadorned-announcement-bar' ),
      __( 'Custom React Framework', 'unadorned-announcement-bar' ),
      'manage_options',
      'unadorned-announcement-bar',
      'unadorned_announcement_bar_settings_page_html',
      'dashicons-admin-generic',
      30
  );

  add_submenu_page(
      'unadorned-announcement-bar',
      __( 'Orders', 'unadorned-announcement-bar' ),
      __( 'Orders', 'unadorned-announcement-bar' ),
      'manage_options',
      'unadorned-announcement-bar-orders',
      'unadorned_announcement_bar_orders_page_html'
  );
}

function unadorned_announcement_bar_settings_page_html() {
  echo '<div class="wrap" id="unadorned-announcement-bar-settings">';
  echo '<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">';
  echo '<div class="spinner is-active" style="float: none; width: 100px; height: 100px; padding: 10px;"></div>';
  echo '<span class="screen-reader-text">' . esc_html__( 'Loading…', 'unadorned-announcement-bar' ) . '</span>';
  echo '</div></div>';
}

function unadorned_announcement_bar_orders_page_html() {
  echo '<div class="wrap" id="unadorned-announcement-bar-orders">';
  echo '<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">';
  echo '<div class="spinner is-active" style="float: none; width: 100px; height: 100px; padding: 10px;"></div>';
  echo '<span class="screen-reader-text">' . esc_html__( 'Loading…', 'unadorned-announcement-bar' ) . '</span>';
  echo '</div></div>';
}

add_action( 'admin_menu', 'unadorned_announcement_bar_menu' );


function unadorned_announcement_bar_enqueue_admin_scripts( $hook ) {
    // Check if we're on either the main settings page or the orders page
    if ( 'toplevel_page_unadorned-announcement-bar' !== $hook && 'custom-react-framework_page_unadorned-announcement-bar-orders' !== $hook ) {
        return;
    }

    $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = include $asset_file;

    wp_enqueue_script(
        'unadorned-announcement-bar-script',
        plugins_url( 'build/index.js', __FILE__ ),
        $asset['dependencies'],
        $asset['version'],
        array(
            'in_footer' => true,
        )
    );

    wp_localize_script('unadorned-announcement-bar-script', 'mypluginData', array(
        'restUrl' => esc_url_raw( rest_url() ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
    ));

    wp_enqueue_style( 'wp-components' );

    // Enqueue admin Tailwind styles
    wp_enqueue_style(
        'unadorned-announcement-bar-admin-style',
        plugins_url( 'build/style-index.css', __FILE__ ),
        array( 'wp-components' ), // Ensures this loads after wp-components
        filemtime( plugin_dir_path( __FILE__ ) . 'build/style-index.css' )
    );
}

add_action( 'admin_enqueue_scripts', 'unadorned_announcement_bar_enqueue_admin_scripts' );







function bradsboilerplateblocktailwindreactregister() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'bradsboilerplateblocktailwindreactregister' );


add_filter( 'rest_prepare_user', function( $response, $user, $request ) {
    if ( current_user_can( 'list_users' ) ) { // Check if the current user has the necessary permissions
        $response->data['email'] = $user->user_email; // Add the email to the response
        $response->data['user_registered'] = $user->user_registered;
    }

    return $response;
}, 10, 3 );


function unadorned_announcement_bar_settings() {
    // Set the default for page_id to null
    $default = array(
        'feature_1' => array(
            'page_id' => null, // Default is null
        ),
    );

    // Update the schema to allow null for page_id
    $schema  = array(
        'type'       => 'object',
        'properties' => array(
            'feature_1' => array(
                'type'       => 'object',
                'properties' => array(
                    'page_id' => array(
                        'type'    => ['integer', 'null'], // Accept integer or null
                    ),
                ),
            ),
        ),
    );

    // Register the setting with the updated default and schema
    register_setting(
        'options',
        'unadorned_announcement_bar',
        array(
            'type'         => 'object',
            'default'      => $default,
            'show_in_rest' => array(
                'schema' => $schema,
            ),
        )
    );
}

add_action( 'init', 'unadorned_announcement_bar_settings' );

function unadorned_announcement_bar_frontend_script() {
    $options = get_option('unadorned_announcement_bar');
    $feature_1_page_id = isset($options['feature_1']['page_id']) ? $options['feature_1']['page_id'] : null;

    if (is_page($feature_1_page_id)) {

        $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = include $asset_file;

    wp_enqueue_script(
        'unadorned-announcement-bar-frontend',
        plugins_url('build/frontend.js', __FILE__),
        $asset['dependencies'],
        $asset['version'],
        array(
        'in_footer' => true,
    )
    );

    wp_localize_script('unadorned-announcement-bar-frontend', 'mypluginData', array(
        'restUrl' => esc_url_raw(rest_url()),
        'nonce'   => wp_create_nonce('wp_rest'),
    ));

        // wp_enqueue_style(
        //     'unadorned-announcement-bar-frontend-style',
        //     plugins_url('build/frontend.css', __FILE__),
        //     array(),
        //     filemtime(plugin_dir_path(__FILE__) . 'build/frontend.css')
        // );
    }
}
add_action('wp_enqueue_scripts', 'unadorned_announcement_bar_frontend_script');

// function unadorned_announcement_bar_enqueue_wc_api() {
//     wp_enqueue_script('wc-api', plugins_url('build/wc-api.js', __FILE__), array('wp-api'), null, true);
//     wp_localize_script('wc-api', 'woocommerceApiSettings', array(
//         'root' => esc_url_raw(rest_url()),
//         'nonce' => wp_create_nonce('wp_rest')
//     ));
// }
// add_action('wp_enqueue_scripts', 'unadorned_announcement_bar_enqueue_wc_api');

function unadorned_announcement_bar_frontend_container($content) {
    $options = get_option('unadorned_announcement_bar');
    $feature_1_page_id = isset($options['feature_1']['page_id']) ? $options['feature_1']['page_id'] : null;

    if (is_page($feature_1_page_id)) {
        $component = '<div id="unadorned-announcement-bar-frontend-root"></div>';
        // $toaster = '<div id="unadorned-announcement-bar-toaster"></div>';
        return $component . $content;
    }

    return $content;
}
add_filter('the_content', 'unadorned_announcement_bar_frontend_container');

function unadorned_announcement_bar_add_toaster() {
    echo '<div id="unadorned-announcement-bar-toaster"></div>';
}
add_action('wp_footer', 'unadorned_announcement_bar_add_toaster');


// Add custom template to the page attributes dropdown.
function your_plugin_register_template( $post_templates ) {
    // Add your template to the list
    $post_templates['brads-boilerplate-block-plugin-tailwind/templates/custom-template.php'] = __('No Styles', 'your-plugin');
    return $post_templates;
}
add_filter( 'theme_page_templates', 'your_plugin_register_template' );




// Allow WordPress to recognize the custom template.
function your_plugin_load_template( $template ) {
    global $post;

    // If a custom template is set, load the template from the plugin
    if ( $post && get_page_template_slug( $post->ID ) === 'brads-boilerplate-block-plugin-tailwind/templates/custom-template.php' ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/custom-template.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'your_plugin_load_template' );


// Dequeue styles dynamically for the body content only
add_action('wp_enqueue_scripts', 'custom_deregister_body_styles', 100);

function custom_deregister_body_styles() {
    if (is_page_template('custom-template.php')) {
        // Get all enqueued styles
        global $wp_styles;

        // Dequeue all styles temporarily
        foreach ($wp_styles->queue as $handle) {
            wp_dequeue_style($handle);
        }

        // Re-enqueue styles in the header and footer
        add_action('wp_head', 'custom_reenqueue_header_styles', 999);
        add_action('wp_footer', 'custom_reenqueue_footer_styles', 999);
    }
}

function custom_reenqueue_header_styles() {
    global $wp_styles;

    // Re-enqueue all styles for header
    foreach ($wp_styles->queue as $handle) {
        wp_enqueue_style($handle);
    }
}

function custom_reenqueue_footer_styles() {
    global $wp_styles;

    // Re-enqueue all styles for footer
    foreach ($wp_styles->queue as $handle) {
        wp_enqueue_style($handle);
    }
}