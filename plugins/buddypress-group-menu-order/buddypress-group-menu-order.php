<?php
/**
 * Plugin Name: BuddyPress/BuddyBoss Group Menu Order
 * Description: Automatically assigns and manages menu order for BuddyPress/BuddyBoss groups.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: bp-group-menu-order
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk Assign 'menu_order' to Existing Groups on Plugin Activation
 *
 * This function assigns the group's ID as the 'menu_order' for all groups
 * that do not already have a 'menu_order' meta key set.
 *
 * @return void
 */
function bp_bulk_assign_menu_order_to_existing_groups() {
    // Retrieve all groups without a 'menu_order' meta key
    $groups = groups_get_groups( array(
        'per_page'   => 0, // Retrieve all groups
        'type'       => 'all',
        'meta_query' => array(
            array(
                'key'     => 'menu_order',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ) );

    if ( ! empty( $groups['groups'] ) ) {
        foreach ( $groups['groups'] as $group ) {
            groups_update_groupmeta( $group->id, 'menu_order', $group->id );
        }
        // Optionally, notify admins that the operation is complete
        error_log( 'Menu Order assigned to existing groups.' );
    }
}

/**
 * Register Plugin Activation Hook
 *
 * Ensures that 'menu_order' is assigned to existing groups upon plugin activation.
 */
register_activation_hook( __FILE__, 'bp_bulk_assign_menu_order_to_existing_groups' );

/**
 * Add Menu Order field to BuddyPress/BuddyBoss Group Editing Form
 *
 * @param BP_Groups_Group $group The group object being edited.
 */
function bp_add_group_menu_order_field_edit( $group ) {
    // Ensure the BugFu class exists for logging (optional)
    if ( class_exists( 'BugFu' ) ) {
        BugFu::log("bp_add_group_menu_order_field_edit");
        BugFu::log($group->id); // Log the group ID for debugging
    }

    // Retrieve existing menu_order value or set default to 0
    $group_menu_order = groups_get_groupmeta( $group->id, 'menu_order' );
    if ( empty( $group_menu_order ) ) {
        $group_menu_order = 0;
    }

    // Output the Menu Order field within a styled div to appear in the right column
    ?>
    <div class="bp-admin-side-section" style="padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <h3><?php esc_html_e( 'Menu Order', 'your-text-domain' ); ?></h3>
        <p>
            <label for="bp-group-menu-order"><?php esc_html_e( 'Menu Order:', 'your-text-domain' ); ?></label><br/>
            <input type="number" name="bp_group_menu_order" id="bp-group-menu-order" value="<?php echo esc_attr( $group_menu_order ); ?>" min="0" style="width: 100%;" />
        </p>
        <p class="description"><?php esc_html_e( 'Specify the order in which this group should appear. Lower numbers appear first.', 'your-text-domain' ); ?></p>
    </div>
    <?php
}
add_action( 'bp_groups_admin_edit', 'bp_add_group_menu_order_field_edit', 10, 1 );


/**
 * Modify BuddyPress Group Queries to Order by Menu Order
 */
function bp_order_groups_by_menu_order( $args ) {
    BugFu::log("bp_order_groups_by_menu_order");
    BugFu::log($args);
    // Only modify the query on specific contexts if needed
    // For example, only on the groups directory or specific group loops
    // Here, we'll apply it universally. Adjust as necessary.

    // Ensure that we order by menu_order meta
    $args['meta_key'] = 'menu_order';
    $args['orderby']  = 'meta_value';
    $args['order']    = 'ASC'; // or 'DESC' based on preference
    BugFu::log($args);

    return $args;
}
add_filter( 'bp_after_has_groups_parse_args', 'bp_order_groups_by_menu_order' , 999);



/**
 * Save Menu Order field on BuddyPress Group Update
 */
function bp_save_group_menu_order_field_edit( $group_id, $group_meta ) {
    BugFu::log("groups_details_updated");
    BugFu::log($_POST['bp_group_menu_order'] );
    if ( isset( $_POST['bp_group_menu_order'] ) && is_numeric( $_POST['bp_group_menu_order'] ) ) {
        $menu_order = intval( $_POST['bp_group_menu_order'] );
        groups_update_groupmeta( $group_id, 'menu_order', $menu_order );
    }
}
add_action( 'groups_details_updated', 'bp_save_group_menu_order_field_edit', 10, 2 );