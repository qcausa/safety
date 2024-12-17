<?php

class WPF_EC_Sendinblue {

	/**
	 * Lets other integrations know which features are supported by the CRM.
	 */
	public $supports = array( 'deal_stages' );

	/**
	 * Get things started.
	 *
	 * @since 1.19.0
	 */
	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		add_action( 'wpf_sync', array( $this, 'sync' ) );

		// Sync data on first run.
		$pipelines = wpf_get_option( 'sendinblue_pipelines' );

		if ( ! is_array( $pipelines ) ) {
			$this->sync();
		}

	}


	/**
	 * Add fields to settings page.
	 * 
	 * @since 1.19.0
	 *
	 * @return array Settings
	 */
	public function register_settings( $settings, $options ) {

		$settings['sendinblue_pipeline_stage'] = array(
			'title'       => __( 'Deal Stage', 'wp-fusion' ),
			'type'        => 'select',
			'section'     => 'ecommerce',
			'placeholder' => __( 'Select a Stage', 'wp-fusion' ),
			'choices'     => isset( $options['sendinblue_pipelines'] ) ? $options['sendinblue_pipelines'] : array(),
			'desc'        => __( 'Select a default stage for new deals.', 'wp-fusion' ),
		);

		return $settings;

	}


	/**
	 * Syncs pipelines on plugin install or when Resynchronize is clicked.
	 *
	 * @since 1.19.0
	 */
	public function sync() {

		$params = wp_fusion()->crm->get_params();

		$request  = 'https://api.brevo.com/v3/crm/pipeline/details';
		$response = wp_remote_get( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$pipelines = array();

		foreach ( $response->stages as $field ) {
			$pipelines[ $field->id ] = $field->name;
		}

		wp_fusion()->settings->set( 'sendinblue_pipelines', $pipelines );

	}

	/**
	 * Add an order.
	 *
	 * @since 1.19.0
	 *
	 * @param int    $order_id   The order ID in WordPress.
	 * @param string $contact_id The contact ID in the CRM.
	 * @param array  $order_args The order details.
	 * @return int|WP_Error The deal ID or error on failure.
	 */
	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( wpf_get_option( 'sendinblue_pipeline_stage' ) ) ) {
			return new WP_Error( 'error', 'To sync orders with Brevo you must first select a deal stage from the Enhanced Ecommerce tab in the WP Fusion settings.' );
		}

		$description = '';

		foreach ( $order_args['products'] as $product ) {

			$description .= $product['name'] . ' - ' . html_entity_decode( $order_args['currency_symbol'] ) . $product['price'];

			if ( $product['qty'] > 1 ) {
				$description .= ' - x' . $product['qty'];
			}

			$description .= PHP_EOL;

		}

		foreach ( $order_args['line_items'] as $line_item ) {

			$description .= $line_item['title'] . ' - ' . html_entity_decode( $order_args['currency_symbol'] ) . $line_item['price'] . PHP_EOL;

		}

		if ( ! empty( $order_args['deal_stage'] ) ) {
			$stage = $order_args['deal_stage'];
		} else {
			$stage = wpf_get_option( 'sendinblue_pipeline_stage' );
		}

		if ( isset( $order_args['status'] ) && ! empty( wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" ) ) ) {
			$stage = wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" );
		}

		$data = array(
			'name'              => $order_args['order_label'],
			'linkedContactsIds' => array( $contact_id ),
			'attributes'        => array(
				'deal_description' => $description,
				'amount'           => floatval( $order_args['total'] ),
				'deal_stage'       => $stage,
				'close_date'       => gmdate( 'Y-m-d', $order_args['order_date'] ),
			),
		);

		/**
		 * Filters the deal data.
		 *
		 * @since 1.19.0
		 *
		 * @param array $data     The deal data.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_sendinblue_add_deal', $data, $order_id );

		/**
		 * Filters the deal data.
		 *
		 * @since 1.20.4 Added when Sendinblue rebranded to Brevo.
		 *
		 * @param array $data     The deal data.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_brevo_add_deal', $data, $order_id );

		$params = wp_fusion()->crm->get_params();

		// See if we're updating an existing order or creating a new one.

		if ( empty( $order_args['invoice_id'] ) ) {
			$action           = 'Adding';
			$params['method'] = 'POST';
			$request          = 'https://api.brevo.com/v3/crm/deals';
		} else {
			$action           = 'Updating';
			$params['method'] = 'PATCH';
			$request          = 'https://api.brevo.com/v3/crm/deals/' . $order_args['invoice_id'];
		}

		wpf_log(
			'info',
			$order_args['user_id'],
			$action . ' <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params['body'] = wp_json_encode( $data );
		$response       = wp_remote_request( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $order_args['invoice_id'] ) ) {

			// If we just updated a deal we get a 204 response.

			return $order_args['invoice_id'];

		} else {

			$response = json_decode( wp_remote_retrieve_body( $response ) );
			return $response->id;
		}

	}

	/**
	 * Update a deal stage when an order status is changed.
	 * 
	 * @since 1.19.0
	 *
	 * @param int    $deal_id  The deal ID.
	 * @param string $stage    The deal stage.
	 * @param int    $order_id The order ID.
	 * @return bool|WP_Error True on success, error on fail.
	 */
	public function change_stage( $deal_id, $stage, $order_id ) {

		$data = array(
			'attributes' => array(
				'deal_stage' => $stage,
			),
		);

		$params = wp_fusion()->crm->get_params();

		$params['body']   = wp_json_encode( $data );
		$params['method'] = 'PATCH';

		$response = wp_remote_request( 'https://api.brevo.com/v3/crm/deals/' . $deal_id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
