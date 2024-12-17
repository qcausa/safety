<?php
/**
 * Plugin Name: WooCommerce Inventory System
 * Description: Adds custom WooCommerce order statuses "Checkout" and "Checkin".
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom WooCommerce Order Statuses: Checkout and Checkin
 */
function custom_register_order_statuses() {
    // Register "Checkout" status
    register_post_status( 'wc-checkout', array(
        'label'                     => 'Checkout',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Checkout <span class="count">(%s)</span>', 'Checkout <span class="count">(%s)</span>' )
    ) );

    // Register "Checkin" status
    register_post_status( 'wc-checkin', array(
        'label'                     => 'Checkin',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Checkin <span class="count">(%s)</span>', 'Checkin <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'custom_register_order_statuses' );

/**
 * Add Custom Order Statuses to WooCommerce
 */
function custom_add_order_statuses( $order_statuses ) {
    $order_statuses['wc-checkout'] = 'Checkout';
    $order_statuses['wc-checkin']  = 'Checkin';

    return $order_statuses;
}
add_filter( 'wc_order_statuses', 'custom_add_order_statuses' );

/**
 * Add Custom Order Status Styles to WooCommerce Admin
 */
function custom_order_status_admin_styles() {
    ?>
    <style>
        .order-status.status-checkout {
            color: #ffb347; /* Orange for Checkout */
        }
        .order-status.status-checkin {
            color: #32cd32; /* Green for Checkin */
        }
    </style>
    <?php
}
add_action( 'admin_head', 'custom_order_status_admin_styles' );

/**
 * Add Custom Order Statuses to WooCommerce Bulk Actions
 */
function custom_register_bulk_actions( $bulk_actions ) {
    $bulk_actions['mark_checkout'] = 'Change to Checkout';
    $bulk_actions['mark_checkin']  = 'Change to Checkin';
    return $bulk_actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'custom_register_bulk_actions' );

/**
 * Handle Bulk Actions for Custom Order Statuses
 */
function custom_handle_bulk_actions( $redirect_to, $action, $post_ids ) {
    if ( 'mark_checkout' === $action || 'mark_checkin' === $action ) {
        foreach ( $post_ids as $post_id ) {
            $order = wc_get_order( $post_id );
            if ( $order ) {
                $new_status = $action === 'mark_checkout' ? 'checkout' : 'checkin';
                $order->update_status( $new_status, 'Bulk status change.', true );
            }
        }
        $redirect_to = add_query_arg( 'bulk_custom_status_updated', count( $post_ids ), $redirect_to );
    }
    return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-shop_order', 'custom_handle_bulk_actions', 10, 3 );

/**
 * Admin Notice for Bulk Actions
 */
function custom_bulk_action_admin_notice() {
    if ( ! empty( $_REQUEST['bulk_custom_status_updated'] ) ) {
        $count = intval( $_REQUEST['bulk_custom_status_updated'] );
        printf( '<div id="message" class="updated notice is-dismissible"><p>%d orders updated to Checkout or Checkin status.</p></div>', $count );
    }
}
add_action( 'admin_notices', 'custom_bulk_action_admin_notice' );




// ------------------------------


/**
 * Add Custom Fields to Order Line Items in Admin
 */
function custom_add_line_item_fields( $item_id, $item, $product ) {
    $order = $item->get_order();
    $status = $order->get_status();

    // Show fields only when order status is 'checkout' or 'checkin'
    if ( in_array( $status, array( 'checkout', 'checkin' ) ) ) {
        // Checkout Quantity
        woocommerce_wp_text_input( array(
            'id'          => "custom_checkout_qty_{$item_id}",
            'label'       => __( 'Checkout Quantity', 'woocommerce' ),
            'value'       => wc_get_order_item_meta( $item_id, '_checkout_quantity', true ),
            'placeholder' => __( 'Enter Checkout Quantity', 'woocommerce' ),
            'type'        => 'number',
            'desc_tip'    => true,
            'description' => __( 'Quantity when checked out.', 'woocommerce' ),
            'custom_attributes' => array(
                'min' => '0' // Prevent values below 0
            ),
        ) );

        // Checkin Quantity
        woocommerce_wp_text_input( array(
            'id'          => "custom_checkin_qty_{$item_id}",
            'label'       => __( 'Checkin Quantity', 'woocommerce' ),
            'value'       => wc_get_order_item_meta( $item_id, '_checkin_quantity', true ),
            'placeholder' => __( 'Enter Checkin Quantity', 'woocommerce' ),
            'type'        => 'number',
            'desc_tip'    => true,
            'description' => __( 'Quantity when checked in.', 'woocommerce' ),
            'custom_attributes' => array(
                'min' => '0' // Prevent values below 0
            ),
        ) );
    }
}
add_action( 'woocommerce_after_order_itemmeta', 'custom_add_line_item_fields', 10, 3 );

/**
 * Save Custom Fields from Admin
 */
function custom_save_line_item_fields( $item_id, $item, $order_id ) {
    $order = wc_get_order( $order_id );
    $status = $order->get_status();

    // Save fields only if status is 'checkout' or 'checkin'
    if ( in_array( $status, array( 'checkout', 'checkin' ) ) ) {
        // Save Checkout Quantity
        if ( isset( $_POST[ "custom_checkout_qty_{$item_id}" ] ) ) {
            wc_update_order_item_meta( $item_id, '_checkout_quantity', sanitize_text_field( $_POST[ "custom_checkout_qty_{$item_id}" ] ) );
        }

        // Save Checkin Quantity
        if ( isset( $_POST[ "custom_checkin_qty_{$item_id}" ] ) ) {
            wc_update_order_item_meta( $item_id, '_checkin_quantity', sanitize_text_field( $_POST[ "custom_checkin_qty_{$item_id}" ] ) );
        }
    }
}
add_action( 'woocommerce_save_order_line_items', 'custom_save_line_item_fields', 10, 3 );

/**
 * Display Custom Fields in Admin (Replaces Default Quantity)
 */
function custom_display_line_item_fields_in_admin( $item_id, $item, $product ) {
    $order = $item->get_order();
    $status = $order->get_status();

    // Replace built-in Quantity field with custom fields for specific statuses
    if ( in_array( $status, array( 'checkout', 'checkin' ) ) ) {

        // Checkout Quantity
        $checkout_qty = wc_get_order_item_meta( $item_id, '_checkout_quantity', true );
        echo '<p><strong>' . __( 'Checkout Quantity', 'woocommerce' ) . ':</strong> ' . esc_html( $checkout_qty ) . '</p>';

        // Checkin Quantity
        $checkin_qty = wc_get_order_item_meta( $item_id, '_checkin_quantity', true );
        echo '<p><strong>' . __( 'Checkin Quantity', 'woocommerce' ) . ':</strong> ' . esc_html( $checkin_qty ) . '</p>';

        // Hide the built-in quantity view (if displayed elsewhere)
        echo '<style>.quantity .view { display: none; }</style>';
    }
}
add_action( 'woocommerce_order_item_meta_start', 'custom_display_line_item_fields_in_admin', 10, 3 );

// /**
//  * Display Custom Fields on the Frontend Order View
//  */
// function custom_display_line_item_fields_on_frontend( $item_id, $item, $order ) {
//     // Checkout Quantity
//     $checkout_qty = wc_get_order_item_meta( $item_id, '_checkout_quantity', true );
//     if ( $checkout_qty ) {
//         echo '<p><strong>' . __( 'Checkout Quantity', 'woocommerce' ) . ':</strong> ' . esc_html( $checkout_qty ) . '</p>';
//     }

//     // Checkin Quantity
//     $checkin_qty = wc_get_order_item_meta( $item_id, '_checkin_quantity', true );
//     if ( $checkin_qty ) {
//         echo '<p><strong>' . __( 'Checkin Quantity', 'woocommerce' ) . ':</strong> ' . esc_html( $checkin_qty ) . '</p>';
//     }
// }
// add_action( 'woocommerce_order_item_meta_end', 'custom_display_line_item_fields_on_frontend', 10, 3 );

/**
 * Autofill Checkout Quantity When Status Changes to Checkout
 */
function custom_autofill_checkout_quantity( $order_id, $old_status, $new_status ) {
    if ( 'checkout' === $new_status ) {
        $order = wc_get_order( $order_id );
        foreach ( $order->get_items() as $item_id => $item ) {
            $checkout_qty = wc_get_order_item_meta( $item_id, '_checkout_quantity', true );

            // If Checkout Quantity is empty, autofill from the base quantity
            if ( empty( $checkout_qty ) ) {
                $base_qty = $item->get_quantity(); // Base WooCommerce quantity
                wc_update_order_item_meta( $item_id, '_checkout_quantity', $base_qty );
            }
        }
    }
}
add_action( 'woocommerce_order_status_changed', 'custom_autofill_checkout_quantity', 10, 3 );



/**
 * Shortcode to display orders with "Checkout" status and a Checkin button
 */
function custom_checkout_orders_table_shortcode() {
    // Check for POST request to handle the "Checkin" action
    if ( isset( $_POST['custom_checkin_order_id'] ) ) {
        $order_id = intval( $_POST['custom_checkin_order_id'] );
        $order = wc_get_order( $order_id );

        if ( $order && $order->get_status() === 'checkout' ) {
            $order->update_status( 'checkin', 'Order status updated to Checkin.' );
            wc_add_notice( "Order #{$order_id} has been checked in successfully.", 'success' );
        }
    }

    // Query for orders with "Checkout" status
    $args = array(
        'status'   => 'checkout',
        'limit'    => -1, // Get all orders with this status
    );

    $orders = wc_get_orders( $args );

    if ( empty( $orders ) ) {
        return '<p>No orders with Checkout status found.</p>';
    }

    ob_start();

    // Display the table
    ?>
    <form method="post">
        <table style="width: 100%; border-collapse: collapse;" border="1">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Checkout Quantity</th>
                    <th>Checkin Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $orders as $order ) : ?>
                    <tr>
                        <td>#<?php echo esc_html( $order->get_id() ); ?></td>
                        <td><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
                        <td>
                            <?php
                            foreach ( $order->get_items() as $item_id => $item ) {
                                $checkout_qty = wc_get_order_item_meta( $item_id, '_checkout_quantity', true );
                                echo '<p>' . esc_html( $item->get_name() . ': ' . ( $checkout_qty ? $checkout_qty : '0' ) ) . '</p>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            foreach ( $order->get_items() as $item_id => $item ) {
                                $checkin_qty = wc_get_order_item_meta( $item_id, '_checkin_quantity', true );
                                echo '<p>' . esc_html( $item->get_name() . ': ' . ( $checkin_qty ? $checkin_qty : '0' ) ) . '</p>';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="submit" name="custom_checkin_order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
                                Checkin
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode( 'checkout_orders_table', 'custom_checkout_orders_table_shortcode' );

add_shortcode('my_account_section', 'shortcode_my_account_section');

function shortcode_my_account_section($atts) {
    extract(shortcode_atts(['section' => ''], $atts));
    ob_start();
    do_action('woocommerce_account_' . $section . '_endpoint');
    return ob_get_clean();
}


/**
 * Add a Checkin Button to the WooCommerce View Order Page for Orders with "Checkout" Status
 */
function custom_add_checkin_button_on_view_order( $order_id ) {
    $order = wc_get_order( $order_id );

    // Ensure we have a valid order and the status is 'checkout'
    if ( ! $order || $order->get_status() !== 'checkout' ) {
        return;
    }

    // Handle Checkin Button Submission
    if ( isset( $_POST['custom_checkin_order'] ) && wp_verify_nonce( $_POST['custom_checkin_nonce'], 'custom_checkin_action' ) ) {
        // Update order status to 'checkin'
        $order->update_status( 'checkin', 'Order has been checked in by the user.' );

        // Redirect to avoid form resubmission
        wp_redirect( esc_url( wc_get_endpoint_url( 'view-order', $order_id ) ) );
        exit;
    }

    // Display the Checkin Button
    ?>
     <div style="text-align: right; margin-top: 20px;">
        <form method="post" style="display: inline-block;">
            <?php wp_nonce_field( 'custom_checkin_action', 'custom_checkin_nonce' ); ?>
            <button type="submit" name="custom_checkin_order" class="button alt" style="padding: 10px 20px;">
                <?php _e( 'Checkin Order', 'woocommerce' ); ?>
            </button>
        </form>
    </div>
    <?php
}
add_action( 'woocommerce_view_order', 'custom_add_checkin_button_on_view_order', 50 );

/**
 * Change button text to "Checkin Order" for orders with "Checkout" status in My Account > Orders table.
 */
function custom_modify_order_actions( $actions, $order ) {
    // Check if the order status is 'checkout'
    if ( $order->get_status() === 'checkout' ) {
        // Modify the "View" button text to "Checkin Order"
        if ( isset( $actions['view'] ) ) {
            $actions['view']['name'] = __( 'Checkin Order', 'woocommerce' );
        }
    }
    return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', 'custom_modify_order_actions', 10, 2 );




/**
 * Add Checkin Quantity input fields to the "Qty" column on View Order page.
 */
function custom_modify_order_item_quantity_html( $quantity_html, $item ) {
    $order = $item->get_order();

    // Only modify when the order status is "checkout"
    if ( $order && $order->get_status() === 'checkout' ) {
        $item_id = $item->get_id();
        $checkout_qty = wc_get_order_item_meta( $item_id, '_checkout_quantity', true );
        $checkin_qty  = wc_get_order_item_meta( $item_id, '_checkin_quantity', true );

        // Create Checkin Quantity input field
        ob_start();
        ?>
        <div style="margin-top: 5px;">
            <label style="font-size: 12px; font-weight: bold; display: block;">
                <?php _e( 'Checkin Quantity:', 'woocommerce' ); ?>
            </label>
            <input type="number" name="checkin_qty[<?php echo esc_attr( $item_id ); ?>]" 
                   value="<?php echo esc_attr( $checkin_qty ); ?>" 
                   min="0" step="1" style="width: 80px; padding: 2px;" />
        </div>
        <?php
        $checkin_field_html = ob_get_clean();

        // Append the Checkin Quantity input field to the default quantity display
        $quantity_html .= '<div style="margin-top: 5px;">' . __( 'Checkout Quantity:', 'woocommerce' ) . ' ' . esc_html( $checkout_qty ) . '</div>';
        $quantity_html .= $checkin_field_html;
    }

    return $quantity_html;
}
add_filter( 'woocommerce_order_item_quantity_html', 'custom_modify_order_item_quantity_html', 10, 2 );

/**
 * Save Checkin Quantities when submitted on View Order page.
 */
function custom_save_checkin_quantities_on_view_order() {
    if ( isset( $_POST['checkin_qty'] ) && is_user_logged_in() ) {
        $checkin_quantities = $_POST['checkin_qty'];

        foreach ( $checkin_quantities as $item_id => $checkin_qty ) {
            $checkin_qty = max( 0, intval( $checkin_qty ) ); // Ensure non-negative value
            wc_update_order_item_meta( $item_id, '_checkin_quantity', $checkin_qty );
        }

        // Redirect to prevent resubmission
        wp_redirect( esc_url_raw( wc_get_endpoint_url( 'view-order', get_query_var( 'view-order' ) ) ) );
        exit;
    }
}
add_action( 'template_redirect', 'custom_save_checkin_quantities_on_view_order' );





/**
 * Hide the sidebar on WooCommerce View Order page.
 */
function custom_hide_sidebar_on_view_order_page() {
    if ( is_wc_endpoint_url( 'view-order' ) ) {
        ?>
        <style>
            /* Hide sidebar container */
            .woocommerce-MyAccount-navigation {
                display: none !important;
            }

            /* Make content full width */
            .woocommerce-MyAccount-content {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
            }
        </style>
        <?php
    }
}
add_action( 'wp_head', 'custom_hide_sidebar_on_view_order_page' );


/**
 * Add a "View All Orders" button at the top of the WooCommerce View Order page.
 */
function custom_add_view_all_orders_button( $order_id ) {
    if ( is_wc_endpoint_url( 'view-order' ) ) { // Confirm we're on the View Order page
        ?>
        <div style="margin-bottom: 20px;">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"
               class="button alt"
               style="display: inline-block; padding: 10px 20px; background: #0071a1; color: #fff; border-radius: 5px; text-decoration: none;">
                <?php esc_html_e( 'View All Orders', 'woocommerce' ); ?>
            </a>
        </div>
        <?php
    }
}
add_action( 'woocommerce_view_order', 'custom_add_view_all_orders_button', 5 );








// /**
//  * Remove "Addresses" and "Downloads" from WooCommerce My Account menu.
//  */
// function custom_remove_my_account_menu_items( $menu_items ) {
//     // Unset the items you want to remove
//     unset( $menu_items['downloads'] ); // Remove "Downloads"
//     unset( $menu_items['edit-address'] ); // Remove "Addresses"

//     return $menu_items;
// }
// add_filter( 'woocommerce_account_menu_items', 'custom_remove_my_account_menu_items' );
