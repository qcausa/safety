<?php

/**
 * Plugin Name: WooCommerce Checkout Custom
 * Description: Customizes WooCommerce checkout with BuddyPress group shipping integration
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: woocommerce-checkout-custom
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function wc_checkout_custom_init()
{
    // Ensure WooCommerce and BuddyPress are active
    if (!class_exists('WooCommerce') || !class_exists('BuddyPress')) {
        return;
    }

    class WC_Checkout_Custom
    {
        public function __construct()
        {
            // Add BuddyPress group dropdown to shipping fields
            add_filter('woocommerce_checkout_fields', array($this, 'customize_checkout_fields'), 5);
            add_filter('woocommerce_checkout_fields', array($this, 'custom_remove_checkout_fields'), 5);

            // Add AJAX handler for bulk address fetching
            add_action('wp_ajax_get_all_group_addresses', array($this, 'get_all_group_addresses'));
            add_action('wp_ajax_nopriv_get_all_group_addresses', array($this, 'get_all_group_addresses'));

            // Enqueue scripts
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

            // Force show shipping fields but hide default checkbox
            add_filter('woocommerce_ship_to_different_address_checked', '__return_true');
            add_action('wp_head', array($this, 'hide_default_shipping_checkbox'));

            // Add shipping details heading
            add_action('woocommerce_before_checkout_shipping_form', array($this, 'add_shipping_details_heading'));

            // Change billing details heading
            add_filter('gettext', array($this, 'change_billing_heading'), 20, 3);
        }

        public function custom_remove_checkout_fields($fields)
        {
            // Remove unnecessary billing fields
            unset($fields['billing']['billing_company']);
            unset($fields['billing']['billing_city']);
            unset($fields['billing']['billing_postcode']);
            unset($fields['billing']['billing_country']);
            unset($fields['billing']['billing_state']);
            unset($fields['billing']['billing_address_1']);
            unset($fields['billing']['billing_address_2']);

            // Make email and name fields readonly if user is logged in
            if (is_user_logged_in()) {
                $readonly_fields = array('billing_email', 'billing_first_name', 'billing_last_name');
                foreach ($readonly_fields as $field) {
                    $fields['billing'][$field]['custom_attributes'] = array(
                        'readonly' => 'readonly'
                    );
                    $fields['billing'][$field]['class'][] = 'readonly';
                }
            }

            return $fields;
        }

        public function customize_checkout_fields($fields)
        {
            // Add our custom shipping fields
            $bp_groups = groups_get_groups(array(
                'show_hidden' => false,
                'per_page'    => 9999,
            ));

            // Build group options array
            $group_options = array('' => __('Select a District', 'woocommerce-checkout-custom'));
            if (!empty($bp_groups['groups'])) {
                foreach ($bp_groups['groups'] as $group) {
                    $group_options[$group->id] = $group->name;
                }
            }

            // Add group select field
            $fields['shipping']['buddy_group'] = array(
                'type'        => 'select',
                'label'       => __('District', 'woocommerce-checkout-custom'),
                'required'    => true,
                'options'     => $group_options,
                'class'       => array('form-row-wide'),
                'priority'    => 1,
                'clear'       => true
            );

            // Add custom shipping checkbox right after the district select
            $fields['shipping']['custom_ship_to_different'] = array(
                'type'        => 'checkbox',
                'label'       => __('Ship to a different address?', 'woocommerce-checkout-custom'),
                'required'    => false,
                'class'       => array('form-row-wide'),
                'priority'    => 2,
                'clear'       => true,
                'id'          => 'custom-ship-to-different-address'
            );

            return $fields;
        }

        public function get_all_group_addresses()
        {
            check_ajax_referer('wc_checkout_custom', 'nonce');

            if (!isset($_POST['group_ids']) || !is_array($_POST['group_ids'])) {
                wp_send_json_error('Invalid group IDs');
            }

            $group_ids = array_map('intval', $_POST['group_ids']);
            $addresses = array();

            // Try to get cached addresses first
            foreach ($group_ids as $group_id) {
                $cache_key = 'wc_group_address_' . $group_id;
                $cached = wp_cache_get($cache_key);
                if ($cached !== false) {
                    $addresses[$group_id] = $cached;
                    // Remove from array to not query again
                    $group_ids = array_diff($group_ids, array($group_id));
                }
            }

            if (!empty($group_ids)) {
                global $wpdb;

                // Get all meta values in a single query
                $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
                $meta_values = $wpdb->get_results($wpdb->prepare("
                    SELECT group_id, meta_key, meta_value 
                    FROM {$wpdb->prefix}bp_groups_groupmeta 
                    WHERE group_id IN ($placeholders)
                    AND meta_key IN ('shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_postcode', 'shipping_country')
                ", $group_ids));

                // Organize results by group
                foreach ($group_ids as $group_id) {
                    $group_meta = array_filter($meta_values, function ($meta) use ($group_id) {
                        return $meta->group_id == $group_id;
                    });

                    $address = array(
                        'address_1' => '',
                        'address_2' => '',
                        'city' => '',
                        'state' => '',
                        'postcode' => '',
                        'country' => ''
                    );

                    foreach ($group_meta as $meta) {
                        $key = str_replace('shipping_', '', $meta->meta_key);
                        $address[$key] = $meta->meta_value;
                    }

                    $addresses[$group_id] = $address;

                    // Cache individual address
                    $cache_key = 'wc_group_address_' . $group_id;
                    wp_cache_set($cache_key, $address, '', 12 * HOUR_IN_SECONDS);
                }
            }

            wp_send_json_success($addresses);
        }

        public function enqueue_scripts()
        {
            if (!is_checkout()) {
                return;
            }

            wp_enqueue_script(
                'wc-checkout-custom',
                plugins_url('js/checkout-custom.js', __FILE__),
                array('jquery'),
                '1.0.0',
                true
            );

            wp_localize_script('wc-checkout-custom', 'wcCheckoutCustom', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('wc_checkout_custom')
            ));
        }

        public function add_shipping_details_heading()
        {
            echo '<h3>' . esc_html__('Shipping Details', 'woocommerce-checkout-custom') . '</h3>';
        }

        public function hide_default_shipping_checkbox()
        {
            if (!is_checkout()) {
                return;
            }
?>
            <style>
                #ship-to-different-address {
                    display: none !important;
                }

                .woocommerce-checkout .bb-wc-co #customer_details {
                    display: flex;
                    flex-direction: column;
                    gap: 60px;
                }

                .woocommerce-shipping-fields {
                    display: block !important;
                }

                /* Add some spacing around the custom checkbox */
                #custom-ship-to-different-address_field {
                    margin: 0 0 20px;
                    padding-top: 5px;
                }

                /* Style the shipping details heading */
                .woocommerce-shipping-fields h3:first-child {
                    margin-top: 0;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }

                /* Unified disabled field styles */
                .woocommerce-shipping-fields input:disabled,
                .woocommerce-shipping-fields select:disabled,
                .woocommerce-checkout input#billing_email[readonly],
                .woocommerce-checkout input#billing_first_name[readonly],
                .woocommerce-checkout input#billing_last_name[readonly] {
                    background-color: #fafafa !important;
                    border-color: #ddd !important;
                    cursor: not-allowed !important;
                    color: #444 !important;
                    opacity: 0.7 !important;
                    box-shadow: none !important;
                    -webkit-text-fill-color: #444 !important;
                }

                /* Remove autofill background color in Chrome */
                .woocommerce-checkout input#billing_email:-webkit-autofill,
                .woocommerce-checkout input#billing_first_name:-webkit-autofill,
                .woocommerce-checkout input#billing_last_name:-webkit-autofill,
                .woocommerce-checkout input#billing_email:-webkit-autofill:hover,
                .woocommerce-checkout input#billing_first_name:-webkit-autofill:hover,
                .woocommerce-checkout input#billing_last_name:-webkit-autofill:hover,
                .woocommerce-checkout input#billing_email:-webkit-autofill:focus,
                .woocommerce-checkout input#billing_first_name:-webkit-autofill:focus,
                .woocommerce-checkout input#billing_last_name:-webkit-autofill:focus {
                    -webkit-box-shadow: 0 0 0 30px #fafafa inset !important;
                    -webkit-text-fill-color: #444 !important;
                }
            </style>
<?php
        }

        public function change_billing_heading($translated_text, $text, $domain)
        {
            if ($domain === 'woocommerce' && $translated_text === 'Billing details') {
                $translated_text = __('Order Details', 'woocommerce-checkout-custom');
            }
            return $translated_text;
        }
    }

    new WC_Checkout_Custom();
}
add_action('plugins_loaded', 'wc_checkout_custom_init');
