<?php

class WPF_EC_Salesforce {

	/**
	 * Lets other integrations know which features are supported by the CRM.
	 *
	 * @since 1.23.0
	 * @var array $supports The supported features.
	 */
	public $supports = array( 'products' );

	/**
	 * Get things started.
	 *
	 * @since 1.23.0
	 */
	public function init() {

		if ( ! wpf_get_option( 'salesforce_pricebook' ) ) {
			$this->get_pricebook_id();
		}

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ) );

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );
		add_action( 'wpf_sync', array( $this, 'get_pricebook_id' ) );

	}


	/**
	 * Add fields to settings page.
	 *
	 * @since 1.23.0
	 *
	 * @param array $settings The settings array.
	 * @return array Settings The settings array.
	 */
	public function register_settings( $settings ) {

		$settings['ec_salesforce_header'] = array(
			'title'   => __( 'Salesforce Enhanced Ecommerce', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
			'desc'    => sprintf( __( 'For more information on WP Fusion\'s ecommerce integration with Salesforce, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/ecommerce-tracking/salesforce-ecommerce/" target="_blank">', '</a>' ),
		);

		$settings['salesforce_ecom_account'] = array(
			'title'   => __( 'Account ID', 'wp-fusion' ),
			'type'    => 'text',
			'section' => 'ecommerce',
			'desc'    => __( 'The Salesforce Account ID to associate with new orders (Required).', 'wp-fusion' ),
		);

		return $settings;

	}

	/**
	 * Sync account ID if it is not set by the user.
	 *
	 * @since 1.23.0
	 *
	 * @access private
	 * @return string|false The account ID or false on failure.
	 */
	private function get_account_id() {

		if ( wpf_get_option( 'salesforce_ecom_account' ) ) {
			return wpf_get_option( 'salesforce_ecom_account' );
		}

		if ( wpf_get_option( 'salesforce_account' ) ) {
			return wpf_get_option( 'salesforce_account' ); // fall back to the Contacts one.
		}

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=SELECT+Id+FROM+Account';
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body->records ) ) {

			$id = $body->records[0]->Id;

			wpf_log( 'notice', get_current_user_id(), 'To create an order with Salesforce you must enter an account ID on the Enhanced Ecommerce tab in the WP Fusion settings. Since no account is selected, the first account will be used as a fallback.' );

			wp_fusion()->settings->set( 'salesforce_ecom_account', $id );

			return $id;
		}

	}

	/**
	 * Sync pricebook ID.
	 *
	 * @return string The pricebook ID
	 */
	public function get_pricebook_id() {

		if ( ! wpf_get_option( 'salesforce_pricebook' ) && empty( get_option( 'wpf_salesforce_products' ) ) ) {
			$this->sync_products(); // initial setup, sync products.
		}

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=SELECT+Id+FROM+Pricebook2+WHERE+isStandard=true';
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body->records ) ) {
			$id = $body->records[0]->Id;
			wp_fusion()->settings->set( 'salesforce_pricebook', $id );
			return $id;
		}
	}


	/**
	 * Syncs an order to the CRM.
	 *
	 * @since 1.23.0
	 *
	 * @param int   $order_id   The order ID.
	 * @param int   $contact_id The contact ID.
	 * @param array $order_args The order arguments.
	 * @return WP_Error|int The order ID or WP_Error if the order could not be synced.
	 */
	public function add_order( $order_id, $contact_id, $order_args ) {

		foreach ( $order_args['products'] as $product ) {

			if ( empty( $product['crm_product_id'] ) ) {

				$product_id = $this->get_product_id( $product );

				if ( ! is_wp_error( $product_id ) && ! empty( $product_id ) ) {

					// Existing product found.

					$product['crm_product_id'] = $product_id;

				} elseif ( false === $product_id ) {

					// Add the product to Salesforce.

					$product_id = $this->add_product( $product );

					if ( ! is_wp_error( $product_id ) ) {

						$product['crm_product_id'] = $product_id;

					} else {

						wpf_log(
							'error',
							$order_args['user_id'],
							'Error registering new product in Salesforce: ' . $product_id->get_error_message(),
							array(
								'source'              => 'wpf-ecommerce',
								'meta_array_nofilter' => $product_id,
							)
						);
						continue;

					}
				} elseif ( is_wp_error( $product_id ) ) {

					wpf_log(
						'error',
						$order_args['user_id'],
						'Error looking up product in Salesforce: ' . $product_id->get_error_message(),
						array(
							'source'              => 'wpf-ecommerce',
							'meta_array_nofilter' => $product_id,
						)
					);
					continue;

				}
			}

			$product['crm_product_id'] = explode( ':', $product['crm_product_id'] );
			$pricebook_id              = $product['crm_product_id'][0]; // pricebook ID.
			$product_id                = $product['crm_product_id'][1]; // product ID.

			$items[] = array(
				'OrderId'          => '@{orderRef.id}',
				'PricebookEntryId' => $pricebook_id,
				'Quantity'         => $product['qty'],
				'UnitPrice'        => $product['price'],
			);

		}

		/*
		 * When a client application creates an order, the Status Code must be Draft and
		 * the Status must be any value that corresponds to a Status Code of Draft. The
		 * application can then activate an order by updating it and setting the value in
		 * its Status field to an Activated state; however, the Status field is the only
		 * field you can update when activating the order.
		 *
		 * @link https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_order.htm
		 */

		$order_data = array(
			'AccountId'              => $this->get_account_id(),
			'Name'                   => $order_args['order_label'],
			'EffectiveDate'          => gmdate( 'Y-m-d', $order_args['order_date'] ),
			'Status'                 => 'Draft', // see above.
			'Pricebook2Id'           => wpf_get_option( 'salesforce_pricebook' ),
			'ShipToContactId'        => $contact_id,
			'BillToContactId'        => $contact_id,
			'CustomerAuthorizedById' => $contact_id,
		);

		$requests = array();
		// Request 1: Create an order record.
		$order_request = array(
			'method'      => 'POST',
			'url'         => '/services/data/v57.0/sobjects/Order',
			'referenceId' => 'orderRef',
			'body'        => $order_data,
		);

		$requests[] = $order_request;

		// Request 2: Create the OrderItem records and associate them with the order.
		foreach ( $items as $index => $order_item ) {
			$order_item_request = array(
				'method'      => 'POST',
				'url'         => '/services/data/v57.0/sobjects/OrderItem',
				'referenceId' => 'orderItemRef' . $index,
				'body'        => $order_item,
			);
			$requests[]         = $order_item_request;
		}

		$request_payload = array(
			'compositeRequest' => $requests,
		);

		/**
		 * Filters the order data.
		 *
		 * @since 1.23.0
		 *
		 * @link https://wpfusion.com/documentation/ecommerce-tracking/salesforce-ecommerce/#modifying-the-api-data
		 *
		 * @param array $request_payload The payload.
		 * @param int   $order_id        ID of the order.
		 */

		$request_payload = apply_filters( 'wpf_ecommerce_salesforce_add_deal', $request_payload, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $request_payload,
				'source'              => 'wpf-ecommerce',
			)
		);

		// Make the composite request
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $request_payload );

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/composite/';
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->{'compositeResponse'}[0]->body->id;

	}


	/**
	 * Sync available products
	 *
	 * @since 1.23.0
	 * @return array The products in the CRM.
	 */
	public function sync_products() {

		$products = array();
		$query    = 'SELECT Name, Id FROM Product2';
		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=' . rawurlencode( $query );
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			wpf_log( $response->get_error_code(), 0, 'Error syncing products: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return array();
		}

		$products_response = json_decode( $response['body'] );

		if ( empty( $products_response->records ) ) {
			// No products found, return empty array.
			return $products;
		}

		// Retrieve the Pricebook Entry IDs for the specified pricebook.
		$pricebook_id        = wpf_get_option( 'salesforce_pricebook' );
		$pricebook_entry_ids = array();

		$pricebook_query    = "SELECT Product2Id, Id FROM PricebookEntry WHERE Pricebook2Id = '$pricebook_id'";
		$pricebook_request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=' . rawurlencode( $pricebook_query );
		$pricebook_response = wp_safe_remote_get( $pricebook_request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $pricebook_response ) ) {
			wpf_log( $pricebook_response->get_error_code(), 0, 'Error syncing pricebook entries: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return;
		}

		$pricebook_response_data = json_decode( $pricebook_response['body'] );

		foreach ( $pricebook_response_data->records as $pricebook_entry ) {
			$pricebook_entry_ids[ $pricebook_entry->{'Product2Id'} ] = $pricebook_entry->{'Id'};
		}

		// Match up products and pricebook entry IDs.
		foreach ( $products_response->records as $product ) {

			$product_id = $product->{'Id'};

			if ( isset( $pricebook_entry_ids[ $product_id ] ) ) {

				// Combine the pricebook ID and product ID.
				$combined_id              = $pricebook_entry_ids[ $product_id ] . ':' . $product_id;
				$products[ $combined_id ] = $product->{'Name'};

			}
		}

		asort( $products );

		update_option( 'wpf_salesforce_products', $products, false );

		return $products;

	}

	/**
	 * Searches for a product by name and code.
	 *
	 * @since 1.23.0
	 *
	 * @param array $product The product.
	 * @return string|bool|WP_Error The product ID or a WP_Error object, or false if not found.
	 */
	public function get_product_id( $product ) {

		$product_name = $product['name'];
		$query        = "SELECT Name, Id FROM Product2 WHERE Name = '$product_name'";

		if ( ! empty( $product['sku'] ) ) {
			$product_code = $product['sku'];
			$query       .= " OR ProductCode = '$product_code'";
		}

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=' . rawurlencode( $query );
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $response->records ) ) {
			return false;
		}

		$product_id = $response->records[0]->Id;

		// We also need the pricebook ID. For now this just supports the main pricebook.

		$query = "SELECT Pricebook2Id FROM PricebookEntry WHERE Product2Id = '$product_id' LIMIT 1";

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/query/?q=' . rawurlencode( $query );
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $response->records ) ) {
			wpf_log( 'error', 0, 'Unknown error. A Product ID was found but no corresponding Pricebook ID.', array( 'source' => 'wpf-ecommerce' ) );
			return false;
		}

		// Create ID (combo of pricebook entry and product).
		$pricebook_id = $response->records[0]->Id;
		$product_id   = $pricebook_id . ':' . $product_id;

		return $product_id;

	}

	/**
	 * Add a new product into the CRM.
	 *
	 * @since 1.23.0
	 *
	 * @param array $product The product.
	 * @return string|WP_Error The product ID or a WP_Error object.
	 */
	public function add_product( $product ) {

		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		$product_data = array(
			'Name'        => $product['name'],
			'ProductCode' => ( isset( $product['sku'] ) ? $product['sku'] : '' ),
			'Description' => ( isset( $product['description'] ) ? $product['description'] : '' ),
			'IsActive'    => true,
		);

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in Salesforce:',
			array(
				'meta_array_nofilter' => $product_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$requests = array();

		// Request 1: Create a Product2 record.
		$product_request = array(
			'method'      => 'POST',
			'url'         => '/services/data/v57.0/sobjects/Product2',
			'referenceId' => 'productRef',
			'body'        => $product_data,
		);

		// Request 2: Create a PricebookEntry record.
		$pricebook_entry_request = array(
			'method'      => 'POST',
			'url'         => '/services/data/v57.0/sobjects/PricebookEntry',
			'referenceId' => 'pricebookEntryRef',
			'body'        => array(
				'Product2Id'   => '@{productRef.id}',
				'Pricebook2Id' => wpf_get_option( 'salesforce_pricebook' ),
				'UnitPrice'    => $product['price'],
				'IsActive'     => true,
			),
		);

		// Update.
		if ( ! empty( $product['crm_product_id'] ) ) {

			$product['crm_product_id'] = explode( ':', $product['crm_product_id'] );
			$pricebook_id              = $product['crm_product_id'][0]; // pricebook ID.
			$product_id                = $product['crm_product_id'][1]; // product ID.

			// Product.
			$product_request['method'] = 'PATCH';
			$product_request['url']   .= '/' . $product_id;

			// PriceBookEntry.
			$pricebook_entry_request['url']               .= '/' . $pricebook_id;
			$pricebook_entry_request['body']['Product2Id'] = $product_id;

		}

		$requests[] = $product_request;
		$requests[] = $pricebook_entry_request;

		$request_payload = array(
			'compositeRequest' => $requests,
		);

		/**
		 * Filters the product data.
		 *
		 * @since 1.23.0
		 *
		 * @param array $request_payload The API request payload.
		 * @param array $product         The product data.
		 */
		$request_payload = apply_filters( 'wpf_ecommerce_salesforce_add_product', $request_payload, $product );

		// Make the composite request.
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $request_payload );

		$request  = wpf_get_option( 'sf_instance_url' ) . '/services/data/v57.0/composite';
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );
		$errors   = array();

		foreach ( $response->{'compositeResponse'} as $res ) {
			if ( 200 !== $res->{'httpStatusCode'} && 201 !== $res->{'httpStatusCode'} ) {
				$errors[] = wp_list_pluck( $res->body, 'message' );
			}
		}

		if ( ! empty( $errors ) ) {
			$errors = array_merge( ...$errors );
			return new WP_Error( 'error', implode( ', ', $errors ) );
		}

		// Create ID (combo of pricebook entry and product).
		$product_id         = $response->{'compositeResponse'}[0]->body->id;
		$pricebook_entry_id = $response->{'compositeResponse'}[1]->body->id;

		$product_id = $pricebook_entry_id . ':' . $product_id;

		if ( empty( $product['crm_product_id'] ) ) {

			// Save it to the product.

			update_post_meta( $product['id'], 'salesforce_product_id', $product_id );
			$products                = get_option( 'wpf_salesforce_products', array() );
			$products[ $product_id ] = $product['name'];
			update_option( 'wpf_salesforce_products', $products, false );

		}

		return $product_id;
	}


}
