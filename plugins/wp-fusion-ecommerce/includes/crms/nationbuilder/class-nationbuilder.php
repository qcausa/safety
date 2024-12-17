<?php

class WPF_EC_NationBuilder {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'refunds' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 10, 2 );

	}


	/**
	 * Add fields to settings page
	 *
	 * @since  1.17.6
	 *
	 * @param  array $settings The settings.
	 * @param  array $options  The options.
	 * @return array  Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ecommerce_header'] = array(
			'title'   => __( 'NationBuilder Enhanced Ecommerce', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['tracking_code_slug'] = array(
			'title'   => __( 'Tracking Code Slug', 'wp-fusion' ),
			'type'    => 'text',
			'desc'    => __( 'The tracking code will be displayed in the donations table in NationBuilder. It allows you to differentiate between donation sources.', 'wp-fusion' ),
			'section' => 'ecommerce',
		);

		return $settings;

	}

	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  bool
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		// Convert date
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		$order_date = new DateTime( date( 'c', $order_date ) );

		// DateTimeZone throws an error with 0 as the timezone
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		$order_args['currency_symbol'] = html_entity_decode( $order_args['currency_symbol'] );

		$data = array(
			'donor_id'              => $contact_id,
			'amount'                => $order_args['currency_symbol'] . $order_args['total'],
			'amount_in_cents'       => $order_args['total'] * 100,
			'payment_type_name'     => 'Credit Card',
			'payment_type_ngp_code' => 'D',
			'succeeded_at'          => $order_date->format( 'c' ),
			'note'                  => $order_args['order_label'],
		);

		if ( wpf_get_option( 'tracking_code_slug' ) ) {
			$data['tracking_code_slug'] = wpf_get_option( 'tracking_code_slug' );
		}

		/**
		 * Filters the deal data.
		 *
		 * @since 1.21.1
		 *
		 * @param array $data     The deal data.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_nationbuilder_add_donation', $data, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( array( 'donation' => $data ) );

		$request  = 'https://' . wp_fusion()->crm->url_slug . '.nationbuilder.com/api/v1/donations?access_token=' . wp_fusion()->crm->token;
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {

			wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding donation: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return $response;

		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->donation->id;

	}


	/**
	 * Refund an order.
	 *
	 * @since 1.9.2
	 *
	 * @param int    $transaction_id The transaction ID.
	 * @param int    $final_amount   The final order amount.
	 * @param int    $refund_amount  The refund amount.
	 * @param array  $order_args   The original order args.
	 * @param string $contact_id  The contact ID in the CRM.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function refund_order( $transaction_id, $final_amount, $refund_amount, $order_args, $contact_id ) {

		$data = array(
			'amount'          => $order_args['currency_symbol'] . $final_amount,
			'amount_in_cents' => $final_amount * 100,
			'note'            => __( 'Refunded ' . $order_args['currency_symbol'] . $refund_amount ),
		);

		$params           = wp_fusion()->crm->get_params();
		$params['body']   = wp_json_encode( array( 'donation' => $data ) );
		$params['method'] = 'PUT';

		$request  = 'https://' . wp_fusion()->crm->url_slug . '.nationbuilder.com/api/v1/donations/' . $transaction_id . '?access_token=' . wp_fusion()->crm->token;
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}


}
