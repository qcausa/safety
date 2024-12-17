<?php

class WPF_EC_Ontraport {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'products', 'refunds' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 20, 2 );

		if ( false === get_option( 'wpf_ontraport_products' ) ) {
			$this->sync_products();
		}

		if ( false === get_option( 'wpf_ontraport_taxes' ) ) {
			$this->sync_taxes();
		}

		// Sync the data during the intitial sync.

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );
		add_action( 'wpf_sync', array( $this, 'sync_taxes' ) );

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ec_op_header'] = array(
			'title'   => __( 'Ontraport Enhanced Ecommerce', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
			'desc'    => sprintf( __( 'For more information on WP Fusion\'s ecommerce integration with Ontraport, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/ecommerce-tracking/ontraport-ecommerce/" target="_blank">', '</a>' ),
		);

		$settings['ec_op_prices'] = array(
			'title'   => __( 'Ontraport Pricing', 'wp-fusion' ),
			'type'    => 'radio',
			'section' => 'ecommerce',
			'choices' => array(
				'op'       => __( 'Use product prices as set in Ontraport', 'wp-fusion' ),
				'checkout' => __( 'Use product prices as paid at checkout' ),
			),
			'std'     => 'op',
		);

		$product_options = array(
			'no_shipping' => '- ' . __( 'Don\'t track shipping', 'wp-fusion' ) . ' -',
			'create'      => '- ' . __( 'Create as needed', 'wp-fusion' ) . ' -',
		);

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		if ( ! empty( $available_products ) ) {
			$product_options = $product_options + $available_products;
		}

		// Maybe select the "Shipping" product if one already exists

		$std = array_search( 'Shipping', $product_options );

		if ( empty( $std ) ) {
			$std = 'no_shipping';
		}

		$settings['ec_op_shipping'] = array(
			'title'   => __( 'Shipping Product', 'wp-fusion' ),
			'type'    => 'select',
			'std'     => $std,
			'section' => 'ecommerce',
			'choices' => $product_options,
			'desc'    => __( 'WP Fusion can use a pseudo-product in Ontraport to track shipping charges. You can select your shipping product here, or leave blank to have one created automatically at the next checkout.', 'wp-fusion' ),
		);

		$tax_options = array(
			'ignore'  => '- ' . __( 'Don\'t sync taxes', 'wp-fusion' ) . ' -',
			'product' => '- ' . __( 'Include in product prices', 'wp-fusion' ) . ' -',
		);

		$available_taxes = get_option( 'wpf_' . wp_fusion()->crm->slug . '_taxes', array() );

		if ( ! empty( $available_taxes ) ) {
			$tax_options = $tax_options + $available_taxes;
		}

		$settings['ec_op_taxes'] = array(
			'title'   => __( 'Taxes / Tax Object', 'wp-fusion' ),
			'type'    => 'select',
			'std'     => 'ignore',
			'section' => 'ecommerce',
			'choices' => $tax_options,
			'desc'    => sprintf( __( 'Ontraport has limited support for taxes over the API. Here you can choose how WP Fusion should treat taxes. For more info %1$ssee the documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/ecommerce-tracking/ontraport-ecommerce/#taxes" target="_blank">', '</a>' ),
		);

		return $settings;

	}

	/**
	 * Syncs available products and taxes
	 *
	 * @since 1.3
	 * @return array Products
	 */

	public function sync_products() {

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$products = array();
		$offset   = 0;
		$proceed  = true;

		while ( $proceed ) {

			$request  = 'https://api.ontraport.com/1/Products?range=50&start=' . $offset . '';
			$response = wp_remote_get( $request, wp_fusion()->crm->params );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body_json = json_decode( $response['body'], true );

			foreach ( (array) $body_json['data'] as $product ) {
				$products[ $product['id'] ] = $product['name'];
			}

			$offset = $offset + 50;

			if ( count( $body_json['data'] ) < 50 ) {
				$proceed = false;
			}
		}

		asort( $products );

		update_option( 'wpf_ontraport_products', $products, false );

		return $products;
	}

	/**
	 * Syncs available taxes
	 *
	 * @since 1.16.2
	 * @return array Taxes
	 */

	public function sync_taxes() {

		$taxes   = array();
		$offset  = 0;
		$proceed = true;

		while ( $proceed ) {

			$request  = 'https://api.ontraport.com/1/objects?objectID=63&range=50&start=' . $offset . '';
			$response = wp_remote_get( $request, wp_fusion()->crm->params );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body_json = json_decode( $response['body'], true );

			foreach ( (array) $body_json['data'] as $tax ) {
				$taxes[ $tax['id'] ] = $tax['name'] . ' (' . $tax['rate'] . '%)';
			}

			$offset = $offset + 50;

			if ( empty( $body_json['data'] ) || count( $body_json['data'] ) < 50 ) {
				$proceed = false;
			}
		}

		asort( $taxes );

		update_option( 'wpf_ontraport_taxes', $taxes, false );

		return $taxes;

	}

	/**
	 * Register a product in Ontraport.
	 *
	 * @param array $product
	 */
	public function add_product( $product ) {

		// First look it up.

		$product['crm_product_id'] = $this->get_product_id( $product );

		$data = array(
			'name'        => $product['name'],
			'price'       => $product['price'],
			'sku'         => isset( $product['sku'] ) ? $product['sku'] : false,
			'external_id' => isset( $product['id'] ) ? $product['id'] : false,
		);

		/**
		 * Filters the product data.
		 *
		 * @since 1.18.3
		 *
		 * @param array $data    The product data to send to the CRM.
		 * @param array $product The product data from WordPress.
		 */

		$data = apply_filters( 'wpf_ecommerce_ontraport_add_product', $data, $product );

		// Update.
		if ( ! empty( $product['crm_product_id'] ) ) {
			$data['id'] = $product['crm_product_id'];
		}

		// Logger.
		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $data['name'] . '</a> in Ontraport:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		// Add or Update product.
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $data );

		$response = wp_remote_post( 'https://api.ontraport.com/1/Products', $params );

		if ( is_wp_error( $response ) ) {

			return $response;

		} else {

			$body       = json_decode( wp_remote_retrieve_body( $response ), true );
			$product_id = $body['data']['id'];

		}

		update_post_meta( $product['id'], 'ontraport_product_id', $product_id );

		// Update the global products list.
		$ontraport_products                = get_option( 'wpf_ontraport_products', array() );
		$ontraport_products[ $product_id ] = $product['name'];
		update_option( 'wpf_ontraport_products', $ontraport_products, false );

		return $product_id;

	}

	/**
	 * Search for product in CRM.
	 *
	 * @since 1.23.0
	 *
	 * @param array $product Product attributes.
	 * @return int|bool Product ID if found, false if not.
	 */
	public function get_product_id( $product ) {
		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		// Try and find an existing product by name.
		$query    = '[{ "field":{"field":"name"}, "op":"=", "value":{"value":"' . $product['name'] . '"} }]';
		$request  = 'https://api.ontraport.com/1/Products?condition=' . urlencode( $query );
		$response = wp_remote_get( $request, wp_fusion()->crm->params );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data'] ) && ! empty( $product['sku'] ) ) {
			// Try with sku.
			$query    = '[{ "field":{"field":"sku"}, "op":"=", "value":{"value":"' . $product['sku'] . '"} }]';
			$request  = 'https://api.ontraport.com/1/Products?condition=' . urlencode( $query );
			$response = wp_remote_get( $request, wp_fusion()->crm->params );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		// If we found a product, update it.
		if ( ! empty( $body['data'] ) ) {
			return $body['data'][0]['id'];
		} else {
			return false;
		}

	}



	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  mixed Invoice ID or
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		// Convert date to GMT.
		$offset      = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$order_date -= $offset;

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$params = wp_fusion()->crm->params;

		$order_data = array(
			'objectID'          => 0,
			'contact_id'        => $contact_id,
			'chargeNow'         => 'chargeLog',
			'trans_date'        => (int) $order_date * 1000,
			'invoice_template'  => 0,
			'offer'             => array( 'products' => array() ),
			'delay'             => 0,
			'external_order_id' => $order_id,
		);

		// Referral handling (The OPRID is a combination between a promo tool id and the affiliate id. ).
		if ( isset( $_COOKIE['oprid'] ) ) {

			wpf_log( 'info', 0, 'Recording referral for partner ID ' . $_COOKIE['oprid'], array( 'source' => 'wpf-ecommerce' ) );

			$order_data['oprid'] = $_COOKIE['oprid'];

		}

		$total_products = 0;

		//
		// Products.
		//

		// Get product IDs for each product.

		foreach ( $order_args['products'] as $product ) {

			if ( empty( $product['crm_product_id'] ) ) {

				$product['crm_product_id'] = $this->get_product_id( $product );

				// Create each product if it doesn't exist yet.
				if ( false === $product['crm_product_id'] ) {
					$product['crm_product_id'] = $this->add_product( $product );
				}

				// Error handling for adding products.

				if ( is_wp_error( $product['crm_product_id'] ) ) {
					return $product['crm_product_id'];
				}
			}

			if ( empty( $product['qty'] ) ) {
				// Ontraport will throw an error for empty quantitity so this lets us properly sync refunded orders.
				$product['price'] = 0;
			}

			$product_data = array(
				'name'     => $product['name'],
				'id'       => $product['crm_product_id'],
				'quantity' => $product['qty'],
				'sku'      => $product['sku'],
			);

			if ( 'checkout' === wpf_get_option( 'ec_op_prices' ) ) {

				// By default we'll just send the product ID and let Ontraport
				// calculate the price. This can be overridden by selecting
				// "Use prices paid at checkout" in the WPF settings.

				$product_data['price'] = array(
					array(
						'price'         => $product['price'],
						'payment_count' => 1,
						'unit'          => 'day',
					),
				);

				if ( 'product' === wpf_get_option( 'ec_op_taxes', 'ignore' ) && ! empty( $product['tax'] ) ) {

					// If taxes are added to the product price.
					$product_data['price'][0]['price'] += $product['tax'];

				}
			}

			if ( ! empty( $product['tax'] ) ) {
				$product_data['taxable'] = true;
				$product_data['tax']     = true;
			}

			$order_data['offer']['products'][] = $product_data;

			$total_products += $product['qty'];

		}

		//
		// Line items.
		//

		foreach ( $order_args['line_items'] as $line_item ) {

			if ( 'shipping' === $line_item['type'] ) {

				// Shipping doesn't work properly with the current API so we get around that by creating a pseudo-product for shipping fees.

				$product_id = wpf_get_option( 'ec_op_shipping' );

				if ( 'no_shipping' === $product_id ) {
					continue;
				}

				if ( $product_id === 'create' || empty( $product_id ) ) {

					// First let's make sure there isn't one already.

					$available_products = get_option( 'wpf_ontraport_products', array() );
					$product_id         = array_search( $line_item['title'], $available_products ); // search by method name.

					if ( ! $product_id ) {
						$product_id = array_search( 'Shipping', $available_products ); // generic shipping product.
					}

					if ( ! $product_id ) {

						// Create the product to handle shipping.

						$product_data = array(
							'name'  => $line_item['title'],
							'price' => 0,
							'id'    => 0,
						);

						$product_id = $this->add_product( $product_data );

						if ( is_wp_error( $product_id ) ) {
							return $product_id;
						}
					}

				}

				$product_data = array(
					'name'     => $line_item['title'],
					'quantity' => 1,
					'id'       => $product_id,
					'price'    => array(
						array(
							'price'         => $line_item['price'],
							'payment_count' => 1,
							'unit'          => 'day',
						),
					),
				);

				$order_data['offer']['products'][] = $product_data;

			} elseif ( 'tax' === $line_item['type'] ) {

				// Taxes don't work well, see http://wpfusion.com/documentation/ecommerce-tracking/ontraport-ecommerce/#taxes.

				$tax_id = wpf_get_option( 'ec_op_taxes', 'ignore' );

				if ( 'ignore' === $tax_id ) {

					// Ignore taxes.

					continue;

				} elseif ( is_numeric( $tax_id ) ) {

					// We're using a pre-created tax ID.

					$order_data['offer']['taxes'] = array(
						array(
							'id' => $tax_id,
						),
					);

					$order_data['hasTaxes'] = true;

				}
			} elseif ( 'discount' === $line_item['type'] ) {

				// Discounts don't work properly with the current API, but we can adjust the prices of the other order items to reflect the discount and at least get a proper total

				if ( wpf_get_option( 'ec_op_prices' ) == 'checkout' ) {

					$discount_per_product = $line_item['price'] / $total_products;

					foreach ( $order_data['offer']['products'] as $i => $product ) {

						$order_data['offer']['products'][ $i ]['price'][0]['price'] = round( $order_data['offer']['products'][ $i ]['price'][0]['price'] + $discount_per_product, 2 );

					}
				}
			} else {

				// Addons and other meta

				$product = array(
					'id'    => $line_item['id'],
					'name'  => $line_item['title'],
					'price' => 0,
				);

				$product['crm_product_id'] = $this->add_product( $product );

				$product_data = array(
					'name'     => $product['name'],
					'id'       => $product['crm_product_id'],
					'quantity' => 1,
				);

				$order_data['offer']['products'][] = $product_data;

			}
		}

		/**
		 * Filters the order data.
		 *
		 * @since 1.17.11
		 *
		 * @param array $order_data The order data.
		 * @param int   $order_id   ID of the order.
		 */

		$order_data = apply_filters( 'wpf_ecommerce_ontraport_add_transaction', $order_data, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $order_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params['body'] = wp_json_encode( $order_data );

		$response = wp_remote_post( 'https://api.ontraport.com/1/transaction/processManual', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body       = json_decode( wp_remote_retrieve_body( $response ), true );
		$invoice_id = $body['data']['invoice_id'];

		return $invoice_id;

	}


	/**
	 * Mark a previously added order as refunded
	 *
	 * @access  public
	 * @return  mixed Bool or WP_Error
	 */

	public function refund_order( $transaction_id, $refund_amount, $contact_id = '' ) {

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$params = wp_fusion()->crm->params;

		$refund_data = array(
			'ids' => array( $transaction_id ),
		);

		$params['body']   = wp_json_encode( $refund_data );
		$params['method'] = 'PUT';

		$response = wp_remote_request( 'https://api.ontraport.com/1/transaction/refund', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
