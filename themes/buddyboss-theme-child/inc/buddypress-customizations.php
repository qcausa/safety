<?php

/**
 * BuddyPress Customizations
 *
 * @package BuddyBoss Child
 */

/**
 * Add a custom tab to BuddyPress Groups
 */
function custom_bp_group_new_tab()
{
    if (!bp_is_group()) {
        return; // Avoid errors if not on a group page
    }

    $current_group = groups_get_current_group();
    $parent_url = bp_get_group_permalink($current_group);
    $parent_slug = bp_get_current_group_slug();

    if (empty($current_group)) {
        return; // Exit if no group context is available
    }

    // Register a new sub-navigation item (tab) for each group
    bp_core_new_subnav_item(array(
        'name'                  => __('Dashboard', 'textdomain'), // Tab Name
        'slug'                  => 'custom-landing',             // Unique Slug for the Tab
        'parent_url'            => bp_get_group_permalink($current_group), // Parent Group URL
        'parent_slug'           => bp_get_current_group_slug(),  // Parent Slug
        'screen_function'       => 'custom_bp_group_tab_screen', // Callback function
        'position'              => 0,                            // Set position to 0 to make it the first tab
        'default_subnav_slug'   => 'custom-landing',             // Define as the default tab
    ));
}
add_action('bp_setup_nav', 'custom_bp_group_new_tab');

/**
 * Screen Function for the Custom Tab
 */
function custom_bp_group_tab_screen()
{
    add_action('bp_template_content', 'custom_bp_group_tab_content');
    bp_core_load_template('groups/single/plugins');
}

/**
 * Content for the Custom Tab - Load Elementor Template Dynamically
 */
function custom_bp_group_tab_content()
{
    echo '<div id="custom-landing-tab">';

    // Check if we are on a BuddyPress group landing tab
    if (function_exists('bp_is_group') && bp_is_group() && bp_current_action() === 'custom-landing') {

        // Map of group IDs to Elementor template IDs
        $group_to_template_map = array(
            8  => 1253, // Group ID 8 -> Template ID 1253
            12 => 3055, // Group ID 12 -> Template ID 3055
            // Add more mappings as needed
        );

        // Get the current group ID
        $group_id = bp_get_current_group_id();

        // Determine the template ID for the current group
        $template_id = isset($group_to_template_map[$group_id]) ? $group_to_template_map[$group_id] : null;

        if ($template_id && class_exists('\Elementor\Plugin')) {
            // Render the Elementor template dynamically
            echo Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);
        }
    }

    echo '</div>';
}
define('BP_GROUPS_DEFAULT_EXTENSION', 'custom-landing');

/**
 * Redirect BuddyPress Group Root to the Custom Landing Tab
 */
function custom_bp_group_default_tab_redirect()
{
    if (bp_is_group() && ! bp_is_group_admin_page() && bp_is_current_action('')) {
        $group_permalink = bp_get_group_permalink(groups_get_current_group());
        wp_redirect($group_permalink . 'custom-landing/'); // Redirect to the Custom Landing tab
        exit;
    }
}
add_action('bp_actions', 'custom_bp_group_default_tab_redirect');

/**
 * Add Menu Order field to BuddyPress Group Creation Form
 */
function bp_add_group_menu_order_field()
{
?>
    <div class="bp-widget">
        <label for="group-menu-order"><?php _e('Menu Order', 'your-text-domain'); ?></label>
        <input type="number" name="group_menu_order" id="group-menu-order" value="0" min="0" />
        <p class="description"><?php _e('Specify the order in which this group should appear. Lower numbers appear first.', 'your-text-domain'); ?></p>
    </div>
<?php
}
add_action('bp_after_group_details_creation_step', 'bp_add_group_menu_order_field');

/**
 * Save Menu Order field on BuddyPress Group Creation
 */
function bp_save_group_menu_order_field($group_id, $group_meta)
{
    if (isset($_POST['group_menu_order']) && is_numeric($_POST['group_menu_order'])) {
        $menu_order = intval($_POST['group_menu_order']);
        groups_update_groupmeta($group_id, 'menu_order', $menu_order);
    }
}
add_action('groups_create_group', 'bp_save_group_menu_order_field', 10, 2);

/**
 * Register custom metaboxes for BuddyPress/BuddyBoss Groups.
 */
function bp_register_custom_group_metaboxes($group)
{
    // Get the group type post type.
    $group_type_post_type = bp_groups_get_group_type_post_type();

    // Check if the function exists to prevent errors.
    if (!$group_type_post_type) {
        return;
    }

    // Add the Menu Order metabox.
    add_meta_box(
        'bp-group-menu-order',                     // Metabox ID
        __('Menu Order', 'your-text-domain'),      // Title
        'bp_group_menu_order_metabox_callback',    // Callback function
        null,                                      // Screen (post type)
        'side',                                    // Context (side, normal, advanced)
        'default'                                  // Priority (default, high, low)
    );
}
add_action('bp_groups_admin_meta_boxes', 'bp_register_custom_group_metaboxes', 1);

/**
 * Callback function to render the Menu Order metabox.
 *
 * @param WP_Post $post The current post object.
 */
function bp_group_menu_order_metabox_callback($post)
{
    // Add a nonce field for security.
    wp_nonce_field('bp_save_group_menu_order', 'bp_group_menu_order_nonce');

    // Retrieve the existing menu_order value, if any.
    $menu_order = groups_get_groupmeta($post->ID, 'menu_order');
    if (empty($menu_order)) {
        $menu_order = 0; // Default value
    }

    echo '<input type="number" name="group_menu_order" value="' . esc_attr($menu_order) . '" min="0" />';
}

/**
 * Assign a Contact post to a category when the contact_division changes.
 */
function custom_assign_contact_to_division_category($pieces, $is_new_item, $id)
{
    BugFu::log("custom_assign_contact_to_division_category");
    $params = $pieces['params'];
    BugFu::log($params);
    BugFu::log($params->pod);

    // Check if the pod is "contacts" (your custom post type)
    if ('contact' !== $params->pod) {
        return;
    }

    // Get the new value of the contact_division relationship field
    $new_divisions_ids = isset($pieces['fields']['contact_division']['value']) ? $pieces['fields']['contact_division']['value'] : null;
    BugFu::log($new_divisions_ids);

    // Ensure $new_divisions is an array
    if (!is_array($new_divisions_ids)) {
        $new_divisions_ids = !empty($new_divisions_ids) ? array($new_divisions_ids) : array();
    }

    // Retrieve the old divisions to detect changes
    $old_divisions = get_post_meta($id, 'contact_division', true);

    // Check if there are any changes
    if ($new_divisions_ids === $old_divisions) {
        return;
    }

    // Update the post meta to save the new divisions value
    update_post_meta($id, 'contact_division', $new_divisions_ids);

    // Prepare an array to hold term IDs
    $term_ids = array();

    // Loop through each division
    foreach ($new_divisions_ids as $new_divisions_id) {
        if (empty($new_divisions_id)) {
            continue;
        }
        BugFu::log($new_divisions_id);

        // Get the division name (post title)
        $division_name = get_the_title($new_divisions_id);
        BugFu::log($division_name);

        // Check if the category exists
        $division_term = get_term_by('name', $division_name, 'category');

        // If the category doesn't exist, create it
        if (!$division_term) {
            $new_term = wp_insert_term($division_name, 'category');
            if (!is_wp_error($new_term)) {
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
    if (!empty($term_ids)) {
        wp_set_post_terms($id, $term_ids, 'category', false);
    }
}
add_action('pods_api_post_save_pod_item', 'custom_assign_contact_to_division_category', 10, 3);
