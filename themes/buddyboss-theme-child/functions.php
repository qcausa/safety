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
  load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

  // Translate text from the CHILD theme only.
  // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
  // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

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
  wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri().'/assets/css/custom.css' );

  // Javascript
  wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri().'/assets/js/custom.js' );
}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

// Add Quantity Input Beside Product Name
   
add_filter( 'woocommerce_checkout_cart_item_quantity', 'bbloomer_checkout_item_quantity_input', 9999, 3 );
  
function bbloomer_checkout_item_quantity_input( $product_quantity, $cart_item, $cart_item_key ) {
   $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
   $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
   if ( ! $product->is_sold_individually() ) {
      $product_quantity = woocommerce_quantity_input( array(
         'input_name'  => 'shipping_method_qty_' . $product_id,
         'input_value' => $cart_item['quantity'],
         'max_value'   => $product->get_max_purchase_quantity(),
         'min_value'   => '0',
      ), $product, false );
      $product_quantity .= '<input type="hidden" name="product_key_' . $product_id . '" value="' . $cart_item_key . '">';
   }
   return $product_quantity;
}
 
// ----------------------------
// Detect Quantity Change and Recalculate Totals
 
add_action( 'woocommerce_checkout_update_order_review', 'bbloomer_update_item_quantity_checkout' );
 
function bbloomer_update_item_quantity_checkout( $post_data ) {
   parse_str( $post_data, $post_data_array );
   $updated_qty = false;
   foreach ( $post_data_array as $key => $value ) {   
      if ( substr( $key, 0, 20 ) === 'shipping_method_qty_' ) {         
         $id = substr( $key, 20 );   
         WC()->cart->set_quantity( $post_data_array['product_key_' . $id], $post_data_array[$key], false );
         $updated_qty = true;
      }     
   }  
   if ( $updated_qty ) WC()->cart->calculate_totals();
}

/**
 * @snippet       Avoid Empty Cart Redirect @ WooCommerce Checkout
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.6.4
 * @community     https://businessbloomer.com/club/
 */
 
 add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false' );
 add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false' );



 add_action('elementor/query/division_contacts', function($query) {
   // Get the current post ID (specific division page)
   $current_post_id = get_the_ID();

   // Get the Pods object for the specific division page
   $pods = pods('division', $current_post_id);
   
   // Ensure the Pods object is valid and fetch the specific division page entry
   if ($pods) {
       // Get the related post data from the relationship field
       $division_contacts = $pods->field('division_contacts'); // Array of post data
       

       // Extract post IDs from division_contacts field if it has data
       $post_ids = !empty($division_contacts) ? wp_list_pluck($division_contacts, 'ID') : [];
       // BugFu::log($post_ids);

       // Set the post__in parameter for the query if there are valid post IDs
       if (!empty($post_ids)) {
           $query->set('post__in', $post_ids);
       }
   }
});

add_action('elementor/query/division_downloads', function($query) {
   // Get the current post ID (specific division page)
   $current_post_id = get_the_ID();

   // Get the Pods object for the specific division page
   $pods = pods('division', $current_post_id);
  
   // Ensure the Pods object is valid and fetch the specific division page entry
   if ($pods) {
       // Get the related post data from the relationship field
       $division_downloads = $pods->field('division_downloads'); // Array of post data or single item

       BugFu::log($division_downloads);

       // Check if the result is an array of multiple items or a single item
       $post_ids = [];
       if (isset($division_downloads['ID'])) {
           // Single item case
           $post_ids[] = $division_downloads['ID'];
       } elseif (is_array($division_downloads) && !empty($division_downloads)) {
           // Multiple items case
           $post_ids = wp_list_pluck($division_downloads, 'ID');
       }

       BugFu::log($post_ids);

       // Set the post__in parameter for the query if there are valid post IDs
       if (!empty($post_ids)) {
           $query->set('post__in', $post_ids);
       }
   }
});



add_action( 'save_post', 'custom_woocommerce_order_update', 10, 3 );

function custom_woocommerce_order_update( $post_id, $post, $update ) {
   BugFu::log("custom_woocommerce_order_update");
   BugFu::log($post);

}


/**
 * Register custom shortcode after all plugins are loaded to ensure Download Monitor is available.
 */
function custom_register_download_page_shortcode() {
   if ( function_exists( 'download_monitor' ) ) {

       /**
        * Wrapper for [download_page] shortcode to add a 'division' argument and filter downloads by post meta.
        */
       function custom_download_page_shortcode( $atts ) {
           // Parse shortcode arguments
           $atts = shortcode_atts( array(
               'division' => '', // Custom attribute
           ), $atts, 'custom_download_page' );

           // Debug to confirm shortcode execution
           error_log( 'Shortcode Executed: Division = ' . $atts['division'] );

           // Sanitize the division value
           $division_filter = sanitize_text_field( $atts['division'] );
           BugFu::log($division_filter);

           // Hook into the Download Monitor query to add the division filter
           add_filter( 'dlm_page_addon_download_retrieve_args', function( $query_args, $category ) use ( $division_filter ) {
               if ( ! empty( $division_filter ) ) {
                   $query_args['meta_query'][] = array(
                       'key'     => 'download_divisions',
                       'value'   => $division_filter,
                       'compare' => 'LIKE',
                   );
               }
               return $query_args;
           }, 10, 2 );

           // Safely call the original download_page function
           // Ensure Download Monitor is active
            if ( function_exists( 'download_monitor' ) ) {
               $download_page_service = download_monitor()->service( 'download_page' );

               if ( $download_page_service && method_exists( $download_page_service, 'download_page' ) ) {
                  // Safely call the original download_page() function
                  return $download_page_service->download_page( $atts );
               }
            }
  

           return __( 'Unable to display downloads. Please try again.', 'text-domain' );
       }

       // Register the custom shortcode
       add_shortcode( 'custom_download_page', 'custom_download_page_shortcode' );

   } else {
       // Fallback message if Download Monitor is inactive
       add_shortcode( 'custom_download_page', function() {
           return __( 'Download Monitor plugin is not active.', 'text-domain' );
       } );
   }
}
add_action( 'wp_loaded', 'custom_register_download_page_shortcode', 9999 );




/**
 * Assign a Contact post to a category when the contact_division changes.
 */
function custom_assign_contact_to_division_category( $pieces, $is_new_item, $id ) {
   BUgFu::log("custom_assign_contact_to_division_category");
   $params = $pieces['params'];
   BUgFu::log($params);
   //BugFu::log($params['pod']);
   BugFu::log($params->pod);
   // Check if the pod is "contacts" (your custom post type)
   if ( 'contact' !== $params->pod ) {
       return;
   }

   // Get the new value of the contact_division relationship field
   $new_divisions_ids = isset( $pieces['fields']['contact_division']['value'] ) ? $pieces['fields']['contact_division']['value'] : null;
    BugFu::log($new_divisions_ids);

    // Ensure $new_divisions is an array
    if ( ! is_array( $new_divisions_ids ) ) {
        $new_divisions_ids = ! empty( $new_divisions_ids ) ? array( $new_divisions_ids ) : array();
    }

    // Retrieve the old divisions to detect changes
    $old_divisions = get_post_meta( $id, 'contact_division', true );

    // Check if there are any changes
    if ( $new_divisions_ids === $old_divisions ) {
        return;
    }

    // Update the post meta to save the new divisions value
    update_post_meta( $id, 'contact_division', $new_divisions_ids );

    // Prepare an array to hold term IDs
    $term_ids = array();

    // Loop through each division
    foreach ( $new_divisions_ids as $new_divisions_id ) {
        if ( empty( $new_divisions_id ) ) {
            continue;
        }
        BugFu::log($new_divisions_id);

         // Get the division name (post title)
         $division_name = get_the_title( $division_id );
         BugFu::log($division_name);

        // Check if the category exists
        $division_term = get_term_by( 'name', $division_name, 'category' );

        // If the category doesn't exist, create it
        if ( ! $division_term ) {
            $new_term = wp_insert_term( $division_name, 'category' );
            if ( ! is_wp_error( $new_term ) ) {
                $term_id = $new_term['term_id'];
            } else {
                continue; // Skip this term if creation failed
            }
        } else {
            $term_id = $division_term->term_id;
        }

        // Add the term ID to the list
        $term_ids[] = $term_id;
        BugFu::log($term_ids);
    }

    // Assign all collected categories (term IDs) to the post
    if ( ! empty( $term_ids ) ) {
        wp_set_post_terms( $id, $term_ids, 'category', false );
    }
}
add_action( 'pods_api_post_save_pod_item', 'custom_assign_contact_to_division_category', 10, 3 );

function bb_custom_login() {?>
    <style type="text/css">
        .login-split {
            background-position: 10% center !important;
        }

        .register-section-logo{
            text-align: center !important; 
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'bb_custom_login' );





add_action( 'elementor/theme/register_conditions', function( $conditions_manager ) {
	class Page_Template_Condition extends ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base {
		public static function get_type() {
			return 'singular';
		}

		public static function get_priority() {
			return 30;
		}

		public function get_name() {
			return 'page_template';
		}

		public function get_label() {
			return __( 'Page Template' );
		}

		public function check( $args ) {
			return isset( $args['id'] ) && is_page_template( $args['id'] );
		}

		protected function _register_controls() {
			$this->add_control(
				'page_template',
				[
					'section' => 'settings',
					'label' => __( 'Page Template' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => array_flip( get_page_templates() ),
				]
			);
		}
	}

	$conditions_manager->get_condition( 'singular' )->register_sub_condition( new Page_Template_Condition() );
}, 100 );


add_action( 'elementor/theme/register_conditions', function( $conditions_manager ) {

    /**
     * BuddyPress Group Page Condition Class
     */
    class BP_Group_Page_Condition extends ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base {

        /**
         * Condition Type
         */
        public static function get_type() {
            return 'singular'; // Add under 'Singular' conditions.
        }

        /**
         * Condition Priority
         */
        public static function get_priority() {
            return 30;
        }

        /**
         * Condition Name
         */
        public function get_name() {
            return 'bp_group_page';
        }

        /**
         * Condition Label (What the user sees in Elementor)
         */
        public function get_label() {
            return __( 'BuddyPress Group Page', 'your-text-domain' );
        }

        /**
         * Check if Condition is Met
         */
        public function check( $args ) {
            if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
                // Check if the current tab slug matches 'custom-landing'
                global $bp;
                // BugFu::log("check");
                // BugFu::log($bp->current_action);
               // Return true ONLY when the current action is 'custom-landing'
                return true;
            }
            return false;
        }

        /**
         * No Custom Controls Required
         */
        protected function _register_controls() {
            // No additional controls needed since it targets all BuddyPress group pages.
        }
    }

    // Register the custom BuddyPress Group Page condition
    $conditions_manager->get_condition( 'singular' )->register_sub_condition( new BP_Group_Page_Condition() );

}, 100 );


 
 
/**
 * Add a custom tab to BuddyPress Groups
 */
function custom_bp_group_new_tab() {
    if ( ! bp_is_group() ) {
        return; // Avoid errors if not on a group page
    }

    $current_group = groups_get_current_group();
    //BugFu::log($current_group);
    $parent_url = bp_get_group_permalink( $current_group );
    //BugFu::log($parent_url);
    $parent_slug = bp_get_current_group_slug();
    //BUgFu::log($parent_slug);

    if ( empty( $current_group ) ) {
        return; // Exit if no group context is available
    }

    // Register a new sub-navigation item (tab) for each group
    bp_core_new_subnav_item( array(
        'name'            => __( 'Dashboard', 'textdomain' ), // Tab Name
        'slug'            => 'custom-landing', // Unique Slug for the Tab
        'parent_url'      => bp_get_group_permalink( $current_group ), // Parent Group URL
        'parent_slug'     => bp_get_current_group_slug(), // Parent Slug
        'screen_function' => 'custom_bp_group_tab_screen', // Callback function
        'position'        => 0, // Set position to 0 to make it the first tab
        'default_subnav_slug' => 'custom-landing', // Define as the default tab
    ) );

    // Register a new sub-navigation item (tab) for each group
    // bp_core_new_subnav_item( array(
    //     'name'            => __( 'Add ', 'textdomain' ), // Tab Name
    //     'slug'            => 'custom-landing', // Unique Slug for the Tab
    //     'parent_url'      => bp_get_group_permalink( $current_group ), // Parent Group URL
    //     'parent_slug'     => bp_get_current_group_slug(), // Parent Slug
    //     'screen_function' => 'custom_bp_group_tab_screen', // Callback function
    //     'position'        => 0, // Set position to 0 to make it the first tab
    //     'default_subnav_slug' => 'custom-landing', // Define as the default tab
    // ) );
}
add_action( 'bp_setup_nav', 'custom_bp_group_new_tab' );

/**
 * Screen Function for the Custom Tab
 */
function custom_bp_group_tab_screen() {
    add_action( 'bp_template_content', 'custom_bp_group_tab_content' );
    bp_core_load_template( 'groups/single/plugins' );
}

/**
 * Content for the Custom Tab - Load Elementor Template Dynamically
 */
function custom_bp_group_tab_content() {
    echo '<div id="custom-landing-tab">';

    // Check if we are on a BuddyPress group landing tab
    if ( function_exists( 'bp_is_group' ) && bp_is_group() && bp_current_action() === 'custom-landing' ) {

        // Map of group IDs to Elementor template IDs
        $group_to_template_map = array(
            8    => 1253, // Group ID 1 -> Template ID 1253
            // Add more mappings as needed
        );

        // Get the current group ID
        $group_id = bp_get_current_group_id();

        // Determine the template ID for the current group
        $template_id = isset( $group_to_template_map[ $group_id ] ) ? $group_to_template_map[ $group_id ] : null;

        if ( $template_id && class_exists( '\Elementor\Plugin' ) ) {
            // Render the Elementor template dynamically
            echo Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );
        } 
        // else {
        //     // Fallback message if no template is found or Elementor is not active
        //     echo '<p>' . __( 'No template found for this group or Elementor is inactive.', 'textdomain' ) . '</p>';
        // }
    }

    echo '</div>';
}

define( 'BP_GROUPS_DEFAULT_EXTENSION', 'custom-landing' );


/**
 * Redirect BuddyPress Group Root to the Custom Landing Tab
 */
// function custom_bp_group_default_tab_redirect() {
//     BugFu::log("custom_bp_group_default_tab_redirect");
//     BugFu::log(bp_is_group());
//     BugFu::log(! bp_is_group_admin_page());
//     BugFu::log(bp_is_current_action( '' ));
//     if ( bp_is_group() && ! bp_is_group_admin_page() && bp_is_current_action( '' ) ) {
//         $group_permalink = bp_get_group_permalink( groups_get_current_group() );
//         wp_redirect( $group_permalink . 'custom-landing/' ); // Redirect to the Custom Landing tab
//         exit;
//     }
// }
// add_action( 'bp_actions', 'custom_bp_group_default_tab_redirect' );


/**
 * Change post status after submission based on user role.
 *
 * @param array $fields Submitted form fields.
 * @param array $entry  Entry data.
 * @param int   $form_id Form ID.
 */
function custom_change_post_status_based_on_user_role( $fields, $entry, $form_id ) {
    // Check if this is the correct form (replace 123 with your form ID)
    // if ( $form_id !== 123 ) {
    //     return;
    // }

    // Check if the post was created
    if ( empty( $entry['post_id'] ) ) {
        return;
    }

    // Get the current user
    $current_user = wp_get_current_user();

    // Default post status
    $new_post_status = 'pending'; // Default fallback status

    // Set post status based on user role
    if ( in_array( 'administrator', (array) $current_user->roles, true ) ) {
        $new_post_status = 'publish'; // Admin posts are published immediately
    } elseif ( in_array( 'editor', (array) $current_user->roles, true ) ) {
        $new_post_status = 'publish'; // Editors need pending review
    } elseif ( in_array( 'subscriber', (array) $current_user->roles, true ) ) {
        $new_post_status = 'pending'; // Subscribers’ posts are saved as drafts
    }

    // Update the post status
    $post_id = (int) $entry['post_id'];

    wp_update_post( array(
        'ID'          => $post_id,
        'post_status' => $new_post_status,
    ) );
}
add_action( 'wpforms_post_submissions_process_complete', 'custom_change_post_status_based_on_user_role', 10, 3 );




/**
 * Allow WooCommerce products and posts to use the same 'post_tag' taxonomy.
 */
function custom_share_tags_between_posts_and_products() {
    // Unregister WooCommerce's default 'product_tag' taxonomy
    unregister_taxonomy( 'product_tag' );

    // Register 'post_tag' taxonomy for WooCommerce products
    register_taxonomy_for_object_type( 'post_tag', 'product' );
}
add_action( 'init', 'custom_share_tags_between_posts_and_products', 11 );


/**
 * Register 'post_tag' taxonomy for WooCommerce products early.
 */
function custom_register_post_tag_for_products() {
    global $wp_taxonomies;

    // Ensure 'post_tag' is associated with 'product'
    if ( isset( $wp_taxonomies['post_tag'] ) ) {
        $wp_taxonomies['post_tag']->object_type[] = 'product';
        register_taxonomy_for_object_type( 'post_tag', 'product' );
    }
}
add_action( 'after_setup_theme', 'custom_register_post_tag_for_products', 0 );






 
add_action('bp_setup_nav', function()
{
    // BugFu::log("bp_setup_nav");
    $bp = buddypress();
    if(bp_is_group())
    {
        //BugFu::log("bp_is_group");
 
        $group_slug = bp_get_current_group_slug();
        $current_group = groups_get_current_group();
        $parent_url = trailingslashit( bp_get_group_permalink( $current_group ) . 'admin' );
        $args =
        [
            'name' => 'Group Slider',
            'slug' => 'group-slider',
            'rewrite_id' => 'bp_group_manage_change_group_photo',
            'parent_slug' => $group_slug . '_manage',
            'parent_url'      => $parent_url,
            'position' => 10,
            'user_has_access' => true,
            'show_in_admin_bar' => true,
            'no_access_url'   => bp_get_group_permalink( $current_group ),
            'screen_function' => 'custom_group_management_tab_loader'
        ];
        $tab = bp_core_new_subnav_item($args, 'groups');
    }
}, 100);

function custom_group_management_tab_loader() {

}

function groups_screen_group_admin_change_gorup_photo() {
	if ( 'group-slider' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	/**
	 * Filters the template to load for a group's Change cover photo page.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $value Path to a group's Change cover photo template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_cover_image', 'groups/single/admin' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_change_gorup_photo' );


/**
 * Load the requested Manage Screen for the current group.
 *
 * @since BuddyPress 3.0.0
 */

 function bp_custom_nouveau_group_manage_screen() {
	BugFu::log( 'bp_custom_nouveau_group_manage_screen' );
	$action          = bp_action_variable( 0 );
	BugFu::log( $action );
	$is_group_create = bp_is_group_create();
	$output          = '';

	if ( $is_group_create ) {
		$action = bp_action_variable( 1 );
	}

	$screen_id = urlencode( sanitize_file_name( urldecode( $action ) ) );
	BugFu::log( $screen_id );   //change-group-photo
	if ( ! bp_is_group_admin_screen( $screen_id ) && ! bp_is_group_creation_step( $screen_id ) ) {
		return;
	}

	if ( ! $is_group_create ) {
		BugFu::log( 'not is_group_create' );
		/**
		 * Fires inside the group admin form and before the content.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_before_group_admin_content' );

		$core_screen = bp_nouveau_group_get_core_manage_screens( $screen_id );
		BugFu::log( $core_screen );

	// It's a group step, get the creation screens.
	} else {
		$core_screen = bp_nouveau_group_get_core_create_screens( $screen_id );
		BugFu::log( $core_screen );
	}

	if ( ! $core_screen ) {
		if ( ! $is_group_create ) {
			/**
			 * Fires inside the group admin template.
			 *
			 * Allows plugins to add custom group edit screens.
			 *
			 * @since BuddyPress 1.1.0
			 */

             //TODO : Custom Code Here
            $template = 'groups/single/admin/' . $screen_id;
            //BugFu::log(bp_get_template_part( $template ));
            bp_get_template_part( $template );
            
			do_action( 'groups_custom_edit_steps' );

		// Else use the group create hook
		} else {
			/**
			 * Fires inside the group admin template.
			 *
			 * Allows plugins to add custom group creation steps.
			 *
			 * @since BuddyPress 1.1.0
			 */

             

			do_action( 'groups_custom_create_steps' );
		}

	// Else we load the core screen.
	} else {
		if ( ! empty( $core_screen['hook'] ) ) {
			/**
			 * Fires before the display of group delete admin.
			 *
			 * @since BuddyPress 1.1.0 For most hooks.
			 * @since BuddyPress 2.4.0 For the cover photo hook.
			 */
			do_action( 'bp_before_' . $core_screen['hook'] );
		}

		$template = 'groups/single/admin/' . $screen_id;
		BugFu::log( $template );

		if ( ! empty( $core_screen['template'] ) ) {
			$template = $core_screen['template'];
		}

		bp_get_template_part( $template );
		BugFu::log(bp_get_template_part( $template ));

		if ( ! empty( $core_screen['hook'] ) ) {
			BugFu::log( $core_screen['hook'] );

			// Group's "Manage > Details" page.
			if ( 'group_details_admin' === $core_screen['hook'] ) {
				/**
				 * Fires after the group description admin details.
				 *
				 * @since BuddyPress 1.0.0
				 */
				do_action( 'groups_custom_group_fields_editable' );
			}

			/**
			 * Fires before the display of group delete admin.
			 *
			 * @since BuddyPress 1.1.0 For most hooks.
			 * @since BuddyPress 2.4.0 For the cover photo hook.
			 */
			do_action( 'bp_after_' . $core_screen['hook'] );
		}

		if ( ! empty( $core_screen['nonce'] ) ) {
			if ( ! $is_group_create ) {
				$output = sprintf( '<p><input type="submit" value="%s" id="save" name="save" /></p>', esc_attr__( 'Save Changes', 'buddyboss' ) );

				// Specific case for the delete group screen
				if ( 'delete-group' === $screen_id ) {
					$output = sprintf(
						'<div class="submit">
							<input type="submit" disabled="disabled" value="%s" id="delete-group-button" name="delete-group-button" />
						</div>',
						esc_attr__( 'Delete Group', 'buddyboss' )
					);
				}
			}
		}
	}

	if ( $is_group_create ) {
		/**
		 * Fires before the display of the group creation step buttons.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_before_group_creation_step_buttons' );

		if ( 'crop-image' !== bp_get_avatar_admin_step() ) {
			$creation_step_buttons = '';

			if ( ! bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="button" value="%1$s" id="group-creation-previous" name="previous" onclick="%2$s" />',
					esc_attr__( 'Previous Step', 'buddyboss' ),
					"location.href='" . esc_js( esc_url_raw( bp_get_group_creation_previous_link() ) ) . "'"
				);
			}

			if ( ! bp_is_last_group_creation_step() && ! bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-next" name="save" />',
					esc_attr__( 'Next Step', 'buddyboss' )
				);
			}

			if ( bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-create" name="save" />',
					esc_attr__( 'Create Group and Continue', 'buddyboss' )
				);
			}

			if ( bp_is_last_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-finish" name="save" />',
					esc_attr__( 'Finish', 'buddyboss' )
				);
			}

			// Set the output for the buttons
			$output = sprintf( '<div class="submit" id="previous-next">%s</div>', $creation_step_buttons );
		}

		/**
		 * Fires after the display of the group creation step buttons.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_after_group_creation_step_buttons' );
	}

	/**
	 * Avoid nested forms with the Backbone views for the group invites step.
	 */
	if ( 'group-invites' === bp_get_groups_current_create_step() ) {
		printf(
			'<form action="%s" method="post" enctype="multipart/form-data">',
			bp_get_group_creation_form_action()
		);
	}

	if ( ! empty( $core_screen['nonce'] ) ) {
		wp_nonce_field( $core_screen['nonce'] );
	}

	printf(
		'<input type="hidden" name="group-id" id="group-id" value="%s" />',
		$is_group_create ? esc_attr( bp_get_new_group_id() ) : esc_attr( bp_get_group_id() )
	);

	printf(
		'<input type="hidden" name="parent-id" id="parent-id" value="%s" />',
		$is_group_create ? esc_attr( bp_get_parent_group_id( bp_get_new_group_id() ) ) : esc_attr( bp_get_parent_group_id( bp_get_group_id() ) )
	);

	// The submit actions
	echo $output;

	if ( ! $is_group_create ) {
		/**
		 * Fires inside the group admin form and after the content.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_after_group_admin_content' );

	} else {
		/**
		 * Fires and displays the groups directory content.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_directory_groups_content' );
	}

	/**
	 * Avoid nested forms with the Backbone views for the group invites step.
	 */
	if ( 'group-invites' === bp_get_groups_current_create_step() ) {
		echo '</form>';
	}
}






/**
 * Add BuddyPress Groups to Pods 'Extend an Existing Content Type' screen.
 */
add_filter( 'pods_admin_setup_add_extend_pod_type', 'add_buddypress_groups_to_pods_extend_types' );

function add_buddypress_groups_to_pods_extend_types( $data ) {
    // Ensure BuddyPress is active before adding BuddyPress Groups
    if ( function_exists( 'groups_get_groups' ) ) {
        // Add BuddyPress Groups to the Extend Content Type list
        $data['bp_groups'] = __( 'BuddyPress Groups', 'textdomain' );
    }

    return $data;
}


/**
 * Register BuddyPress Groups in Pods using the existing wp_bp_groups table.
 */
add_action( 'pods_init', 'register_bp_groups_pods_type' );

function register_bp_groups_pods_type() {
    // Ensure BuddyPress is active
    if ( ! function_exists( 'groups_get_groups' ) ) {
        return;
    }

    // Register BuddyPress Groups as a Pods type
    $args = array(
        'name'        => 'bp_groups',
        'label'       => __( 'BuddyPress Groups', 'textdomain' ),
        'type'        => 'custom', // Mark it as a custom content type
        'table'       => 'wp_bp_groups', // Use the existing BuddyPress groups table
        'object_type' => 'custom',
        'groups'      => array(
            array(
                'name'  => 'main',
                'label' => __( 'Main Fields', 'textdomain' ),
            ),
        ),
        'fields'      => array(
            array(
                'name'  => 'id',
                'label' => __( 'Group ID', 'textdomain' ),
                'type'  => 'number',
                'options' => array( 'readonly' => true ),
            ),
            array(
                'name'  => 'name',
                'label' => __( 'Group Name', 'textdomain' ),
                'type'  => 'text',
            ),
            array(
                'name'  => 'slug',
                'label' => __( 'Group Slug', 'textdomain' ),
                'type'  => 'text',
            ),
            array(
                'name'  => 'description',
                'label' => __( 'Group Description', 'textdomain' ),
                'type'  => 'textarea',
            ),
            array(
                'name'  => 'status',
                'label' => __( 'Group Status', 'textdomain' ),
                'type'  => 'text',
            ),
        ),
    );

    pods_register_type( 'custom', 'bp_groups', $args );
}



add_action( 'bp_activity_post_form_options', 'render_custom_activity_fields' );
function render_custom_activity_fields() {
	if ( ! bp_is_groups_component() ) {
		return;
	}
	
	if ( ! groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() ) ) {
		return;
	}
    ?>
    <div style="padding: 20px 25px 16px;background-color: var(--bb-content-alternate-background-color);">
        <input type="checkbox" name="is_announcement" id="is_announcement" value="1" />
        <label for="is_announcement">Is announcement?</label>
    </div>

   

    <?php
}


add_action( 'elementor/query/bp_activity_query', function( $query ) {
    BugFu::log("elementor/query/bp_activity_query");
    global $wpdb;

    // Custom table name
    $activity_table = $wpdb->prefix . 'bp_activity';

    // Query to fetch recent activity records (you can add conditions here)
    $results = $wpdb->get_results( "
        SELECT id
        FROM $activity_table
        WHERE type = 'last_activity'
        ORDER BY date_recorded DESC
        LIMIT 10
    " );

    BugFu::log($results);

    // Prepare an array of post IDs for the Loop Grid
    $post_ids = [];
    foreach ( $results as $activity ) {
        // Use activity ID to store as post IDs in a virtual way
        $post_ids[] = $activity->id;
    }
    BugFu::log($post_ids);

    // Stop if no results found
    if ( empty( $post_ids ) ) {
        return;
    }

    // Modify the main query to include custom "post IDs"
    $query->set( 'post_type', 'any' ); // Use 'any' to allow custom virtual posts
    $query->set( 'post__in', $post_ids );
    $query->set( 'orderby', 'post__in' );
    $query->set( 'posts_per_page', count( $post_ids ) );
} );
 


/**
 * Add a custom submenu item under 'Posts' for the 'Tips Tuesday' category
 * and place it right after 'All Posts'.
 */
function add_tips_tuesday_admin_submenu() {
    add_submenu_page(
        'edit.php',                 // Parent menu slug (Posts)
        'Tips Tuesday',             // Page title
        'Tips Tuesday',             // Menu title
        'edit_posts',               // Capability (who can see it)
        'edit.php?tips_tuesday=1'   // Target URL with query string
    );
}
add_action( 'admin_menu', 'add_tips_tuesday_admin_submenu' );

/**
 * Reorder the Posts submenu to make 'Tips Tuesday' priority 2.
 */
function reorder_tips_tuesday_submenu() {
    global $submenu;

    // Check if 'Posts' submenu exists
    if ( isset( $submenu['edit.php'] ) ) {
        $all_posts_item = array_shift( $submenu['edit.php'] ); // Remove 'All Posts'
        $tips_tuesday_item = array_pop( $submenu['edit.php'] ); // Remove 'Tips Tuesday'
        
        // Reinsert in desired order
        array_unshift( $submenu['edit.php'], $all_posts_item ); // Add 'All Posts' first
        array_splice( $submenu['edit.php'], 1, 0, [ $tips_tuesday_item ] ); // Insert 'Tips Tuesday' as second
    }
}
add_action( 'admin_menu', 'reorder_tips_tuesday_submenu', 999 );

/**
 * Add a custom view/tab in the posts list screen for the 'Tips Tuesday' category
 * and place it after the 'All' tab.
 */
function add_custom_tips_tuesday_view( $views ) {
    global $wpdb;

    // Define the category slug for 'Tips Tuesday'
    $tips_tuesday_slug = 'tips-tuesday';

    // Get the category object
    $category = get_category_by_slug( $tips_tuesday_slug );

    // Ensure the category exists
    if ( $category ) {
        // Correctly count posts in the 'Tips Tuesday' category
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_status = 'publish'
             AND tt.taxonomy = 'category'
             AND tt.term_id = %d",
            $category->term_id
        ) );

        // Highlight the active tab
        $class = ( isset( $_GET['tips_tuesday'] ) ) ? 'current' : '';

        // Add the Tips Tuesday view
        $tips_tuesday_view = [
            'tips_tuesday' => sprintf(
                '<a href="%s" class="%s">Tips Tuesday <span class="count">(%d)</span></a>',
                admin_url( 'edit.php?tips_tuesday=1' ),
                $class,
                $count
            ),
        ];

        // Insert 'Tips Tuesday' after 'All'
        if ( isset( $views['all'] ) ) {
            $views = array_slice( $views, 0, 1, true ) + $tips_tuesday_view + array_slice( $views, 1, null, true );
        } else {
            $views = $tips_tuesday_view + $views;
        }
    }

    return $views;
}
add_filter( 'views_edit-post', 'add_custom_tips_tuesday_view' );

/**
 * Modify the query to filter posts in the 'Tips Tuesday' category.
 */
function filter_posts_for_tips_tuesday( $query ) {
    if ( is_admin() && $query->is_main_query() && isset( $_GET['tips_tuesday'] ) && $_GET['tips_tuesday'] == 1 ) {
        // Add the category filter for 'Tips Tuesday'
        $query->set( 'category_name', 'tips-tuesday' );
    }
}
add_action( 'pre_get_posts', 'filter_posts_for_tips_tuesday' );






/**
 * Automatically sync year and month tags based on post date for 'Tips Tuesday' posts.
 */
function sync_post_year_month_tags( $post_id, $post, $update ) {
    // Check if it's a 'post' post type and not an autosave or revision
    if ( 'post' !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // Get the categories assigned to this post
    $categories = wp_get_post_categories( $post_id, [ 'fields' => 'names' ] );

    // Check if 'Tips Tuesday' category is assigned to this post
    if ( ! in_array( 'Tips Tuesday', $categories ) ) {
        return; // Exit if 'Tips Tuesday' is not assigned
    }

    // Get the post date
    $post_date = get_the_date( 'Y-m-d', $post_id );
    $year      = date( 'Y', strtotime( $post_date ) );
    $month     = date( 'F', strtotime( $post_date ) ); // Full month name

    // Prepare the new tags
    $new_tags = [ $year, $month ];

    // Get all existing tags for the post
    $existing_tags = wp_get_post_tags( $post_id, [ 'fields' => 'names' ] );

    // Remove old year/month tags if they exist
    $old_tags = array_filter( $existing_tags, function( $tag ) {
        return preg_match( '/^\d{4}$/', $tag ) || in_array( $tag, get_month_names() );
    } );

    // Remove old tags
    $remaining_tags = array_diff( $existing_tags, $old_tags );

    // Combine remaining tags with new year/month tags
    $final_tags = array_merge( $remaining_tags, $new_tags );

    // Assign the updated tags to the post
    wp_set_post_tags( $post_id, $final_tags, false ); // 'false' replaces the tags completely
}
add_action( 'save_post', 'sync_post_year_month_tags', 10, 3 );

/**
 * Helper function to get an array of month names.
 */
function get_month_names() {
    return [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
}



// /**
//  * Elementor Custom Query: Tips Tuesday Category with Tag Filtering.
//  */
// function custom_elementor_tips_tuesday_query( $query ) {
//     BugFu::log("custom_elementor_tips_tuesday_query");
//     // Set the base query to only include posts in the 'Tips Tuesday' category
//     $query->set( 'category_name', 'tips-tuesday' );

//     // Allow filtering by tags using Elementor's Taxonomy Filter Widget
//     if ( isset( $_GET['post_tag'] ) && ! empty( $_GET['post_tag'] ) ) {
//         $query->set( 'tag', sanitize_text_field( $_GET['post_tag'] ) );
//     }
// }
// add_action( 'elementor/query/tips_tuesday_filter', 'custom_elementor_tips_tuesday_query' );


add_action('elementor/query/featured_posts', function($query) {
    // Add the "featured" meta query to the query
    $meta_query = array(
        array(
            'key'     => '_featured', // Meta key for featured status (adjust this if your site uses a different meta key)
            'value'   => 'yes',       // Value that indicates the post is featured
            'compare' => '='
        )
    );

    // Merge any existing meta query with the new condition
    $existing_meta_query = $query->get('meta_query');

    if ( !empty($existing_meta_query) ) {
        $meta_query = array_merge($existing_meta_query, $meta_query);
    }

    $query->set('meta_query', $meta_query);

    // Optional: Order by date or other criteria
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
});



/**
 * Limit posts or pages displayed by category.
 *
 * @link https://wpforms.com/developers/how-to-exclude-posts-pages-or-categories-from-dynamic-choices/
 */
  
//  function wpf_dynamic_choices_categories( $args, $field, $form_data ) {
//     BugFu::log("wpf_dynamic_choices_categories");
//     BugFu::log($args);
//     BugFu::log($field);
//     BugFu::log($form_data);
      
//     // For field #10 in form #851, only show entries in category #37
//     if ( '946' == $form_data['id'] && '5' == $field[ 'id' ] ) {
  
//         $args[ 'category' ] = '161';
  
//     } 
      
//     return $args;
      
// }
  
// add_filter( 'wpforms_dynamic_choice_taxonomy_args', 'wpf_dynamic_choices_categories', 100, 3 );



// function wpf_dev_dynamic_choices_exclude( $args, $field, $form_id ) {
//     BugFu::log("wpf_dev_dynamic_choices_exclude");
//     BugFu::log($args);
  
//     if ( is_array( $form_id ) ) {
//         $form_id = $form_id[ 'id' ];
//     }
  
//     // Only on form #212 and field #16
//     if ( $form_id == 946 && $field[ 'id' ] == 5 ) {
  
//         // Category IDs to exclude
//         $args[ 'include' ] = '161';
//     }
  
//     return $args;
  
// }
  
// add_filter( 'wpforms_dynamic_choice_taxonomy_args', 'wpf_dev_dynamic_choices_exclude', 10, 3 );


function wpf_dev_dynamic_choices_include_subcategories( $args, $field, $form_id ) {
    BugFu::log("wpf_dev_dynamic_choices_include_subcategories");

    // Ensure $form_id is correctly retrieved
    if ( is_array( $form_id ) ) {
        $form_id = $form_id['id'];
    }

    // Only target form ID #946
    if ( $form_id == 946 ) {
        
        // Map field IDs to parent category IDs
        $field_to_category_map = [
            5  => 132, // Field ID 5 -> Category ID 132
            11 => 143,
            12 => 193,
            13 => 199,
            14 => 161,
            15 => 167, 
            16 => 175,
            17 => 190, 
        ];

        // Check if the current field ID exists in the map
        if ( isset( $field_to_category_map[ $field['id'] ] ) ) {
            $parent_category_id = $field_to_category_map[ $field['id'] ];

            // Get all subcategories under the parent category
            $subcategories = get_terms( [
                'taxonomy'   => 'category',
                'parent'     => $parent_category_id,
                'hide_empty' => false, // Include empty categories
            ] );

            // Extract term IDs and include the parent category itself
            $subcategory_ids = !empty( $subcategories ) ? wp_list_pluck( $subcategories, 'term_id' ) : [];
            $args['include'] = $parent_category_id . ',' . implode( ',', $subcategory_ids );

            BugFu::log($args['include']);
        }
    }

    return $args;
}
add_filter( 'wpforms_dynamic_choice_taxonomy_args', 'wpf_dev_dynamic_choices_include_subcategories', 10, 3 );



// ----------------------------------------------------------------

/**
 * Register a virtual custom post type for BuddyPress Activity
 */
function register_bp_activity_post_type() {
    register_post_type( 'bp_activity_post', array(
        'labels' => array(
            'name' => __( 'BuddyPress Activity', 'textdomain' ),
            'singular_name' => __( 'Activity', 'textdomain' ),
        ),
        'public' => true,
        'show_ui' => false, // Hide from WordPress admin
        'exclude_from_search' => true,
        'supports' => array( 'title', 'editor' ),
    ) );
}
add_action( 'init', 'register_bp_activity_post_type' );


/**
 * Modify Elementor query to fetch BuddyPress Activity records
 */
add_action( 'elementor/query/bp_activity_query', function( $query ) {
    global $wpdb;

    // Fetch BuddyPress activity data
    $activity_table = $wpdb->prefix . 'bp_activity';
    $results = $wpdb->get_results( "
        SELECT id, user_id, content, date_recorded
        FROM {$activity_table}
        WHERE type = 'last_activity'
        ORDER BY date_recorded DESC
        LIMIT 10
    " );

    // If no results, stop here
    if ( empty( $results ) ) {
        $query->set( 'post__in', [0] );
        return;
    }

    // Directly override query results with custom posts
    add_filter( 'posts_results', function( $posts, $query_instance ) use ( $results ) {
        BugFu::log("posts_results");
        if ( $query_instance->get( 'post_type' ) !== 'bp_activity_post' ) {
            return $posts;
        }
        BugFu::log("PASS 1");

        // Inject fake posts directly
        $fake_posts = [];
        foreach ( $results as $index => $activity ) {
            $post = new stdClass();

            $post->ID = $index + 1000; // Ensure unique ID
            $post->post_author = $activity->user_id;
            $post->post_date = $activity->date_recorded;
            $post->post_title = 'Activity by User ' . $activity->user_id;
            $post->post_content = $activity->content;
            $post->post_status = 'any';
            $post->post_type = 'bp_activity_post';
            $post->guid = home_url( '/?post_type=bp_activity_post&p=' . ( $index + 1000 ) );
            $post->post_name = sanitize_title( 'activity-' . $index );

            $fake_posts[] = new WP_Post( $post );
        }

        return $fake_posts;
    }, 10, 2 );

    // Set post type to prevent normal WP_Query
    $query->set( 'post_type', 'bp_activity_post' );
    $query->set( 'post__in', [] ); // Empty to bypass wp_posts
} );









/**
 * Override post meta requests to fetch data from bp_activity_meta
 */
// add_filter( 'get_post_metadata', function( $value, $post_id, $meta_key, $single ) {
//     global $wpdb;

//     // Check if we are handling BuddyPress activity fake posts
//     $post = get_post( $post_id );
//     if ( $post && $post->post_type === 'bp_activity_post' ) {

//         // Fetch meta data from bp_activity_meta table
//         $meta_table = $wpdb->prefix . 'bp_activity_meta';
//         $meta_value = $wpdb->get_var( $wpdb->prepare( "
//             SELECT meta_value FROM {$meta_table}
//             WHERE activity_id = %d AND meta_key = %s
//         ", $post_id, $meta_key ) );

//         if ( ! is_null( $meta_value ) ) {
//             return maybe_unserialize( $meta_value );
//         }
//     }

//     return $value;
// }, 10, 4 );




add_action( 'elementor/query/query_results', function( $query, $widget ) {
    // BugFu::log("elementor/query/query_results");
    // BugFu::log($query);

    
    }, 10, 2 );




// add_action( 'template_redirect', function() {
//     global $wp_query;

//     if ( is_post_type_archive( 'dlm_download' ) ) {
//         BugFu::log( 'We are on the dlm_download archive page' );
//     }

//     if ( is_404() ) {
//         BugFu::log( '404 triggered' );
//     }

//     if ( wp_redirect( '' ) ) {
//         BugFu::log( 'Redirection is happening here' );
//     }
// }, 1 );



// ----------------------------------------------------------------
    
// add_filter('template_include', 'dlm_downloads_template');

// function dlm_downloads_template( $template ) {
//     BugFu::log("dlm_downloads");
//     if ( is_post_type_archive('dlm_downloads') ) {
//         BugFu::log("is dlm_downloads archive");
//     } else {
//         BugFu::log("is NOT dlm_downloads archive");
//     }
//   }



function display_posts_by_category_and_tag_shortcode( $atts ) {
    // Extract shortcode attributes
    $atts = shortcode_atts( array(
        'term_id' => '', // Passed category term_id
    ), $atts );

    // Debugging with BugFu
    BugFu::log( $atts );

    // Ensure term_id is valid
    if ( empty( $atts['term_id'] ) ) {
        return 'No downloads found for this category.';
    }

    // Query downloads with 'dlm_download_category' = term_id
    $query_args = array(
        'post_type'      => 'dlm_download', // Download Monitor post type
        'tax_query'      => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'dlm_download_category', // Taxonomy for downloads
                'field'    => 'term_id',
                'terms'    => $atts['term_id'], // Term ID passed to the shortcode
            ),
            array(
                'taxonomy' => 'dlm_download_tag', // Taxonomy for tags
                'field'    => 'term_id',
                'terms'    => 217, // Exclude tag with ID 217
                'operator' => 'NOT IN', // Exclude posts with this tag
            ),
        ),
        'posts_per_page' => -1, // Retrieve all matching posts
    );


    $query = new WP_Query( $query_args );

    // Check if any posts were found
    if ( ! $query->have_posts() ) {
        return 'No downloads found for this category.';
    }

    // Group downloads by tags
    $grouped_downloads = array();
    $has_tags = false;

    foreach ( $query->posts as $post ) {
        $tags = get_the_terms( $post->ID, 'dlm_download_tag' ); // Get tags for the post

        if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
            $tag_name = $tags[0]->name; // Group by first tag
            $has_tags = true;
        } else {
            $tag_name = 'Untagged';
        }

        $grouped_downloads[ $tag_name ][] = $post;
    }

    // Start output buffering
    ob_start();
    ?>
    <div class="downloads-by-category">
        <?php if ( $has_tags ) : ?>
            <?php foreach ( $grouped_downloads as $tag_name => $posts ) : ?>
                <?php if ( $tag_name === 'Untagged' && $has_tags ) continue; ?> <!-- Skip 'Untagged' if tagged items exist -->

                <div class="accordion-item">
                    <h5 class="accordion-trigger" style="cursor: pointer; margin: 0;" 
                        onclick="toggleAccordion(this)">
                        <?php echo esc_html( $tag_name ); ?>
                        <span style="font-family: 'Nunito Sans' ;display: block;font-size:.8rem;text-transform:lowercase;">(Click to expand)</span>
                    </h5>
                    <div class="accordion-body" style="display: none;">
                        <ul class="downloads-list">
                            <?php foreach ( $posts as $post ) : ?>
                                <li class="download-item">
                                    <a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank">
                                        <?php 
                                        // Display the large featured image
                                        if ( has_post_thumbnail( $post->ID ) ) {
                                            echo get_the_post_thumbnail( $post->ID, 'large', array( 'class' => 'download-thumbnail-large' ) );
                                        }
                                        ?>
                                        <span class="download-title"><?php echo esc_html( $post->post_title ); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Directly list downloads if no tags exist -->
            <ul class="downloads-list">
                <?php foreach ( $query->posts as $post ) : ?>
                    <li class="download-item">
                        <a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank">
                            <?php 
                            // Display the large featured image
                            if ( has_post_thumbnail( $post->ID ) ) {
                                echo get_the_post_thumbnail( $post->ID, 'large', array( 'class' => 'download-thumbnail-large' ) );
                            }
                            ?>
                            <span class="download-title"><?php echo esc_html( $post->post_title ); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <script>
    // Initialize Masonry (ensure this runs on page load)
    var $grid = $('#resource-loop').masonry();

    // Simple accordion toggle functionality
    function toggleAccordion(trigger) {
        var content = trigger.nextElementSibling;

        if (content.style.display === "none" || content.style.display === "") {
            content.style.display = "block";
        } else {
            content.style.display = "none";
        }

        // Trigger Masonry re-layout after accordion toggles
        setTimeout(function() {
            $grid.masonry('layout');
        }, 300); // Add slight delay to ensure content is fully visible
    }
</script>

    <?php
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'posts_by_category_and_tag', 'display_posts_by_category_and_tag_shortcode' );



function display_category_image_shortcode( $atts ) {
    // Extract shortcode attributes
    $atts = shortcode_atts( array(
        'term_id' => '', // Term ID for the category
    ), $atts );

    // Return a blank image if no term_id is provided
    // if ( empty( $atts['term_id'] ) || ! is_numeric( $atts['term_id'] ) ) {
    //     return '<img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Blank Image" style="max-width:100%; height:auto;">';
    // }

    // Check if Pods is installed
    if ( ! class_exists( 'Pods' ) ) {
        return '<img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Blank Image" style="max-width:100%; height:auto;">';
    }

    // Get the term
    $term = get_term( $atts['term_id'], 'dlm_download_category' );

    // if ( ! $term || is_wp_error( $term ) ) {
    //     return '<img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Blank Image" style="max-width:100%; height:auto;">';
    // }

    // Get the category image using Pods
    $image_id = get_term_meta( $atts['term_id'], 'dlm_download_category_image', true );
    BugFu::log($image_id);


    // Validate the image ID
    if ( empty( $image_id ) ) {
        return;
    }

    // Get the image URL
    $image_data = wp_get_attachment_image_src( $image_id, 'large' ); // Specify the desired size
    $image_url = $image_data ? $image_data[0] : '';

    if ( ! $image_url ) {
        return;
    }

    // Output the image
    ob_start();
    ?>
    <div class="category-image">
        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" style="width:100%; height:auto;">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'category_image', 'display_category_image_shortcode' );





function redirect_category_to_latest_post() {
    // Check if we are on a category archive page and target the 'tips-tuesday' category
    if ( is_category( 'tips-tuesday' ) ) {

        // Prevent redirect loops
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        // Query for the latest post in the 'tips-tuesday' category
        $latest_post = get_posts( array(
            'category_name'  => 'tips-tuesday', // Category slug
            'posts_per_page' => 1,              // Fetch only the latest post
            'order'          => 'DESC',
            'orderby'        => 'date'
        ) );

        // If we found a post, redirect to its permalink
        if ( ! empty( $latest_post ) && isset( $latest_post[0] ) ) {
            $latest_post_url = get_permalink( $latest_post[0]->ID );

            // Make sure headers aren't already sent before redirecting
            if ( ! headers_sent() ) {
                wp_safe_redirect( $latest_post_url );
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'redirect_category_to_latest_post' );




function mens_world_champions_query( $query ) {
    // Check if Pods Framework is installed and active
    if ( ! class_exists('Pods') ) {
        return; // Exit early if Pods is not available
    }

    // Get the Pods object for the current post
    $pod = pods( 'post', get_the_ID() );

    // Ensure the Pods object is valid and fetch the 'post_downloads' relationship field
    if ( $pod ) {
        $downloads = $pod->field( 'post_downloads' );

        // Extract IDs or set to an empty array
        $post_ids = ! empty( $downloads ) ? wp_list_pluck( $downloads, 'ID' ) : [];

        // If there are valid post IDs, set them in the query
        if ( ! empty( $post_ids ) ) {
            $query->set( 'post__in', $post_ids );
            $query->set( 'orderby', 'post__in' );
        } else {
            // If no related downloads are found, set an invalid post__in to prevent results
            $query->set( 'post__in', [ 0 ] ); // No results will be returned
        }
    } else {
        // If Pods object is invalid, prevent any results
        $query->set( 'post__in', [ 0 ] );
    }
}
add_action( 'elementor/query/related_posts_query', 'mens_world_champions_query' );


function docs_downloads( $query ) {
    // Check if Pods Framework is installed and active
    if ( ! class_exists('Pods') ) {
        return; // Exit early if Pods is not available
    }

    // Get the Pods object for the current post
    $pod = pods( 'docs', get_the_ID() );
    BugFu::log($pod);

    // Ensure the Pods object is valid and fetch the 'post_downloads' relationship field
    if ( $pod ) {
        $downloads = $pod->field( 'doc_downloads' );
        BugFu::log($downloads);

        // Extract IDs or set to an empty array
        $post_ids = ! empty( $downloads ) ? wp_list_pluck( $downloads, 'ID' ) : [];

        // If there are valid post IDs, set them in the query
        if ( ! empty( $post_ids ) ) {
            $query->set( 'post__in', $post_ids );
            $query->set( 'orderby', 'post__in' );
        } else {
            // If no related downloads are found, set an invalid post__in to prevent results
            $query->set( 'post__in', [ 0 ] ); // No results will be returned
        }
    } else {
        // If Pods object is invalid, prevent any results
        $query->set( 'post__in', [ 0 ] );
    }
}
add_action( 'elementor/query/docs_downloads', 'docs_downloads' );


function custom_elementor_query_buddypress_group( $query ) {
    // Check if BuddyPress is active
    if ( ! function_exists( 'buddypress' ) || ! bp_is_group() ) {
        return;
    }

    // Get the current group ID
    $group_id = bp_get_current_group_id();
    BugFu::log($group_id);

    if ( empty( $group_id ) ) {
        // If no group ID is found, prevent results
        $query->set( 'post__in', [ 0 ] );
        return;
    }

    // Find a category where the name matches the group ID
    $category = get_term_by( 'name', $group_id, 'category' );
    BugFu::log($category);

    if ( $category && ! is_wp_error( $category ) ) {
        BugFu::log("PASS 1");
        // Set the query to fetch posts in the matched category
        $query->set( 'cat', $category->term_id ); // Set the category ID dynamically
        $query->set( 'posts_per_page', -1 ); // Fetch all posts in the category
    } else {
        // If no matching category is found, prevent results
        $query->set( 'post__in', [ 0 ] );
    }
}

add_action( 'elementor/query/bp_group_posts', 'custom_elementor_query_buddypress_group' );



// function rename_bp_group_feed_to_collaboration() {
//     // Ensure we are on a BuddyPress group page
//     if ( ! bp_is_group() || ! bp_is_active( 'groups' ) ) {
//         return; // Exit if we're not on a group page or groups are inactive
//     }

//     // Get the current group object
//     $group = groups_get_current_group();
//     if ( empty( $group ) || ! isset( $group->id ) ) {
//         return; // Exit if no valid group object is found
//     }

//     // Rename the "Activity" tab to "Collaboration"
//     global $bp;
//     $bp->groups->nav->edit_nav(
//         array( 'name' => __( 'Collaboration', 'textdomain' ) ),
//         'activity', // Slug for the "Feed/Activity" tab
//         'groups'
//     );
// }
// add_action( 'bp_setup_nav', 'rename_bp_group_feed_to_collaboration', 15 );


// function ps_rename_group_tabs() {
 
//     // if ( ! bp_is_group() ) {
//     //     return;
//     // }
    
//     buddypress()->groups->nav->edit_nav( array( 'name' => __( 'Collaboration', 'buddypress' ) ), 'feed', bp_current_item() );
// }
// add_action( 'bp_actions', 'ps_rename_group_tabs' );


?>


