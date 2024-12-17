<?php

class WPF_EC_Zoho {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'deal_stages', 'refunds' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		add_action( 'wpf_sync', array( $this, 'sync' ) );

		// Sync data on first run
		$pipelines = wpf_get_option( 'zoho_pipelines' );

		if ( $pipelines != null && ! is_array( $pipelines ) ) {
			$this->sync();
		}

		// Enable products if available in the app

		$products = get_option( 'wpf_zoho_products' );

		if ( 'disabled' !== $products ) {
			$this->supports[] = 'products';
		}
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
		$params           = wp_fusion()->crm->get_params();
		$url              = wp_fusion()->crm->api_domain . '/crm/v2/deals/' . $transaction_id . '';
		$params['method'] = 'PUT';
		$params['body']   = wp_json_encode(
			array(
				'data' => array(
					array(
						'id'               => $transaction_id,
						'Amount'           => floatval( $final_amount ),
						'Expected_Revenue' => floatval( $final_amount ),
					),
				),
			)
		);
		$response         = wp_remote_post( $url, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		if ( ! isset( $options['deals_enabled'] ) ) {
			$options['deals_enabled'] = false;
		}

		$settings['ecommerce_header'] = array(
			'title'   => __( 'Zoho Ecommerce Tracking', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		if ( ! isset( $options['zoho_pipelines'] ) ) {
			$options['zoho_pipelines'] = array();
		}

		$settings['zoho_pipeline_stage'] = array(
			'title'       => __( 'Deal Stage', 'wp-fusion' ),
			'type'        => 'select',
			'section'     => 'ecommerce',
			'placeholder' => __( 'Select a Stage', 'wp-fusion' ),
			'choices'     => $options['zoho_pipelines'],
			'std'         => '3136853000000006815',
			'desc'        => __( 'Select a default stage for new deals.', 'wp-fusion' ),
		);

		if ( ! isset( $options['zoho_accounts'] ) ) {
			$options['zoho_accounts'] = array();
		}

		// Get the first account for the default.

		$first_key = $options['zoho_accounts'];
		reset( $first_key );
		$first_key = key( $first_key );

		$settings['zoho_account'] = array(
			'title'       => __( 'Default Deal Account', 'wp-fusion' ),
			'type'        => 'select',
			'section'     => 'ecommerce',
			'placeholder' => __( 'Select an Account', 'wp-fusion' ),
			'choices'     => $options['zoho_accounts'],
			'std'         => $first_key,
			'desc'        => __( 'Select a default account for new deals. If the contact already is associated with an account then that account will be used.', 'wp-fusion' ),
		);

		if ( in_array( 'products', $this->supports ) ) {

			$settings['sync_products'] = array(
				'title'   => __( 'Sync Products', 'wp-fusion' ),
				'desc'    => __( 'Sync products purchased as line items to deals in Zoho.', 'wp-fusion' ),
				'std'     => 1,
				'type'    => 'checkbox',
				'section' => 'ecommerce',
				'tooltip' => __( 'Note that every line item requires a separate API call, so this may not be reliable on stores where orders contain a large number of items (10+).', 'wp-fusion' ),
			);

		}

		return $settings;

	}


	/**
	 * Syncs pipelines on plugin install or when Resynchronize is clicked
	 *
	 * @since 1.0
	 * @return void
	 */

	public function sync() {

		$params = wp_fusion()->crm->get_params();

		$request  = wp_fusion()->crm->api_domain . '/crm/v2/settings/fields?module=deals';
		$response = wp_remote_get( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$pipelines = array();

		foreach ( $response->fields as $field ) {

			if ( $field->api_name == 'Stage' ) {

				foreach ( $field->pick_list_values as $stage ) {

					$pipelines[ $stage->id ] = $stage->display_value;

				}
			}
		}

		wp_fusion()->settings->set( 'zoho_pipelines', $pipelines );

		// A deal can't be created without an account so we'll get those here

		$request  = wp_fusion()->crm->api_domain . '/crm/v2/accounts';
		$response = wp_remote_get( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$accounts = array();

		if ( ! empty( $response->data ) ) {

			foreach ( $response->data as $account ) {

				$accounts[ $account->id ] = $account->Account_Name;

			}
		}

		wp_fusion()->settings->set( 'zoho_accounts', $accounts );

		// Get products.

		$request  = wp_fusion()->crm->api_domain . '/crm/v2/products';
		$response = wp_remote_get( $request, $params );

		if ( is_wp_error( $response ) ) {

			if ( false !== strpos( $response->get_error_message(), 'INVALID_MODULE' ) ) {

				// Products aren't supported.
				update_option( 'wpf_zoho_products', 'disabled', false );
			}

			return $response;

		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		// Products enabled.

		$products = array();

		if ( ! empty( $response->data ) ) {

			foreach ( $response->data as $product ) {
				$products[ $product->id ] = $product->{'Product_Name'};
			}
		}

		update_option( 'wpf_zoho_products', $products, false );

	}

	/**
	 * Gets a product ID based on product details.
	 *
	 * @since 1.23.2
	 *
	 * @param array $product The product details.
	 * @return int|bool|WP_Error The product ID, false, or error.
	 */
	public function get_product_id( $product ) {

		$url = wp_fusion()->crm->api_domain . '/crm/v2/products/search?criteria=(Product_Name:equals:' . urlencode( $product['name'] ) . ')';

		$response = wp_remote_get( $url, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $response->data ) ) {

			return $response->data[0]->id;

		} else {

			return false;

		}

	}

	/**
	 * Register a product in Zoho
	 *
	 * @access  public
	 * @return  int Product ID
	 */

	public function add_product( $product ) {

		// Add new product

		$data = array(
			'Product_Name' => $product['name'],
			'Unit_Price'   => $product['price'],
		);

		/**
		 * Filters the product data.
		 *
		 * @since 1.17.8
		 *
		 * @param array $data    The product data to send to the CRM.
		 * @param array $product The product data from WordPress.
		 */

		$data = apply_filters( 'wpf_ecommerce_zoho_add_product', $data, $product );

		// Logger
		wpf_log(
			'info',
			0,
			'Registering new product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in Zoho:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( array( 'data' => array( $data ) ) );

		$response = wp_remote_post( wp_fusion()->crm->api_domain . '/crm/v2/products', $params );

		if ( is_wp_error( $response ) ) {

			return $response;

		} else {

			$body       = json_decode( wp_remote_retrieve_body( $response ) );
			$product_id = $body->data[0]->details->id;

		}

		if ( ! empty( $product['id'] ) ) {

			// Save the ID to the product
			update_post_meta( $product['id'], 'zoho_product_id', $product_id );

		}

		// Update the global products list
		$zoho_products                = get_option( 'wpf_zoho_products', array() );
		$zoho_products[ $product_id ] = $product['name'];
		update_option( 'wpf_zoho_products', $zoho_products );

		return $product_id;

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

		$order_args['currency_symbol'] = html_entity_decode( $order_args['currency_symbol'] );

		$calc_totals = 0;

		// Build up items array
		foreach ( $order_args['products'] as $product ) {

			if ( ! isset( $product['price'] ) || ! is_numeric( $product['price'] ) ) {
				$product['price'] = 0;
			}

			$calc_totals += $product['qty'] * $product['price'];

		}

		// Adjust total for line items
		foreach ( $order_args['line_items'] as $line_item ) {

			if ( ! isset( $line_item['price'] ) || ! is_numeric( $line_item['price'] ) ) {
				$line_item['price'] = 0;
			}

			$calc_totals += $line_item['price'];

		}

		// Create description
		$description = '';

		foreach ( $order_args['products'] as $product ) {

			$description .= $product['name'] . ' - ' . $order_args['currency_symbol'] . $product['price'];

			if ( $product['qty'] > 1 ) {
				$description .= ' - x' . $product['qty'];
			}

			$description .= PHP_EOL;

		}

		foreach ( $order_args['line_items'] as $line_item ) {

			$description .= $line_item['title'] . ' - ' . $order_args['currency_symbol'] . $line_item['price'] . PHP_EOL;

		}

		// Get the contact from Zoho so we can check their Account

		$params   = wp_fusion()->crm->get_params();
		$url      = wp_fusion()->crm->api_domain . '/crm/v2/' . wp_fusion()->crm->object_type . '/' . $contact_id;
		$response = wp_remote_get( $url, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body_json = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body_json->data ) && ! empty( $body_json->data[0]->{'Account_Name'} ) ) {

			$account = $body_json->data[0]->{'Account_Name'}->id;

		} else {

			$account = wpf_get_option( 'zoho_account' );

		}

		if ( ! empty( $order_args['deal_stage'] ) ) {
			$stage = $order_args['deal_stage'];
		} else {
			$stage = wpf_get_option( 'zoho_pipeline_stage' );
		}

		if ( isset( $order_args['status'] ) && ! empty( wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" ) ) ) {
			$stage = wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" );
		}

		$data = array(
			'Deal_Name'        => $order_args['order_label'],
			'Account_Name'     => $account,
			'Contact_Name'     => $contact_id,
			'Closing_Date'     => date( 'Y-m-d', $order_date ),
			'Stage'            => strval( $stage ),
			'Amount'           => round( $calc_totals, 2 ),
			'Expected_Revenue' => round( $calc_totals, 2 ),
			'Probability'      => 100,
			'Description'      => $description,
			'Currency'         => $order_args['currency'],
			'$currency_symbol' => $order_args['currency_symbol'],
		);

		/**
		 * Filters the deal data.
		 *
		 * @since 1.17.8
		 *
		 * @param array $data     The deal data.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_zoho_add_deal', $data, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params['body'] = wp_json_encode( array( 'data' => array( $data ) ) );

		$response = wp_remote_post( wp_fusion()->crm->api_domain . '/crm/v2/deals', $params );

		if ( is_wp_error( $response ) ) {

			wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding order: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return $response;

		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$deal_id = $response->data[0]->details->id;

		// Now maybe add the products.

		if ( in_array( 'products', $this->supports ) && wpf_get_option( 'sync_products', true ) ) {

			foreach ( $order_args['products'] as $product ) {

				if ( empty( $product['crm_product_id'] ) ) {

					$product['crm_product_id'] = $this->add_product( $product );

					// Error handling for adding products
					if ( is_wp_error( $product['crm_product_id'] ) ) {

						wpf_log( $product['crm_product_id']->get_error_code(), $order_args['user_id'], 'Error adding product to Zoho: ' . $product['crm_product_id']->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
						continue;

					}
				}

				$params['method'] = 'PUT';

				// Link the product to the deal.
				$response = wp_remote_post( wp_fusion()->crm->api_domain . '/crm/v2/deals/' . $deal_id . '/Products/' . $product['crm_product_id'], $params );

				if ( is_wp_error( $response ) ) {
					wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error associating product ID <strong>' . $product['crm_product_id'] . '</strong> with deal ID <strong>' . $deal_id . ': ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					continue;
				}
			}
		}

		return $deal_id;

	}

	/**
	 * Update a deal stage when an order status is changed
	 *
	 * @access  public
	 * @return  bool
	 */

	public function change_stage( $deal_id, $stage, $order_id ) {

		$data = array(
			'id'    => $deal_id,
			'Stage' => strval( $stage ),
		);

		$params = wp_fusion()->crm->get_params();

		$params['body']   = wp_json_encode( array( 'data' => array( $data ) ) );
		$params['method'] = 'PUT';

		$response = wp_remote_request( wp_fusion()->crm->api_domain . '/crm/v2/deals', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
