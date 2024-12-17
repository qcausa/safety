<?php

class WPF_EC_AgileCRM {

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

		if ( get_option( 'wpf_agilecrm_products' ) == false ) {
			$this->sync_products();
		}

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );

	}


	/**
	 * Refund an order.
	 *
	 * @since 1.19.0
	 *
	 * @param int    $transaction_id The transaction ID.
	 * @param int    $final_amount   The final order amount.
	 * @param int    $refund_amount  The refund amount.
	 * @param array  $order_args   The original order args.
	 * @param string $contact_id  The contact ID in the CRM.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function refund_order( $transaction_id, $final_amount, $refund_amount, $order_args, $contact_id ) {
		// Refund order.
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode(
			array(
				'id'             => $transaction_id,
				'expected_value' => $final_amount,
			)
		);

		$params['method'] = 'PUT';

		$response = wp_remote_post( 'https://' . wp_fusion()->crm->domain . '.agilecrm.com/dev/api/opportunity/partial-update', $params );
		$body     = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_object( $body ) && isset( $body->errors ) ) {
			return new WP_Error( 'error', $body->errors[0]->title );
		}

		return true;

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

		$calc_totals = 0;
		$did_sync    = false;

		foreach ( $order_args['products'] as $product ) {

			if ( empty( $product['crm_product_id'] ) ) {

				if ( $did_sync == false ) {

					$this->sync_products();
					$did_sync = true;

				}

				$response = $this->add_product( $product );

				if ( is_wp_error( $response ) ) {

					wpf_log(
						'error',
						$order_args['user_id'],
						'Error registering new product in AgileCRM: ' . $response->get_error_message(),
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

			$items[] = (object) array(
				'id'    => $product['crm_product_id'],
				'name'  => $product['name'],
				'price' => $product['price'],
				'qty'   => $product['qty'],
				'total' => $product['qty'] * $product['price'],
			);

			$calc_totals += $product['qty'] * $product['price'];

		}

		$order_data = array(
			'name'           => $order_args['order_label'],
			'expected_value' => $calc_totals,
			'close_date'     => $order_date,
			'milestone'      => 'Won',
			'products'       => $items,
		);

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $order_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $order_data );

		// Get email for contact
		$email = wp_fusion()->crm->get_email_from_cid( $contact_id );

		$response = wp_remote_post( 'https://' . wp_fusion()->crm->domain . '.agilecrm.com/dev/api/opportunity/email/' . $email, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->id;

	}


	/**
	 * Sync available products
	 *
	 * @access  public
	 * @return  array Products
	 */

	public function sync_products() {

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$products = array();

		$request  = 'https://' . wp_fusion()->crm->domain . '.agilecrm.com/dev/api/products';
		$response = wp_remote_get( $request, wp_fusion()->crm->params );

		if ( is_wp_error( $response ) ) {
			wpf_log( $response->get_error_code(), 0, 'Error syncing products: ' . $response->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return;
		}

		$body_json = json_decode( $response['body'], true );

		foreach ( (array) $body_json as $product ) {
			$products[ $product['id'] ] = $product['name'];
		}

		update_option( 'wpf_agilecrm_products', $products, false );

		return $products;

	}

	/**
	 * Add a new product into the CRM.
	 *
	 * @since 1.20.0
	 *
	 * @param array $product The product.
	 * @return int|WP_Error The product ID or a WP_Error object.
	 */
	public function add_product( $product ) {

		$product_data = array(
			'name'  => $product['name'],
			'sku'   => $product['sku'],
			'price' => $product['price'],
			'image' => $product['image'],
		);

		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in AgileCRM:',
			array(
				'meta_array_nofilter' => $product_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$request = 'https://' . wpf_get_option( 'agile_domain' ) . '.agilecrm.com/dev/api/products/';
		$params  = wp_fusion()->crm->get_params();

		// Update.
		if ( ! empty( $product['crm_product_id'] ) ) {
			$product_data['id'] = $product['crm_product_id'];
			$params['method']   = 'PUT';
		}

		$params['body'] = wp_json_encode( $product_data );
		$response       = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {

			if ( false !== strpos( $response->get_error_message(), 'Product with this name already exists' ) ) {

				return new WP_Error( 'error', 'A duplicate record error was triggered while trying to register a new product, but WP Fusion was unable to find a duplicate product via search. This product will not be synced. <stromg>Error message:</strong> ' . $response->get_error_message() );

			} else {
				return $response;
			}
		} else {
			$response   = json_decode( wp_remote_retrieve_body( $response ) );
			$product_id = $response->id;

			update_post_meta( $product['id'], 'agilecrm_product_id', $product_id );
			$products                = get_option( 'wpf_agilecrm_products', array() );
			$products[ $product_id ] = $product['name'];
			update_option( 'wpf_agilecrm_products', $products, false );
		}

		return $product_id;
	}


}
