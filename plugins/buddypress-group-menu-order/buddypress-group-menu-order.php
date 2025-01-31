<?php

/**
 * Plugin Name: BuddyPress/BuddyBoss Group Menu Order
 * Description: Automatically assigns and manages menu order for BuddyPress/BuddyBoss groups.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: bp-group-menu-order
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk Assign 'menu_order' to Existing Groups on Plugin Activation
 */
function bp_bulk_assign_menu_order_to_existing_groups()
{
    $groups = groups_get_groups(array(
        'per_page'   => 0,
        'type'       => 'all',
        'meta_query' => array(
            array(
                'key'     => 'menu_order',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ));

    if (!empty($groups['groups'])) {
        foreach ($groups['groups'] as $group) {
            groups_update_groupmeta($group->id, 'menu_order', $group->id);
        }
    }
}
register_activation_hook(__FILE__, 'bp_bulk_assign_menu_order_to_existing_groups');

/**
 * Add Menu Order field to Group Editing Form
 */
function bp_add_group_menu_order_field_edit($group)
{
    $group_menu_order = groups_get_groupmeta($group->id, 'menu_order');
    if (empty($group_menu_order)) {
        $group_menu_order = 0;
    }
?>
    <div class="bp-admin-side-section" style="padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <h3><?php esc_html_e('Menu Order', 'your-text-domain'); ?></h3>
        <p>
            <label for="bp-group-menu-order"><?php esc_html_e('Menu Order:', 'your-text-domain'); ?></label><br />
            <input type="number" name="bp_group_menu_order" id="bp-group-menu-order" value="<?php echo esc_attr($group_menu_order); ?>" min="0" style="width: 100%;" />
        </p>
        <p class="description"><?php esc_html_e('Specify the order in which this group should appear. Lower numbers appear first.', 'your-text-domain'); ?></p>
    </div>
<?php
}
add_action('bp_groups_admin_edit', 'bp_add_group_menu_order_field_edit', 10, 1);

/**
 * Sort groups on frontend display
 */
function bp_sort_groups_by_menu_order($has_groups)
{
    global $groups_template;

    if (!isset($groups_template) || empty($groups_template->groups)) {
        return $has_groups;
    }

    // Get all menu orders first
    $menu_orders = array();
    foreach ($groups_template->groups as $group) {
        $menu_orders[$group->id] = (int) groups_get_groupmeta($group->id, 'menu_order');
    }

    // Sort the groups
    usort($groups_template->groups, function ($a, $b) use ($menu_orders) {
        $a_order = isset($menu_orders[$a->id]) ? $menu_orders[$a->id] : 0;
        $b_order = isset($menu_orders[$b->id]) ? $menu_orders[$b->id] : 0;

        if ($a_order === $b_order) {
            return strcmp($a->name, $b->name);
        }
        return $a_order - $b_order;
    });

    return $has_groups;
}

// Remove existing filters and add our sorting
remove_all_filters('bp_has_groups');
add_filter('bp_has_groups', 'bp_sort_groups_by_menu_order', 999);

/**
 * Save Menu Order field
 */
function bp_save_group_menu_order_field_edit($group_id)
{
    if (isset($_POST['bp_group_menu_order']) && is_numeric($_POST['bp_group_menu_order'])) {
        $menu_order = intval($_POST['bp_group_menu_order']);
        groups_update_groupmeta($group_id, 'menu_order', $menu_order);
    }
}
add_action('groups_details_updated', 'bp_save_group_menu_order_field_edit', 10, 1);
