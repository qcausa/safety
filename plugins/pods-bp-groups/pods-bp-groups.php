<?php

/**
 * Plugin Name: Pods - Buddypress Groups
 * Description: Adds custom fields to BuddyPress groups using Pods
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: pods-custom
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pods_Custom
{
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        // Check if Pods is active
        if (!defined('PODS_VERSION')) {
            return;
        }

        // Register hooks
        add_action('init', array($this, 'register_bp_groups_pod'), 20);

        // Add fields to BP Group Admin Edit screen
        add_action('bp_groups_admin_meta_boxes', array($this, 'add_group_meta_box'));
        add_action('bp_group_admin_edit_after', array($this, 'save_group_meta_box'));
    }

    public function register_bp_groups_pod()
    {
        // Only register if BuddyPress Groups is active
        if (!function_exists('groups_get_groups')) {
            return;
        }

        // Register BP Groups as a Pod
        $args = array(
            'name' => 'bp_groups',
            'label' => __('BuddyPress Groups', 'pods-custom'),
            'type' => 'table',
            'storage' => 'table',
            'table' => 'bp_groups',
            'meta_table' => 'bp_groups_groupmeta',
            'field_id' => 'id',
            'field_index' => 'name',
            'meta_field_id' => 'group_id',
            'meta_field_index' => 'meta_key',
            'meta_field_value' => 'meta_value',
            'pod_table' => false, // Don't create a new table
            'fields' => array(
                array(
                    'name' => 'id',
                    'label' => __('ID', 'pods-custom'),
                    'type' => 'number',
                    'options' => array(
                        'readonly' => true
                    )
                ),
                array(
                    'name' => 'name',
                    'label' => __('Name', 'pods-custom'),
                    'type' => 'text',
                    'options' => array(
                        'readonly' => true
                    )
                ),
                array(
                    'name' => 'description',
                    'label' => __('Description', 'pods-custom'),
                    'type' => 'text',
                    'options' => array(
                        'readonly' => true
                    )
                ),
                array(
                    'name' => 'shipping_address_1',
                    'label' => __('Address Line 1', 'pods-custom'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'shipping_address_2',
                    'label' => __('Address Line 2', 'pods-custom'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'shipping_city',
                    'label' => __('City', 'pods-custom'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'shipping_state',
                    'label' => __('State', 'pods-custom'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'shipping_postcode',
                    'label' => __('Postal Code', 'pods-custom'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'shipping_country',
                    'label' => __('Country', 'pods-custom'),
                    'type' => 'text'
                )
            )
        );

        pods_register_type('table', 'bp_groups', $args);
    }

    public function add_group_meta_box()
    {
        add_meta_box(
            'bp_group_shipping_address',
            __('Shipping Address', 'pods-custom'),
            array($this, 'render_group_meta_box'),
            get_current_screen()->id,
            'normal'
        );
    }

    public function render_group_meta_box()
    {
        $group_id = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
        if (!$group_id) return;

        wp_nonce_field('bp_group_shipping_address', 'bp_group_shipping_address_nonce');

        $fields = array(
            'shipping_address_1' => __('Address Line 1', 'pods-custom'),
            'shipping_address_2' => __('Address Line 2', 'pods-custom'),
            'shipping_city' => __('City', 'pods-custom'),
            'shipping_state' => __('State', 'pods-custom'),
            'shipping_postcode' => __('Postal Code', 'pods-custom'),
            'shipping_country' => __('Country', 'pods-custom')
        );

?>
        <table class="form-table">
            <?php foreach ($fields as $field_id => $field_label): ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                    </th>
                    <td>
                        <input type="text"
                            name="<?php echo esc_attr($field_id); ?>"
                            id="<?php echo esc_attr($field_id); ?>"
                            value="<?php echo esc_attr(groups_get_groupmeta($group_id, $field_id)); ?>"
                            class="regular-text" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <style>
            #bp_group_shipping_address .form-table th {
                padding: 20px 10px 20px 0;
                width: 200px;
            }

            #bp_group_shipping_address .form-table td {
                padding: 15px 10px;
            }

            #bp_group_shipping_address input[type="text"] {
                width: 100%;
                max-width: 400px;
            }
        </style>
<?php
    }

    public function save_group_meta_box()
    {
        $group_id = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
        if (!$group_id) return;

        // Verify nonce
        if (
            !isset($_POST['bp_group_shipping_address_nonce']) ||
            !wp_verify_nonce($_POST['bp_group_shipping_address_nonce'], 'bp_group_shipping_address')
        ) {
            return;
        }

        $fields = array(
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_state',
            'shipping_postcode',
            'shipping_country'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                groups_update_groupmeta($group_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

new Pods_Custom();
