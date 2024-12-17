<?php

class WPF_EC_Hubspot {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'deal_stages', 'products', 'refunds' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'hide_attributes_setting' ), 50, 2 );

		add_action( 'wpf_sync', array( $this, 'sync_pipelines' ) );
		add_action( 'wpf_sync', array( $this, 'sync_products' ) );

		add_action( 'admin_init', array( $this, 'maybe_do_initial_sync' ) );

	}


	/**
	 * Refund an order.
	 *
	 * @since 1.19.0
	 *
	 * @param string $transaction_id The order ID.
	 * @param float  $final_amount   The final order amount.
	 * @param float  $refund_amount  The refund amount.
	 * @param array  $order_args     The original order args.
	 * @param string $contact_id     The contact ID.
	 */
	public function refund_order( $transaction_id, $final_amount, $refund_amount, $order_args, $contact_id ) {

		$params = wp_fusion()->crm->get_params();

		$params['body'] = wp_json_encode(
			array(
				'properties' => array(
					'amount' => floatval( $final_amount ),
				),
			)
		);

		$params['method'] = 'PATCH';
		$response         = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/deals/' . $transaction_id . '', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

	/**
	 * Do initial sync when the addon is installed or upgraded
	 *
	 * @access public
	 * @since  1.17
	 * @return void
	 */

	public function maybe_do_initial_sync() {

		if ( true == wpf_get_option( 'connection_configured' ) ) {

			$pipelines = wpf_get_option( 'hubspot_pipelines' );

			if ( false === $pipelines ) {
				$this->sync_pipelines();
			}

			$products = get_option( 'wpf_hubspot_products' );

			if ( false === $products ) {
				$this->sync_products();
			}
		}

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ecommerce_header'] = array(
			'title'   => __( 'HubSpot Ecommerce Tracking', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		if ( ! isset( $options['hubspot_pipelines'] ) ) {
			$options['hubspot_pipelines'] = array();
		}

		$settings['hubspot_pipeline_stage'] = array(
			'title'       => __( 'Pipeline / Stage', 'wp-fusion' ),
			'type'        => 'select',
			'section'     => 'ecommerce',
			'placeholder' => __( 'Select a Pipeline / Stage', 'wp-fusion' ),
			'choices'     => $options['hubspot_pipelines'],
			'std'         => 'default+closedwon',
			'desc'        => __( 'Select a default pipeline and stage for new deals.', 'wp-fusion' ),
		);

		$settings['hubspot_sync_products'] = array(
			'title'   => __( 'Sync Products', 'wp-fusion' ),
			'desc'    => __( 'Sync products purchased as line items to deals in HubSpot.', 'wp-fusion' ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'tooltip' => __( 'Note that every line item requires a separate API call, so this may not be reliable on stores where orders contain a large number of items (10+).', 'wp-fusion' ),
		);

		if ( ! empty( $options['hubspot_add_note'] ) ) {

			// Deprecated.

			$settings['hubspot_add_note'] = array(
				'title'   => __( 'Add Note', 'wp-fusion' ),
				'desc'    => __( 'Add a note to new deals containing the products purchased and prices (Legacy Feature).', 'wp-fusion' ),
				'type'    => 'checkbox',
				'section' => 'ecommerce',
			);
		}

		return $settings;

	}

	/**
	 * Hubspot doesn't support line items distinct from products so we'll hide the setting for now
	 *
	 * @access public
	 * @return array Settings
	 */

	public function hide_attributes_setting( $settings, $options ) {

		if ( isset( $settings['ec_woo_attributes'] ) ) {
			unset( $settings['ec_woo_attributes'] );
			unset( $settings['ec_woo_header'] );
		}

		return $settings;

	}


	/**
	 * Syncs pipelines on plugin install or when Resynchronize is clicked
	 *
	 * @access public
	 * @since  1.0
	 * @return array Pipelines
	 */

	public function sync_pipelines() {

		$pipelines = array();

		$params   = wp_fusion()->crm->get_params();
		$response = wp_remote_get( 'https://api.hubapi.com/crm/v3/pipelines/deals/', $params );

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_wp_error( $response ) ) {

			wpf_log( 'error', 0, 'Error syncing pipelines: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );

			wp_fusion()->settings->set( 'hubspot_pipelines', $pipelines );

			return $response;
		}

		foreach ( $response->results as $pipeline ) {
			foreach ( $pipeline->stages as $stage ) {
				$pipelines[ $pipeline->id . '+' . $stage->id ] = $pipeline->label . ' &raquo; ' . $stage->label;
			}
		}

		wp_fusion()->settings->set( 'hubspot_pipelines', $pipelines );

		return $pipelines;

	}


	/**
	 * Syncs products on plugin install or when Resynchronize is clicked
	 *
	 * @access public
	 * @since  1.17
	 * @return array|WP_Error Products or an error.
	 */

	public function sync_products() {

		$products = array();

		$proceed = true;

		$params  = wp_fusion()->crm->get_params();
		$request = 'https://api.hubapi.com/crm/v3/objects/products?limit=100'; // Must be 100 or you get a 'You can only request at most 100 objects in one request.' error

		while ( $proceed ) {

			$response = wp_remote_get( $request, $params );
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( is_wp_error( $response ) ) {

				wpf_log( 'error', 0, 'Error syncing products: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );

				update_option( 'wpf_hubspot_products', array(), false ); // Update it so it doesn't check again on every page load

				return $response;
			}

			if ( empty( $response->results ) ) {
				$proceed = false;
			}

			foreach ( $response->results as $product ) {
				$products[ $product->id ] = $product->properties->name;
			}

			if ( empty( $response->results ) || count( $response->results ) < 100 ) {
				$proceed = false;
			} else {

				// There are more records
				$request = $response->paging->next->link;
			}
		}

		update_option( 'wpf_hubspot_products', $products, false );

		return $products;

	}

	/**
	 * Search for product ID in CRM.
	 *
	 * @since 1.20.0
	 *
	 * @param array $product The product data.
	 * @return int|bool|WP_Error Product ID or false or error.
	 */
	public function get_product_id( $product ) {

		$search = array(
			'filterGroups' => array(
				array( // This works out as an OR - https://developers.hubspot.com/docs/api/crm/search.
					'filters' => array(
						array(
							'propertyName' => 'name',
							'operator'     => 'EQ',
							'value'        => str_replace( '"', '', $product['name'] ), // HubSpot throws an error with mismatched quotation marks.
						),
					),
				),
			),
		);

		if ( ! empty( $product['sku'] ) ) {

			$search['filterGroups'][] = array(
				'filters' => array(
					array(
						'propertyName' => 'hs_sku',
						'operator'     => 'EQ',
						'value'        => $product['sku'],
					),
				),
			);

		}

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $search );
		$request        = 'https://api.hubapi.com/crm/v3/objects/products/search';
		$response       = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $response->results ) ) {
			return false;
		}

		$product_id = $response->results[0]->id;

		return $product_id;
	}

	/**
	 * Register a product in HubSpot
	 *
	 * @since   1.17
	 *
	 * @param array $product The product data from the ecommerce integration.
	 * @return int|WP_Error Product ID or error.
	 */

	public function add_product( $product ) {
		$products     = get_option( 'wpf_hubspot_products', array() );
		$product_data = array(
			'properties' => array(
				'name'  => $product['name'],
				'price' => $product['price'],
			),
		);

		if ( ! empty( $product['sku'] ) ) {
			$product_data['properties']['hs_sku'] = $product['sku'];
		}

		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in HubSpot:',
			array(
				'meta_array_nofilter' => $product_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		// Add/Update new product
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $product_data );

		if ( $product['crm_product_id'] ) {
			$params['method'] = 'PATCH';
		}

		$response = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/products' . ( $product['crm_product_id'] ? '/' . $product['crm_product_id'] : '' ), $params );

		if ( is_wp_error( $response ) ) {

			if ( false !== strpos( $response->get_error_message(), 'already has that value' ) ) {

				$original_error_message = $response->get_error_message();

				// An existing product already exists.
				$existing_product_id = $this->get_product_id( $product );

				if ( is_wp_error( $existing_product_id ) ) {
					return $existing_product_id;
				}

				if ( ! empty( $existing_product_id ) ) {

					update_post_meta( $product['id'], 'hubspot_product_id', $existing_product_id );

					$products[ $existing_product_id ] = $product['name'];
					update_option( 'wpf_hubspot_products', $products, false );

					return $existing_product_id;
				} else {

					return new WP_Error( 'error', 'A duplicate record error was triggered while trying to register a new product, but WP Fusion was unable to find a duplicate product via search. This product will not be synced. <stromg>Error message:</strong> ' . $original_error_message );

				}
			} else {

				// Generic error
				return $response;

			}
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		// Save the ID to the product
		update_post_meta( $product['id'], 'hubspot_product_id', $response->id );

		// Update the global products list
		$products[ $response->id ] = $product['name'];
		update_option( 'wpf_hubspot_products', $products, false );

		return $response->id;

	}

	/**
	 * Register a line item in HubSpot.
	 *
	 * @since  1.17
	 * @since  1.17.10 Added $order args.
	 *
	 * @param  array $product  The product data from the ecommerce
	 *                         integration.
	 * @param  int   $order_id The order ID.
	 * @return int|WP_Error Line Item ID or error.
	 */

	public function add_line_item( $product, $order_id ) {

		$line_item_data = array(
			'properties' => array(
				'hs_product_id' => $product['crm_product_id'],
				'name'          => $product['name'],
				'price'         => $product['price'],
				'quantity'      => $product['qty'],
			),
		);

		/**
		 * Filters the line item data.
		 *
		 * @since 1.17.9
		 * @since 1.17.10 Added $order_id.
		 *
		 * @param array $line_item_data The array of data used to create the line item.
		 * @param array $product        The product data.
		 * @param int   $order_id       The order ID.
		 */

		$line_item_data = apply_filters( 'wpf_ecommerce_hubspot_add_line_item', $line_item_data, $product, $order_id );

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $line_item_data );
		$response       = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/line_items', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->id;

	}

	/**
	 * Add an order.
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

		if ( ! empty( $order_args['deal_stage'] ) ) {
			$pipeline_stage = $order_args['deal_stage'];
		} else {
			$pipeline_stage = wpf_get_option( 'hubspot_pipeline_stage', 'default+closedwon' );
		}

		// See if there's a custom pipeline stage set for Woo.

		if ( isset( $order_args['status'] ) && ! empty( wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" ) ) ) {
			$pipeline_stage = wpf_get_option( "ec_woo_status_wc-{$order_args['status']}" );
		}

		$pipeline_stage = explode( '+', $pipeline_stage );

		$order = array(
			'associations' => array(
				array(
					'to'    => array(
						'id' => $contact_id,
					),
					'types' => array(
						array(
							'associationCategory' => 'HUBSPOT_DEFINED',
							'associationTypeId'   => 3,
						),
					),
				),
			),
			'properties'   => array(
				'deal_currency_code' => $order_args['currency'],
				'dealname'           => $order_args['order_label'],
				'pipeline'           => $pipeline_stage[0],
				'dealstage'          => $pipeline_stage[1],
				'closedate'          => $order_date * 1000,
				'amount'             => round( $order_args['total'], 2 ),
			),
		);

		/**
		 * Filters the deal data.
		 *
		 * @since 1.15.0
		 *
		 * @link https://wpfusion.com/documentation/ecommerce-tracking/hubspot-ecommerce/#custom-deal-fields
		 *
		 * @param array $order    The deal.
		 * @param int   $order_id ID of the order.
		 */

		$order = apply_filters( 'wpf_ecommerce_hubspot_add_deal', $order, $order_id );

		if ( empty( $order ) ) {
			return false; // allow for skipping.
		}

		// This helps folks that were using the v1 API for custom properties, it moves the data into v3 format.

		foreach ( $order['properties'] as $i => $property ) {

			if ( is_array( $property ) && isset( $property['name'] ) ) {
				$order['properties'][ $property['name'] ] = $property['value'];
				unset( $order['properties'][ $i ] );
			}
		}

		// Maybe get an existing deal ID from the MakeWebBetter plugin.

		if ( empty( $order_args['invoice_id'] ) ) {
			$order_args['invoice_id'] = get_post_meta( $order_id, 'hubwoo_ecomm_deal_id', true );
		}

		if ( empty( $order_args['invoice_id'] ) ) {
			$message = 'Creating deal for <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:';
		} else {
			$message = 'Updating HubSpot deal #' . $order_args['invoice_id'] . ' from <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:';
		}

		wpf_log(
			'info',
			$order_args['user_id'],
			$message,
			array(
				'meta_array_nofilter' => $order,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $order );

		if ( empty( $order_args['invoice_id'] ) ) {
			$response = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/deals/', $params );
		} else {
			$params['method'] = 'PATCH';
			$response         = wp_remote_request( 'https://api.hubapi.com/crm/v3/objects/deals/' . $order_args['invoice_id'], $params );
		}

		if ( is_wp_error( $response ) ) {

			if ( false !== strpos( $response->get_error_message(), 'resource not found' ) ) {

				// We tried to update a deal that's been deleted. Clear the saved ID and start over.
				delete_post_meta( $order_id, 'wpf_ec_hubspot_invoice_id' );
				delete_post_meta( $order_id, 'hubwoo_ecomm_deal_id' );
				return $this->add_order( $order_id, $contact_id, $order_args );

			}

			return $response;

		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		// Multi currency warning
		if ( strtolower( $response->properties->deal_currency_code ) !== 'usd' && intval( $response->properties->hs_exchange_rate ) === 1 ) {
			wpf_log(
				'warning',
				$order_args['user_id'],
				__( 'Please add your store currency with the correct exchange rate in your HubSpot account.', 'wp-fusion' ),
			);
		}

		$deal_id = $response->id;

		if ( ! empty( $order_args['invoice_id'] ) ) {
			return $deal_id; // If we've just updated an existing deal we don't need to attach a note or modify the line items.
		}

		if ( wpf_get_option( 'hubspot_add_note' ) ) {

			// Attach note to the deal
			$body = '';

			foreach ( $order_args['products'] as $product ) {

				$body .= $product['name'] . ' - ' . $order_args['currency_symbol'] . $product['price'];

				if ( $product['qty'] > 1 ) {
					$body .= ' - x' . $product['qty'];
				}

				$body .= '<br/>';

			}

			foreach ( $order_args['line_items'] as $line_item ) {

				$body .= $line_item['title'] . ' - ' . $order_args['currency_symbol'] . $line_item['price'] . '<br />';

			}

			$note_data = array(
				'properties'   => array(
					'hs_note_body' => $body,
					'hs_timestamp' => date( 'c' ),
				),
				'associations' => array(
					array(
						'to'    => array(
							'id' => $deal_id,
						),
						'types' => array(
							array(
								'associationCategory' => 'HUBSPOT_DEFINED',
								'associationTypeId'   => 214,
							),
						),
					),
				),
			);

			/**
			 * Filters the note data.
			 *
			 * @since 1.17.9
			 *
			 * @param array $note_data The array of data used to create the note.
			 * @param int   $order_id        The order ID.
			 */

			$note_data = apply_filters( 'wpf_ecommerce_hubspot_add_engagement', $note_data, $order_id );

			wpf_log(
				'info',
				$order_args['user_id'],
				'Adding deal note to order <a href="' . $order_args['order_edit_link'] . '" target="_blank">#' . $order_id . '</a>:',
				array(
					'meta_array_nofilter' => $note_data,
					'source'              => 'wpf-ecommerce',
				)
			);

			$params['body']   = wp_json_encode( $note_data );
			$params['method'] = 'POST';
			$response         = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/notes', $params );

			if ( is_wp_error( $response ) ) {

				wpf_log( 'error', $order_args['user_id'], 'Error adding note to deal: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return $deal_id;

			}
		}

		if ( wpf_get_option( 'hubspot_sync_products' ) ) {

			// Sync products and line items
			foreach ( $order_args['products'] as $product ) {

				if ( empty( $product['crm_product_id'] ) ) {

					// Get the product ID
					$response = $this->add_product( $product );

					if ( is_wp_error( $response ) ) {

						wpf_log(
							'error',
							$order_args['user_id'],
							'Error registering new product in HubSpot: ' . $response->get_error_message(),
							array(
								'source'              => 'wpf-ecommerce',
								'meta_array_nofilter' => $product,
							)
						);
						continue;

					} else {

						$product['crm_product_id'] = $response;

					}
				}

				// Add the line item
				$line_item_id = $this->add_line_item( $product, $order_id );

				if ( is_wp_error( $line_item_id ) ) {

					wpf_log(
						'error',
						$order_args['user_id'],
						'Error creating line item: ' . $line_item_id->get_error_message(),
						array(
							'source'              => 'wpf-ecommerce',
							'meta_array_nofilter' => $product,
						)
					);
					continue;
				}

				$params           = wp_fusion()->crm->get_params();
				$params['method'] = 'PUT';

				// Associate the line item with the deal.
				$response = wp_remote_request( 'https://api.hubapi.com/crm/v3/objects/line_items/' . $line_item_id . '/associations/DEAL/' . $deal_id . '/LINE_ITEM_TO_DEAL', $params );

				if ( is_wp_error( $response ) ) {
					wpf_log( 'error', $order_args['user_id'], 'Error associating line item ID ' . $line_item_id . ' with deal ID ' . $deal_id . ': ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					continue;
				}

				// Done.
			}
		}

		return $deal_id;

	}

	/**
	 * Update a deal stage when an order status is changed
	 *
	 * @access public
	 * @return bool|WP_Error True on success, WP_Error on error
	 */
	public function change_stage( $deal_id, $stage, $order_id ) {

		$pipeline_stage = explode( '+', $stage );

		$deal = array(
			'properties' => array(
				'pipeline'  => $pipeline_stage[0],
				'dealstage' => $pipeline_stage[1],
			),
		);

		$params = wp_fusion()->crm->get_params();

		/**
		 * Filters the deal when changing the stage.
		 *
		 * @since 1.17.9
		 *
		 * @param array $deal     The deal data.
		 * @param int   $deal_id  The deal ID to be updated.
		 * @param int   $order_id The order ID.
		 */

		$deal = apply_filters( 'wpf_ecommerce_hubspot_change_deal_stage', $deal, $deal_id, $order_id );

		$params['body']   = wp_json_encode( $deal );
		$params['method'] = 'PATCH';

		$response = wp_remote_request( 'https://api.hubapi.com/crm/v3/objects/deals/' . $deal_id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}



}
