<?php

class WPF_EC_MailerLite {

	/**
	 * Lets other integrations know which features are supported by the CRM.
	 */
	public $supports = array( 'products' );

	/**
	 * Get Shop ID.
	 *
	 * @since 1.22.0
	 *
	 * @var integer
	 */
	private $shop_id;

	/**
	 * Get things started.
	 *
	 * @since 1.22.0
	 */
	public function init() {

		$this->shop_id = $this->get_shop();

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );

	}

	/**
	 * Get Shop id or create it.
	 *
	 * @since 1.22.0
	 *
	 * @return int|false The shop ID or false on failure.
	 */
	public function get_shop() {

		if ( wpf_get_option( 'mailerlite_shop_id' ) ) {
			return wpf_get_option( 'mailerlite_shop_id' );
		}

		$response = wp_remote_get( wp_fusion()->crm->api_url . 'ecommerce/shops?limit=0', wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			wpf_log( 'error', 0, 'Error getting MailerLite shop: ' . $response->get_error_message(), array( 'source' => 'wp-fusion-ecommerce' ) );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body->data ) ) {

			foreach ( $body->data as $shop ) {

				if ( get_bloginfo( 'url' ) === $shop->url ) {
					wp_fusion()->settings->set( 'mailerlite_shop_id', $shop->id );
					return absint( $shop->id );
				}
			}
		}

		// No shop found, create one.
		return $this->create_shop();

	}

	/**
	 * Create Shop.
	 *
	 * @since 1.22.0
	 *
	 * @return string
	 */
	public function create_shop() {

		$data = array(
			'name'     => get_bloginfo( 'name' ),
			'url'      => get_bloginfo( 'url' ),
			'currency' => 'USD',
			'platform' => 'wp-fusion',
			'enabled'  => true,
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $data );

		$response = wp_remote_post( wp_fusion()->crm->api_url . 'ecommerce/shops', $params );

		if ( is_wp_error( $response ) ) {
			wpf_log( 'error', 0, 'Error registering MailerLite shop: ' . $response->get_error_message(), array( 'source' => 'wp-fusion-ecommerce' ) );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		$shop_id = absint( $body->data->id );

		wp_fusion()->settings->set( 'mailerlite_shop_id', $shop_id );

		return $shop_id;
	}


	/**
	 * Add an order.
	 *
	 * @since 1.22.0
	 *
	 * @param int   $order_id   The order ID in WordPress.
	 * @param int   $contact_id The contact ID in the CRM.
	 * @param array $order_args The order args.
	 *
	 * @return int The order ID in the CRM.
	 */
	public function add_order( $order_id, $contact_id, $order_args ) {

		$items = array();

		foreach ( $order_args['products'] as $product ) {

			if ( empty( $product['crm_product_id'] ) ) {

				$response = $this->add_product( $product );

				if ( is_wp_error( $response ) ) {

					wpf_log(
						'error',
						$order_args['user_id'],
						'Error registering new product in MailerLite: ' . $response->get_error_message(),
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

			$items['items'][] = array(
				'ecommerce_product_id' => $product['crm_product_id'],
				'quantity'             => $product['qty'],
				'price'                => $product['qty'] * $product['price'],
			);

		}

		$customer = array(
			'email'             => $order_args['user_email'],
			'create_subscriber' => true,
		);

		$order_data = array(
			'customer'    => $customer,
			'total_price' => $order_args['total'],
			'cart'        => $items,
			'status'      => 'complete',
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

		$response = wp_remote_post( wp_fusion()->crm->api_url . 'ecommerce/shops/' . $this->shop_id . '/orders', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return absint( $response->data->id );

	}


	/**
	 * Sync available products
	 *
	 * @since 1.22.0
	 *
	 * @return array Products
	 */
	public function sync_products() {

		$products = array();

		$request  = wp_fusion()->crm->api_url . 'ecommerce/shops/' . $this->shop_id . '/products';
		$response = wp_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			wpf_log( $response->get_error_code(), 0, 'Error syncing products: ' . $response->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		foreach ( $body['data'] as $product ) {
			$products[ $product['id'] ] = $product['name'];
		}

		update_option( 'wpf_mailerlite_products', $products, false );

		return $products;

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

		$request  = wp_fusion()->crm->api_url . 'ecommerce/shops/' . $this->shop_id . '/products?limit=0';
		$response = wp_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		foreach ( $body['data'] as $product_data ) {

			if ( $product_data['name'] === $product['name'] ) {
				return $product_data['id'];
			}
		}

		return false;


	}

	/**
	 * Add a new product into the CRM.
	 *
	 * @since 1.22.0
	 *
	 * @param array $product The product.
	 * @return int|WP_Error The product ID or a WP_Error object.
	 */
	public function add_product( $product ) {

		$product_data = array(
			'name'  => $product['name'],
			'sku'   => $product['sku'],
			'url'   => get_permalink( $product['id'] ),
			'price' => $product['price'],
		);

		if ( $product['image'] ) {
			$product_data['image'] = $product['image'];
		}

		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in MailerLite:',
			array(
				'meta_array_nofilter' => $product_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params  = wp_fusion()->crm->get_params();
		$request = wp_fusion()->crm->api_url . 'ecommerce/shops/' . $this->shop_id . '/products';

		// Update.
		if ( ! empty( $product['crm_product_id'] ) ) {
			$params['method'] = 'PUT';
			$request         .= '/' . $product['crm_product_id'];
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

			$response = json_decode( wp_remote_retrieve_body( $response ) );

			$product_id = absint( $response->data->id );

			update_post_meta( $product['id'], 'mailerlite_product_id', $product_id );
			$products                = get_option( 'wpf_mailerlite_products', array() );
			$products[ $product_id ] = $product['name'];
			update_option( 'wpf_mailerlite_products', $products, false );
		}

		return $product_id;
	}


}
