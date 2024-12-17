<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Gravity_Forms extends WPF_EC_Integrations_Base {

	/**
	 * The integration slug.
	 *
	 * @since 1.19.0
	 * @var string
	 */
	public $slug = 'gravity-forms';

	/**
	 * Get it started.
	 *
	 * @since 1.19.0
	 */
	public function init() {

		// Send order data
		add_action( 'wpf_gforms_feed_complete', array( $this, 'send_order_data' ), 10, 5 );

		// Register settings.
		add_filter( 'wpf_gform_settings_fields', array( $this, 'settings_fields' ), 10, 2 );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ), 12 ); // 12 so we can match it with the ones added by the core plugin
		add_action( 'wpf_batch_gravityforms_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_gravityforms_ecom', array( $this, 'batch_step' ) );

	}


	/**
	 * Sync the order data after payment.
	 *
	 * @since 1.19.0
	 *
	 * @param array  $feed          The feed.
	 * @param array  $entry         The entry.
	 * @param array  $form          The form.
	 * @param string $contact_id    The contact ID in the CRM.
	 * @param string $email_address The contact's email address in the CRM.
	 */
	public function send_order_data( $feed, $entry, $form, $contact_id, $email_address ) {

		if ( empty( $feed['meta']['enhanced_ecommerce'] ) ) {
			return;
		}

		// Build array of products
		$products = array();

		// Array of line items
		$line_items = array();

		$product_fields = \GFCommon::get_product_fields( $form, $entry );

		foreach ( $product_fields['products'] as $key => $field ) {

			if ( ! empty( $field['isCoupon'] ) ) {

				$line_items[] = array(
					'type'        => 'discount',
					'price'       => GFCommon::to_number( $field['price'] ),
					'title'       => 'Discount',
					'description' => 'Gravity Forms Coupon Code ' . $field['couponCode'],
					'code'        => $field['couponCode'],
				);
			} else {

				$product_data = array(
					'id'    => $key,
					'name'  => $field['name'],
					'sku'   => false,
					'qty'   => intval( $field['quantity'] ),
					'price' => GFCommon::to_number( $field['price'] ),
				);

				if ( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
					$product_data['id']     = $field['options'][0]['id'];
					$product_data['price'] += GFCommon::to_number( $field['options'][0]['price'] );
					$product_data['name']  .= ' - ' . $field['options'][0]['option_label'];
				}

				$products[] = $product_data;
			}
		}


		if ( ! empty( $product_fields['shipping'] ) ) {

			if ( isset( $product_fields['shipping']['price'] ) && $product_fields['shipping']['price'] > 0 ) {
				$line_items[] = array(
					'type'        => 'shipping',
					'price'       => GFCommon::to_number( $product_fields['shipping']['price'] ),
					'title'       => 'Shipping',
					'description' => $product_fields['shipping']['name'],
				);
			}
		}

		// Get the total.

		if ( ! isset( $feed['meta']['deal_value'] ) || 'form_total' === $feed['meta']['deal_value'] ) {
			$total = $entry['payment_amount'];
		} elseif ( 0 === $feed['meta']['deal_value'] ) {
			$total = 0;
		} else {

			// Use a product for the total.
			$total = GFCommon::to_number( $product_fields['products'][ $feed['meta']['deal_value'] ]['price'] );

		}

		// Get the title
		$label = GFCommon::replace_variables( $feed['meta']['deal_title'], $form, $entry, false, false, false, 'text' );

		$order_edit_link = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $entry['form_id'] . '&lid=' . $entry['id'] . '' );
		$currency        = RGCurrency::get_currency( $entry['currency'] );

		$order_args = array(
			'order_label'     => $label,
			'order_number'    => $entry['id'],
			'order_edit_link' => $order_edit_link,
			'payment_method'  => $entry['payment_method'],
			'products'        => $products,
			'deal_stage'      => isset( $feed['meta']['deal_stage'] ) ? $feed['meta']['deal_stage'] : false,
			'user_email'      => $email_address,
			'line_items'      => $line_items,
			'total'           => $total,
			'currency'        => $entry['currency'],
			'currency_symbol' => ( ! empty( $currency['symbol_left'] ) ? $currency['symbol_left'] : $currency['symbol_right'] ),
			'order_date'      => strtotime( $entry['payment_date'] ),
			'provider'        => 'gravityforms',
			'status'          => $entry['status'],
			'user_id'         => $entry['created_by'],
			'invoice_id'      => gform_get_meta( $entry['id'], '_wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id' ),
		);

		$order_args = apply_filters( 'wpf_ecommerce_order_args', $order_args, $entry['id'] );

		if ( ! $order_args ) {
			return false; // Allow for cancelling
		}

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $entry['id'], $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( $result->get_error_code(), $entry['created_by'], 'Error adding Gravityforms Order <a href="' . $order_edit_link . '" target="_blank">#' . $entry['id'] . '</a>: ' . $result->get_error_message() );
			\GFAPI::add_note( $entry['id'], $entry['created_by'], 'WP Fusion', 'Error creating order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );
			return false;

		} elseif ( true === result ) {

			\GFAPI::add_note( $entry['id'], $entry['created_by'], 'WP Fusion', wp_fusion()->crm->name . ' invoice successfully created.' );

		} elseif ( ! empty( $result ) ) {

			// CRMs with invoices.
			\GFAPI::add_note( $entry['id'], $entry['created_by'], 'WP Fusion', wp_fusion()->crm->name . ' invoice #' . $result . ' successfully created.' );
			gform_update_meta( $entry['id'], '_wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		gform_update_meta( $entry['id'], '_wpf_ec_complete', current_time( 'Y-m-d H:i:s' ) );

		do_action( 'wpf_ecommerce_complete', $entry['id'], $result, $contact_id, $order_args );

	}

	/**
	 * Register ecommerce settings fields.
	 *
	 * @since 1.19.0
	 *
	 * @param array $fields       The feed settings fields.
	 * @param bool  $has_payments Whether or not the form has payments enabled.
	 */
	public function settings_fields( $fields, $has_payments = false ) {

		if ( ! $has_payments && ! in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {
			return $fields;
		}

		$new_fields = array(
			array(
				'title'  => esc_html__( 'Enhanced Ecommerce', 'wp-fusion' ),
				'fields' => array(
					'enhanced_ecommerce' => array(
						'type'    => 'checkbox',
						'name'    => 'enhanced_ecommerce',
						'label'   => __( 'Enhanced Ecommerce', 'wp-fusion' ),
						'choices' => array(
							array(
								'label' => sprintf( __( 'Sync the payment data from this order to %s, via WP Fusion\'s Enhanced Ecommerce integration', 'wp-fusion' ), wp_fusion()->crm->name ),
								'name'  => 'enhanced_ecommerce',
							),
						),
					),
					'deal_title' => array(
						'type'          => 'text',
						'name'          => 'deal_title',
						'label'         => esc_html__( 'Deal Title', 'wp-fusion' ),
						'default_value' => 'Gravity Forms Entry #{entry_id}',
						'class'         => 'merge-tag-support mt-position-right mt-hide_all_fields',
					),
				),
			),
		);

		if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

			$choices = array();

			foreach ( wpf_get_option( wp_fusion()->crm->slug . '_pipelines', array() ) as $value => $label ) {

				$choices[] = array(
					'label' => $label,
					'value' => $value,
				);
			}

			$new_fields[0]['fields']['deal_stage'] = array(
				'type'          => 'select',
				'name'          => 'deal_stage',
				'label'         => esc_html__( 'Deal Stage', 'wp-fusion' ),
				'class'         => 'medium',
				'choices'       => $choices,
				'default_value' => wpf_get_option( wp_fusion()->crm->slug . '_pipeline_stage' ),
				'tooltip'       => __( 'Select a pipeline and stage for new deals.', 'wp-fusion' ),
			);

			$form        = GFAPI::get_form( intval( $_GET['id'] ) );
			$form_fields = GFAPI::get_fields_by_type( $form, array( 'product' ) );

			$choices = array(
				array( 'label' => esc_html__( 'No value', 'wpfusion' ), 'value' => 0 ),
			);

			foreach ( $form_fields as $field ) {
				$field_id    = $field->id;
				$field_label = RGFormsModel::get_label( $field );
				$choices[]   = array( 'value' => $field_id, 'label' => $field_label );
			}

			$choices[] = array( 'label' => esc_html__( 'Form Total', 'wpfusion' ), 'value' => 'form_total' );

			$new_fields[0]['fields']['deal_value'] = array(
				'type'          => 'select',
				'name'          => 'deal_value',
				'label'         => esc_html__( 'Deal Value', 'wp-fusion' ),
				'choices'       => $choices,
				'default_value' => 'form_total',
			);

		}

		$fields = wp_fusion()->settings->insert_setting_after( 'additional_options', $fields, $new_fields );

		return $fields;

	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/


	/**
	 * Adds Gravity Forms checkbox to available export options.
	 *
	 * @since 1.19.0
	 *
	 * @param array $options The export options.
	 * @return array The export options.
	 */
	public function export_options( $options ) {

		$options['gravityforms_ecom'] = array(
			'label'         => 'Gravity Forms Entries (Ecommerce addon)',
			'title'         => 'Entries',
			'process_again' => true,
			'tooltip'       => 'Finds Gravity Forms entries that have been processed by WP Fusion but have not been processed by the Ecommerce Addon, and syncs ecommerce data to ' . wp_fusion()->crm->name . '.',
		);

		return $options;

	}

	/**
	 * Query the initial entries to export.
	 *
	 * @since 1.19.0
	 *
	 * @param array $args The export args.
	 * @return array The entry IDs to export.
	 */
	public function batch_init( $args ) {

		$entry_ids = array();

		$feeds = GFAPI::get_feeds( null, null, null );

		if ( empty( $feeds ) ) {
			return $entry_ids;
		}

		$form_ids = array();

		foreach ( $feeds as $feed ) {
			$form_ids[] = $feed['form_id'];
		}

		if ( ! empty( $args['skip_processed'] ) ) {
			$search_criteria = array(
				'field_filters' => array(
					array(
						'key'      => '_wpf_ec_complete',
						'operator' => '=',
					),
				),
			);
		} else {
			$search_criteria = array();
		}

		$entry_ids = GFAPI::get_entry_ids( $form_ids, $search_criteria );

		return $entry_ids;

	}

	/**
	 * Process the entries one by one.
	 *
	 * @since 1.19.0
	 *
	 * @param int $entry_id The entry ID.
	 */
	public function batch_step( $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );
		$this->send_order_data( $entry );

	}

}

new WPF_EC_Gravity_Forms();
