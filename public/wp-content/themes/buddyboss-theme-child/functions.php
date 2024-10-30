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


 

?>


