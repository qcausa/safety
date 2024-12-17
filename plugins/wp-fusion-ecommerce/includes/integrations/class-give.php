<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Give extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'give';

		// Send plan data
		add_action( 'wpf_give_payment_complete', array( $this, 'send_order_data' ), 10, 2 );

		// Meta Box
		add_filter( 'give_metabox_form_data_settings', array( $this, 'add_settings' ), 20 );

		// Super secret admin / debugging tools
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ), 12 ); // 12 so we can match it with the ones added by the core plugin
		add_action( 'wpf_batch_give_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_give_ecom', array( $this, 'batch_step' ) );

	}

	/**
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  void
	 */

	public function send_order_data( $payment_id, $contact_id ) {

		if ( ! empty( give_get_meta( $payment_id, '_wpf_ec_complete', true ) ) ) {
			return true;
		}

		$payment_data = wp_fusion()->integrations->give->get_payment_data( $payment_id );

		$product_name = get_the_title( $payment_data['give_form_id'] );

		if ( ! empty( $payment_data['give_price_id'] ) ) {
			$product_name .= ' - ' . give_get_price_option_name( $payment_data['give_form_id'], $payment_data['give_price_id'], $payment_id );
		}

		// Get stored product ID
		$crm_product_id = false;

		if ( is_array( wp_fusion_ecommerce()->crm->supports ) && in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {

			$settings = get_post_meta( $payment_data['give_form_id'], 'wpf_settings_give', true );

			if ( ! empty( $settings[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
				$crm_product_id = $settings[ wp_fusion()->crm->slug . '_product_id' ];
			}

			if ( ! empty( $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $payment_data['give_price_id'] ] ) ) {
				$crm_product_id = $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $payment_data['give_price_id'] ];
			}

			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			if ( ! isset( $available_products[ $crm_product_id ] ) ) {
				$crm_product_id = false;
			}

			// See of an existing product matches by name
			if ( false === $crm_product_id ) {

				$crm_product_id = array_search( $product_name, $available_products );

			}
		}

		$payment = new Give_Payment( $payment_id );

		// Get user ID

		$user = get_user_by( 'email', $payment_data['user_email'] );

		if ( ! empty( $user ) ) {
			$user_id = $user->ID;
		} else {
			$user_id = 0;
		}

		$products = array(
			array(
				'id'             => $payment_data['give_form_id'],
				'name'           => $product_name,
				'price'          => $payment_data['price'],
				'qty'            => 1,
				'crm_product_id' => $crm_product_id,
			),
		);

		$order_args = array(
			'order_label'     => 'Give payment #' . $payment_id,
			'order_number'    => $payment_id,
			'order_edit_link' => admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details&id=' . $payment_id ),
			'payment_method'  => $payment->gateway,
			'user_email'      => $payment_data['user_email'],
			'products'        => $products,
			'line_items'      => array(),
			'total'           => $payment_data['price'],
			'currency'        => $payment_data['currency'],
			'currency_symbol' => '$',
			'order_date'      => strtotime( $payment_data['date'] ),
			'provider'        => 'give',
			'user_id'         => $user_id,
			'invoice_id'      => give_get_meta( $payment_id, '_wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true ),
		);

		$order_args = apply_filters( 'wpf_ecommerce_order_args', $order_args, $payment_id );

		if ( ! $order_args ) {
			return false; // Allow for cancelling
		}

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $payment_id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', 0, 'Error adding Give payment #' . $payment_id . ': ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return false;

		}

		if ( true === $result ) {

			give_insert_payment_note( $payment_id, wp_fusion()->crm->name . ' invoice successfully created.' );

		} elseif ( null != $result ) {

			// CRMs with invoice IDs
			give_insert_payment_note( $payment_id, wp_fusion()->crm->name . ' invoice #' . $result . ' successfully created.' );
			give_update_meta( $payment_id, '_wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order
		give_update_meta( $payment_id, '_wpf_ec_complete', true );

		do_action( 'wpf_ecommerce_complete', $payment_id, $result, $contact_id, $order_args );

	}

	/**
	 * Add product selection dropdown to Give settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function add_settings( $settings ) {

		if ( ! is_object( wp_fusion_ecommerce()->crm ) || ! is_array( wp_fusion_ecommerce()->crm->supports ) || ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return $settings;
		}

		foreach ( $settings['form_field_options']['fields'] as $i => $field ) {

			if ( isset( $field['id'] ) && $field['id'] == '_give_donation_levels' ) {

				$settings['form_field_options']['fields'][ $i ]['fields'][] = array(
					'name'     => sprintf( __( '%s Product', 'wp-fusion' ), wp_fusion()->crm->name ),
					'id'       => wp_fusion()->crm->slug . '_product_id',
					'type'     => 'select4',
					'callback' => array( $this, 'select_callback' ),
				);

			}
		}

		return $settings;

	}

	/**
	 * Render WPF select box
	 *
	 * @access  public
	 * @return  mixed HTML Output
	 */

	public function select_callback( $field ) {

		global $post;

		$defaults = array(
			wp_fusion()->crm->slug . '_product_id'       => false,
			wp_fusion()->crm->slug . '_product_id_level' => array(),
		);

		$settings = (array) get_post_meta( $post->ID, 'wpf_settings_give', true );

		$settings = array_merge( $defaults, $settings );

		$selected_product_id = $settings[ wp_fusion()->crm->slug . '_product_id' ];

		$name = 'wpf_settings_give[' . wp_fusion()->crm->slug . '_product_id]';

		if ( isset( $field['repeat'] ) ) {

			$field_sub_id = str_replace( '_give_donation_levels_', '', $field['id'] );
			$field_sub_id = str_replace( '_' . wp_fusion()->crm->slug . '_product_id', '', $field_sub_id );

			$name = 'wpf_settings_give[' . wp_fusion()->crm->slug . '_product_id_level][' . $field_sub_id . ']';

			if ( ! empty( $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $field_sub_id ] ) ) {
				$selected_product_id = $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $field_sub_id ];
			}
		}

		// See if there's a matching product name

		if ( empty( $selected_product_id ) ) {

			$product_name = $post->post_title . ' - ' . give_get_price_option_name( $post->ID, $field_sub_id );

			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			$selected_product_id = array_search( $product_name, $available_products );

		}

		echo '<fieldset class="give-field-wrap ' . esc_attr( $field['id'] ) . '_field"><span class="give-field-label">' . wp_kses_post( $field['name'] ) . '</span><legend class="screen-reader-text">' . wp_kses_post( $field['name'] ) . '</legend>';

		echo '<select class="select4-search" data-placeholder="Select a product" name="' . $name . '">';
		echo '<option value="">' . __( 'Select Product', 'wp-fusion' ) . '</option>';

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		asort( $available_products );

		foreach ( $available_products as $id => $name ) {
			echo '<option value="' . $id . '"' . selected( $id, $selected_product_id, false ) . '>' . esc_attr( $name ) . '</option>';
		}

		echo '</select>';

		echo give_get_field_description( $field );
		echo '</fieldset>';

	}

	/**
	 * Support utilities
	 *
	 * @access public
	 * @return void
	 */

	public function settings_page_init() {

		if ( isset( $_GET['give_reset_wpf_ec_complete'] ) ) {

			$args = array(
				'number'     => -1,
				'fields'     => 'ids',
				'status'     => array( 'publish', 'give_subscription' ),
				'order'      => 'ASC',
				'meta_query' => array(
					array(
						'key'     => '_wpf_ec_complete',
						'compare' => 'EXISTS',
					),
				),
			);

			$donation_ids = give_get_payments( $args );

			foreach ( $donation_ids as $donation_id ) {

				give_delete_meta( $donation_id, '_wpf_ec_complete' );

			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success:</strong><code>wpf_ec_complete</code> meta key removed from ' . count( $donation_ids ) . ' donations.</p></div>';

		}

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds WooCommerce checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['give_ecom'] = array(
			'label'         => 'Give donations (Ecommerce addon)',
			'title'         => 'Donations',
			'process_again' => true,
			'tooltip'       => 'Finds Give donations that have been processed by WP Fusion but have not been processed by the Ecommerce Addon, and syncs ecommerce data to ' . wp_fusion()->crm->name . '.',
		);

		return $options;

	}

	/**
	 * Counts total number of orders to be processed
	 *
	 * @access public
	 * @return int Count
	 */

	public function batch_init( $args ) {

		$query_args = array(
			'number'     => -1,
			'fields'     => 'ids',
			'status'     => array( 'publish', 'give_subscription' ),
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'     => '_wpf_complete',
					'compare' => 'EXISTS',
				),
			),
		);

		if ( ! empty( $args['skip_processed'] ) ) {

			$query_args['meta_query'][] = array(
				'key'     => '_wpf_ec_complete',
				'compare' => 'NOT EXISTS',
			);

		}

		return give_get_payments( $query_args );

	}

	/**
	 * Processes order actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $payment_id ) {

		$contact_id = give_get_meta( $payment_id, '_' . WPF_CONTACT_ID_META_KEY, true );

		if ( empty( $contact_id ) ) {
			wpf_log( 'notice', 0, 'Unable to sync Give donation ID <a href="' . admin_url( 'post.php?post=' . $payment_id . '&action=edit' ) . '">#' . $payment_id . '</a>, no contact ID found for payment.', array( 'source' => 'batch-process' ) );
			return;
		}

		$this->send_order_data( $payment_id, $contact_id );

	}


}

new WPF_EC_Give();
