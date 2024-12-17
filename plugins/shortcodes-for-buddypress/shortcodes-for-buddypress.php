<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wbcomdesigns.com/plugins
 * @since             1.0.0
 * @package           Shortcodes_For_Buddypress
 *
 * @wordpress-plugin
 * Plugin Name:       Wbcom Designs – Shortcodes & Elementor Widgets For BuddyPress
 * Plugin URI:        https://github.com/wbcomdesigns/shortcodes-for-buddypress
 * Description:       This plugin will add an extended feature to BuddyPress that will add a Shortcode for Listing Activity Streams, Members, and Groups on any post/page on the website. It will offer the same features as you are getting on BuddyPress-mapped component pages.
 * Version:           2.8.0
 * Author:            Wbcom Designs
 * Author URI:        https://wbcomdesigns.com/plugins
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shortcodes-for-buddypress
 * Domain Path:       /languages
 */
// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Functionality to activate any one of Shortcode for BuddyPress and Shortcodes for BuddyPress Pro if both exists together.
 * 
 * @since 1.0.0
 */
if ( in_array( 'shortcode-for-buddypress-pro/shortcodes-for-buddypress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	add_action(
		'admin_notices',
		function() {
			$bp_shortcode     = esc_html__( 'Wbcom Designs – Shortcodes & Elementor Widgets For BuddyPress', 'shortcodes-for-buddypress' );
			$bp_shortcode_pro = esc_html__( 'Wbcom Designs - Shortcode For BuddyPress Pro', 'shortcodes-for-buddypress' );
			echo '<div class="error"><p>';
			/* translators: %s: */
			echo sprintf( esc_html__( '%1$s and %2$s will not activate togather. Please deactivate %1$s', 'shortcodes-for-buddypress' ), '<strong>' . esc_html( $bp_shortcode_pro ) . '</strong>', '<strong>' . esc_html( $bp_shortcode ) . '</strong>' );
			echo '</p></div>';
		}
	);
	deactivate_plugins( 'shortcodes-for-buddypress/shortcodes-for-buddypress.php' );
	return false;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'SHORTCODES_FOR_BUDDYPRESS_VERSION' ) ) {
	define( 'SHORTCODES_FOR_BUDDYPRESS_VERSION', '2.8.0' );
}

if ( ! defined( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_DIR' ) ) {
	define( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_URL' ) ) {
	define( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
}
if ( ! defined( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_BASENAME' ) ) {
	define( 'SHORTCODES_FOR_BUDDYPRESS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * This function will get triggered when plugin gets activated.
 * In this function we are including the plugin activator file.
 * Plugin activator file will contain all the functionalities that were needed at the time of plugin activation.
 *  
 * @since 1.0.0
 * @return void 
 */
function shortcodes_for_buddypress_activate() {
	
	// Complete path to the plugin activator file.
	$activator_file_path  = plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-for-buddypress-activator.php';
	
	// Checking if the activator file exists.
	if(file_exists($activator_file_path)){
		// Including activator file if it exists.
		require_once $activator_file_path;
		// Checking if activator class exits.
		if(class_exists('Shortcodes_For_Buddypress_Activator')){
			// Call to activate function if the activator file exists.
			Shortcodes_For_Buddypress_Activator::activate();
		}
	}	
}
// Registering activation hook for the plugin.
register_activation_hook( __FILE__, 'shortcodes_for_buddypress_activate' );

/**
 * This function will get triggered when plugin gets deactivated.
 * In this function we are including the plugin deactivator file.
 * Plugin deactivator file will contain all the functionalities that were needed at the time of plugin deactivation.
 * Including clearing unnecessary data reagrding postmeta, termmeta and usermeta.
 * 
 * @since 1.0.0
 * @return void 
 */
function shortcodes_for_buddypress_deactivate() {
	
	// Complete path to the plugin deactivator file.
	$deactivator_file_path  =  plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-for-buddypress-deactivator.php';
	
	// Checks if deactivator file exists.
	if(file_exists($deactivator_file_path)){
		// Including file if file exists.
		require_once $deactivator_file_path;
		// Checking if deactivator class exists.
		if(class_exists('Shortcodes_For_Buddypress_Deactivator')){

			// Call to the deactivate function that contains all the functionality while plugin deactivation.
			Shortcodes_For_Buddypress_Deactivator::deactivate();
		}
	}
}
//Registering deactivation hook for the plugin.
register_deactivation_hook( __FILE__, 'shortcodes_for_buddypress_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-for-buddypress.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

require_once __DIR__ . '/vendor/autoload.php';
HardG\BuddyPress120URLPolyfills\Loader::init();

/**
 * This function will deactivate the Shortcodes_for_Buddypress core plugin if Shortcodes_for_Buddypress Pro plugin is activated.
 * This was done to avoid conflicts between the functionalities of both the plugins.
 * 
 * If Shortcodes_for_Buddypress Pro plugin was not found it will continue the execution of the core plugin.
 *  
 * @since  1.0.0
 * @return void
 */
function shortcodes_for_buddypress_run() {
	//Checks if the pro plugin is installed and activated.
	if ( in_array( 'shortcode-for-buddypress-pro/shortcodes-for-buddypress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		//Deactivates the core plugin if pro plugin is activated.
		deactivate_plugins( 'shortcodes-for-buddypress/shortcodes-for-buddypress.php' );
	} else {
		//Begins execution of the current plugin.
		$plugin = new Shortcodes_For_Buddypress();
		$plugin->run();
	}

}
shortcodes_for_buddypress_run();


/**
 * 
 * This function verifies whether BuddyPress or BuddyBoss is active on the site. If neither is active,
 * it deactivates the Shortcodes for BuddyPress plugin and displays an admin notice informing the user
 * that BuddyPress or BuddyBoss is required. The function only runs in the admin area to avoid unnecessary
 * checks on the frontend.
 *
 * The function uses `deactivate_plugins()` conditionally, ensuring deactivation only when neither BuddyPress
 * nor BuddyBoss is present. It optimizes the plugin status checks by using `is_plugin_active()` and 
 * `is_plugin_active_for_network()` for better performance, particularly on sites with large databases.
 * 
 * @since 1.0.0
 * @return void 
 */
if ( ! function_exists( 'shortcodes_for_buddypress_requires_buddypress' ) ) {
	function shortcodes_for_buddypress_requires_buddypress() {
		
		// Checks if the function is called from the admin area.
		if ( ! is_admin() ) {
			return;
		}
	
		// Load the plugin.php file if the necessary functions are not already available.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
	
		// Check if BuddyPress or BuddyBoss is active either on the network or the current site.
		$is_buddypress_active = class_exists( 'BuddyPress' ) && (
			is_plugin_active( 'buddypress/bp-loader.php' ) || 
			is_plugin_active( 'buddyboss-platform/bp-loader.php' ) || 
			is_plugin_active_for_network( 'buddypress/bp-loader.php' ) || 
			is_plugin_active_for_network( 'buddyboss-platform/bp-loader.php' )
		);
	
		// If neither BuddyPress nor BuddyBoss is active, deactivate the plugin and display an admin notice.
		if ( ! $is_buddypress_active ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
	
			// Add an admin notice to inform the user about the missing dependency.
			add_action( 'admin_notices', 'shortcodes_for_buddypress_required_plugin_admin_notice' );
	
			// Clear the activation flag if it was set during activation.
			$activate = filter_input( INPUT_GET, 'activate' );
			if ( null !== $activate ) {
				unset( $activate );
			}
		}
	}

	add_action( 'admin_init', 'shortcodes_for_buddypress_requires_buddypress', 20 );
}

/**
 * Handles the redirection to the plugin settings page after activation.
 *
 * This function is triggered when the plugin is activated. It checks if BuddyPress
 * (or BuddyBoss) is active and then redirects the user to the plugin's settings page.
 * The redirection only occurs in the admin area and is performed using `wp_safe_redirect()`
 * for added security. All input parameters are sanitized to ensure safe processing.
 *
 * @since 1.0.0
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 * @return void
 */
function shortcodes_for_buddypress_activation_redirect_settings( $plugin ) {
	
	// Ensures that the function only runs in the admin area.
	 if ( ! is_admin() ) {
        return;
    }

    // Sanitize the plugin parameter.
    $plugin = sanitize_text_field( $plugin );

    // Verify if the activated plugin matches the current plugin and BuddyPress is active.
    if ( plugin_basename( __FILE__ ) === $plugin && class_exists( 'BuddyPress' ) ) {
        // Sanitize the $_REQUEST parameters to prevent any potential security issues.
        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        $requested_plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';

        // Check if the plugin was activated via the WordPress Plugins page.
        if ( 'activate' === $action && $requested_plugin === $plugin ) {
            // Safely redirect to the plugin settings page.
            wp_safe_redirect( esc_url( admin_url( 'admin.php?page=shortcodes-for-buddypress' ) ) );
            exit;
        }
    }

    // Set a transient if the plugin was newly activated for a potential redirection.
    if ( $plugin === $requested_plugin && class_exists( 'BuddyPress' ) ) {
        if ( 'activate-plugin' === $action ) {
            set_transient( '_bp_shortcode_is_new_install', true, 30 );
        }
    }
}
// Hook into 'activated_plugin' to handle redirection after plugin activation.
add_action( 'activated_plugin', 'shortcodes_for_buddypress_activation_redirect_settings' );

/**
 * 
 * This function is triggered after the plugin is activated and ensures that the user 
 * is redirected to the Shortcodes for BuddyPress settings page if the plugin was newly 
 * installed. The redirection only occurs in the admin area and uses `wp_safe_redirect()` 
 * for security.
 *
 * @since 1.0.0
 * @return void
 */
function shortcodes_for_buddypress_do_activation_redirect() {

	// Validating if function is called from admin area.
	if(! is_admin()){
		return;
	}

	// Check if the transient for new installation is set.
	if ( get_transient( '_bp_shortcode_is_new_install' ) ) {
		// Deleting the already set transient to avoid repeated redirections.
		delete_transient( '_bp_shortcode_is_new_install' );
		
		// Redirecting to the setting page of the plugin.
		wp_safe_redirect( esc_url(admin_url( 'admin.php?page=shortcodes-for-buddypress' )) );
		exit;
	}
}
// Hook to admin_init.
add_action( 'admin_init', 'shortcodes_for_buddypress_do_activation_redirect' );

/**
 * This function is triggered in the admin area when BuddyPress or BuddyBoss is required
 * but not active. It outputs a message to the admin dashboard, informing the user that 
 * the Shortcodes for BuddyPress plugin requires BuddyPress or BuddyBoss to function correctly.
 * The notice provides a link to install BuddyPress if it is missing.
 *
 * The function uses `wp_kses_post()` to ensure safe HTML rendering and `esc_html()` for 
 * dynamic text strings, preventing potential XSS issues. The logic is optimized to ensure 
 * the notice is only displayed when necessary, reducing load on large databases.
 *
 * @since 2.2.0
 * @return void
 */
if ( ! function_exists( 'shortcodes_for_buddypress_required_plugin_admin_notice' ) ) {
	function shortcodes_for_buddypress_required_plugin_admin_notice() {
		// Check if function is called by admin area.
		if ( ! is_admin() ) {
			return;
		}
	
		// Load the plugin.php file if the function is not already available.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
	
		// Check if BuddyPress or BuddyBoss is active.
		$is_buddypress_active = class_exists( 'BuddyPress' ) && (
			is_plugin_active( 'buddypress/bp-loader.php' ) ||
			is_plugin_active( 'buddyboss-platform/bp-loader.php' ) ||
			is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ||
			is_plugin_active_for_network( 'buddyboss-platform/bp-loader.php' )
		);
	
		// Only display the notice if BuddyPress or BuddyBoss is not active.
		if ( ! $is_buddypress_active ) {
			$plugin_name = esc_html__( 'Shortcodes For BuddyPress', 'shortcodes-for-buddypress' );
			$required_plugin = esc_html__( 'BuddyPress', 'shortcodes-for-buddypress' );
			$install_link = '<a href="' . esc_url( admin_url( 'plugin-install.php?s=BuddyPress&tab=search&type=term' ) ) . '">' . esc_html__( 'Install BuddyPress', 'shortcodes-for-buddypress' ) . '</a>';
			
			$message = sprintf(
				/* translators: 1: Shortcodes For BuddyPress, 2: BuddyPress */
				esc_html__( '%1$s is ineffective as it requires %2$s to be installed and active. Please %3$s.', 'shortcodes-for-buddypress' ),
				'<strong>' . esc_html( $plugin_name ) . '</strong>',
				'<strong>' . esc_html( $required_plugin ) . '</strong>',
				$install_link
			);
	
			// Output the message as a WordPress admin notice with safe HTML rendering.
			echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}
	}
}


/**
 * Registers the 'BuddyPress Widgets' category in Elementor.
 *
 * This function adds a custom category to the Elementor page builder, specifically for 
 * widgets related to BuddyPress. It ensures that the 'BuddyPress Widgets' category is available 
 * for organizing widgets that are related to BuddyPress features.
 * 
 * It is optimized to execute only when Elementor is active, minimizing unnecessary 
 * processing on large sites.
 *
 * @since 2.2.0
 * @param object $elements_manager is the instance of the Elementor elements manager class.
 * @return void
 */
if ( ! function_exists( 'shortcodes_for_buddypress_categories_registered' ) ) {
	function shortcodes_for_buddypress_categories_registered( $elements_manager ) {

		if(class_exists('Elements_Manager')){
			$elements_manager->add_category(
				'buddypress-widgets',
				array(
					'title' => esc_html__('BuddyPress Widgets', 'shortcodes-for-buddypress'),
					'icon'  => 'fa fa-plug',
				)
			);
		}
	}
	// Action hook to register custom category into the Elementor.
	add_action( 'elementor/elements/categories_registered', 'shortcodes_for_buddypress_categories_registered' );
}

/** 
 * This function includes and registers custom Elementor widgets for BuddyPress features,
 * making them available for use within the Elementor page builder. It conditionally includes 
 * the required widget files to prevent multiple inclusions, ensuring they are only included 
 * if they exist, thus avoiding potential errors.
 * 
 * @since 1.0.0
 * @return void
 */
if ( ! function_exists( 'shortcodes_for_buddypress_widgets_registered' ) ) {

	function shortcodes_for_buddypress_widgets_registered() {
		
		// Array of widgets file path.
		$widgets = array(
			'buddypress-shortcodes-element.php',
			'buddypress-members-element.php',
			'buddypress-groups-element.php',
		);

		if(is_array($widgets) && !empty($widgets)){
			foreach($widgets as $widget){
				$widget_path = plugin_dir_path( __FILE__ ) . $widget;
				
				// Checking if widget file exists.
				if ( file_exists( $widget_path ) ) {

					// Including widget file.
					require_once $widget_path;
				}
			}
		}
	}

	// Hook to register widgets into Elementor.
	add_action( 'elementor/widgets/widgets_registered', 'shortcodes_for_buddypress_widgets_registered', 15 );

}

/**
 * Processes and formats BuddyPress comments for activity streams.
 *
 * This function iterates over a list of comment data and processes each comment, 
 * preparing it for use in a BuddyPress activity stream. It extracts and sanitizes 
 * user information, comment metadata, and recursively handles nested comments.
 * The function is optimized for large sites by limiting recursion depth to avoid
 * memory exhaustion and to handle large data sets gracefully.
 *
 * @since 2.2.0
 * @param array $comments      Array of comments to process, each containing comment metadata.
 * @param int   $max_depth     Maximum depth to iterate through nested comments (default is 5).
 * @param int   $current_depth The current recursion depth (default is 1).
 * @return array $comments     Array of sub comments/replies on the main comment of the activity.
 * 
 */
function shortcodes_for_buddypress_iterate_comments( $comments, $max_depth = 5, $current_depth = 1 ) {
	$children = array();
		
	if(is_array($comments) && !empty($comments)){
		foreach ( $comments as $comment ) {
			$user_id                         = intval( $comment['user_id'] );
			$userdata                        = get_userdata( $user_id );
			
			// Continue next loop execution if userdata is empty.
			if(! $userdata ){
				continue;
			}
	
			$user_login                      = sanitize_text_field($userdata->data->user_login);
			$user_email                      = sanitize_email($userdata->data->user_email);
			$user_nicename                   = sanitize_text_field($userdata->data->user_nicename);
				
			$primary_link                    = esc_url(get_site_url() . '/member/'. $user_login);
			$comment_id                      = intval($comment['id']);
			$component                       = sanitize_text_field($comment['component']);
			$comment_type                    = sanitize_text_field($comment['type']);
			$action                          = sanitize_text_field($comment['title']);
			$content                         = wp_kses_post($comment['content']['rendered']);
			$item_id                         = intval($comment['primary_item_id']);
			$secondary_item_id               = intval($comment['secondary_item_id']);
			$date_gmt                        = sanitize_text_field($comment['date_gmt']);
			
			$obj_comment                     = new stdClass();

			$obj_comment->id                 = $comment_id;
			$obj_comment->user_id            = $user_id;
			$obj_comment->component          = $component;
			$obj_comment->type               = $comment_type;
			$obj_comment->action             = $action;
			$obj_comment->content            = $content;
			$obj_comment->primary_link       = $primary_link;
			$obj_comment->item_id            = $item_id;
			$obj_comment->secondary_item_id  = $secondary_item_id;
			$obj_comment->date_recorded      = strtotime($date_gmt);
			$obj_comment->user_email         = $user_email;
			$obj_comment->user_nicename      = $user_nicename;
			$obj_comment->user_login         = $user_login;
			$obj_comment->display_name       = $user_nicename;
			$obj_comment->user_fullname      = $user_nicename;
			$obj_comment->children           = array();
			$obj_comment->depth              = $current_depth;

			// Process nested comments if the current depth is less than the max depth.
			if ( isset( $comment['comments'] ) && is_array( $comment['comments'] ) && $current_depth < $max_depth ) {
				$obj_comment->children = shortcodes_for_buddypress_iterate_comments( $comment['comments'], $max_depth, $current_depth + 1 );
			}
			// Add the processed comment to the children array.
			$children[ $comment_id ] = $obj_comment;
		}
		
		return $children;
	}

}


/**
 * Adds custom classes to the BuddyPress groups loop.
 *
 * This function adjusts the classes applied to the BuddyPress groups loop based on user preferences
 * and customizer settings. It retrieves the layout preferences for the groups loop and applies 
 * appropriate grid classes to adjust the display of groups. This helps provide a customized 
 * layout experience for BuddyPress groups within themes that support BuddyPress.
 *
 * It is optimized to ensure performance with large groups by directly retrieving the necessary
 * layout preferences and applying only the required classes. The function uses `esc_html()` 
 * to ensure that all output strings are safely rendered.
 *
 * @since 2.2.0
 * @param array  $classes   Array of existing classes for the groups loop.
 * @param string $component The current BuddyPress component, in this case 'groups'.
 * @return array Modified array of classes for the groups loop.
 */
function shortcodes_for_buddypress_get_groups_loop_class( $classes, $component ) {
    // Get the BuddyPress Nouveau instance.
    $bp_nouveau = bp_nouveau();

    // Ensure the component is 'groups' before proceeding.
    if ( 'groups' === $component ) {
        // Retrieve layout preferences from the customizer for the groups component.
        $customizer_option = sprintf( '%s_layout', $component );
        $layout_prefs      = bp_nouveau_get_temporary_setting(
            $customizer_option,
            bp_nouveau_get_appearance_settings( $customizer_option )
        );

        // Apply grid classes if layout preferences are set and valid.
        if ( $layout_prefs && (int) $layout_prefs > 1 && function_exists( 'bp_nouveau_customizer_grid_choices' ) ) {
            $grid_classes = bp_nouveau_customizer_grid_choices( 'classes' );

            // If the layout preference corresponds to a valid grid class, merge it with existing classes.
            if ( isset( $grid_classes[ $layout_prefs ] ) ) {
                $classes = array_merge(
                    $classes,
                    array(
                        'grid',
                        esc_html( $grid_classes[ $layout_prefs ] ),
                    )
                );
            }

            // Set the layout preference globally for use in other templates if necessary.
            if ( ! isset( $bp_nouveau->{$component} ) ) {
                $bp_nouveau->{$component} = new stdClass();
            }

            // Store the layout preference for the groups loop.
            if ( property_exists( $bp_nouveau, 'loop_layout' ) ) {
                $bp_nouveau->{$component}->loop_layout = $layout_prefs;
            }
        }
    }
    return $classes;
}

// Hook into the BuddyPress loop classes filter.
add_filter( 'bp_nouveau_get_loop_classes', 'shortcodes_for_buddypress_get_groups_loop_class', 10, 2 );

