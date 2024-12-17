<?php

class WPF_EC_Drip {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'status_changes', 'refunds', 'products' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

	}


	/**
	 * Compatibility checks
	 *
	 * @access public
	 * @return array Notices
	 */

	public function compatibility_notices( $notices ) {

		if ( is_plugin_active( 'drip/drip.php' ) ) {

			$notices['drip'] = __( 'The <strong>Drip for WooCommerce</strong> plugin is active. You will get duplicate order data in Drip if you\'re using WP Fusion\'s Enhanced Ecommerce addon and Drip for WooCommerce at the same time.', 'wp-fusion' );

		}

		return $notices;

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ecommerce_header'] = array(
			'title'   => __( 'Drip Ecommerce Tracking', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
			'url'     => 'https://wpfusion.com/documentation/ecommerce-tracking/drip-ecommerce/',
		);

		$settings['orders_enabled'] = array(
			'title'   => __( 'Orders', 'wp-fusion' ),
			'desc'    => __( 'Create an <a href="https://help.drip.com/hc/en-us/articles/360002603911-Orders" target="_blank">order</a> in Drip for each sale.', 'wp-fusion' ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'unlock'  => array( 'orders_api_version' ),
			'lock'    => array( 'total_revenue_field' ),
		);

		return $settings;

	}


	/**
	 * Add Order
	 * Add an order.
	 *
	 * @param int   $order_id    The order ID.
	 * @param int   $contact_id  The contact ID in the CRM.
	 * @param array $order_args  The order args.
	 *
	 * @since 1.0
	 * @since 1.23.2 Added checkout URL.
	 *
	 * @return bool|WP_Error True if successful, WP_Error on failure.
	 */
	public function add_order( $order_id, $contact_id, array $order_args ) {

		if ( ! wpf_get_option( 'orders_enabled', true ) ) {
			return new WP_Error( 'warning', 'Enhanced Ecommerce is not enabled in the WP Fusion settings, so no ecommerce data was synced to Drip.' );
		}

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		$result = true;

		// Convert date.
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		$order_date = new DateTime( date( 'c', $order_date ) );

		// DateTimeZone throws an error with 0 as the timezone.
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		// Discount code.
		$discount_code = '';
		if ( ! empty( $order_args['line_items'] ) ) {
			foreach ( $order_args['line_items'] as $line => $value ) {
				if ( 'discount' === $value['type'] ) {
					$discount_code = $value['code'];
				}
			}
		}

		$items    = array();
		$discount = 0;
		$shipping = 0;
		$tax      = 0;

		// v3 API. Build up items array.
		foreach ( $order_args['products'] as $product ) {

			// For Woo Dynamic Pricing addon. Drip throws an error if a price is less than 0.
			if ( floatval( $product['price'] ) < 0 ) {

				$discount += abs( $product['price'] );
				continue;
			}

			$item_data = array(
				'product_id'         => strval( isset( $product['parent_id'] ) ? $product['parent_id'] : $product['id'] ),
				'product_variant_id' => strval( $product['id'] ),
				'sku'                => isset( $product['sku'] ) ? $product['sku'] : false,
				'price'              => round( $product['price'], 2 ),
				'name'               => substr( $product['name'], 0, 255 ), // Drip throws an error if the name is longer than 255 characters.
				'quantity'           => intval( $product['qty'] ),
				'total'              => round( ( $product['price'] * $product['qty'] ), 2 ),
				'product_url'        => get_permalink( $product['id'] ),
				'image_url'          => isset( $product['image'] ) ? $product['image'] : false,
				'categories'         => isset( $product['categories'] ) ? $product['categories'] : false,
			);

			// Clean up possible empty values.
			$item_data = array_filter( $item_data );

			if ( ! isset( $item_data['price'] ) ) {
				$item_data['price'] = 0;
			}

			$items[] = (object) $item_data;

		}

		foreach ( $order_args['line_items'] as $line_item ) {

			if ( 'shipping' == $line_item['type'] ) {

				// Shipping.

				$items[] = (object) array(
					'product_id' => 'SHIPPING',
					'price'      => round( $line_item['price'], 2 ),
					'total'      => round( $line_item['price'], 2 ),
					'name'       => substr( $line_item['title'] . ' - ' . $line_item['description'], 0, 255 ),
					'quantity'   => 1,
				);

				$shipping += abs( $line_item['price'] );

			} elseif ( $line_item['type'] == 'tax' ) {

				// Tax.
				$tax += abs( $line_item['price'] );

			} elseif ( 'discount' == $line_item['type'] ) {

				// Discounts.
				$discount += abs( $line_item['price'] );

			} else {

				// Addons & variations.
				$item_data = array(
					'product_id' => (string) $line_item['id'],
					'sku'        => $line_item['sku'],
					'price'      => round( $line_item['price'], 2 ),
					'name'       => substr( $line_item['title'], 0, 255 ),
					'quantity'   => intval( $line_item['qty'] ),
					'total'      => round( ( $line_item['price'] * $line_item['qty'] ), 2 ),
				);

				// Clean up possible empty values.
				$item_data = array_filter( $item_data );

				if ( ! isset( $item_data['price'] ) ) {
					$item_data['price'] = 0;
				}

				$items[] = (object) $item_data;

			}
		}

		$order = (object) array(
			'provider'        => $order_args['provider'],
			'person_id'       => $contact_id,
			'action'          => isset( $order_args['action'] ) ? $order_args['action'] : 'placed',
			'occurred_at'     => $order_date->format( 'c' ),
			'order_id'        => (string) $order_id,
			'order_public_id' => $order_args['order_label'],
			'grand_total'     => round( $order_args['total'], 2 ),
			'total_discounts' => round( $discount, 2 ),
			'total_taxes'     => round( $tax, 2 ),
			'total_shipping'  => round( $shipping, 2 ),
			'currency'        => $order_args['currency'],
			'order_url'       => $order_args['order_edit_link'],
			'items'           => array_reverse( $items ),
			'payment_method'  => $order_args['payment_method'],
			'discount_code'   => $discount_code,
			'checkout_url'    => isset( $order_args['checkout_url'] ) ? $order_args['checkout_url'] : '',
		);

		if ( ! empty( $order_args['refund_amount'] ) ) {
			$order->refund_amount = $order_args['refund_amount'];
		}

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $order,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $order );

		$request  = 'https://api.getdrip.com/v3/' . wp_fusion()->crm->account_id . '/shopper_activity/order';
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {

			wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding order: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return $response;

		} else {

			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $body->request_id ) ) {
				$result = $body->request_id;
			} elseif ( isset( $body->request_ids ) ) {
				$result = $body->request_ids[0];
			}
		}

		if ( wpf_get_option( 'conversions_enabled' ) && isset( $order->action ) && 'placed' === $order->action ) {

			// API request for events (when purchase is made). This is a legacy feature and is no longer an option in the settings.
			$events = array(
				'events' => array(
					(object) array(
						'email'       => wp_fusion()->crm->get_email_from_cid( $contact_id ),
						'occurred_at' => $order_date->format( 'c' ),
						'action'      => 'Conversion',
						'properties'  => array(
							'value' => intval( round( floatval( $order_args['total'] ), 2 ) * 100 ),
						),
					),
				),
			);

			$account_id = wpf_get_option( 'drip_account' );

			$params         = wp_fusion()->crm->get_params();
			$params['body'] = wp_json_encode( $events );
			$request        = 'https://api.getdrip.com/v2/' . $account_id . '/events/';

			$response = wp_remote_post( $request, $params );

			if ( is_wp_error( $response ) ) {

				wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding Event: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return $response;

			}
		}

		return $result;

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

		$order_args['refund_amount'] = round( floatval( $order_args['total'] - $final_amount ), 2 );
		$order_args['action']        = 'refunded';

		$response = $this->add_order( $order_args['order_number'], $contact_id, $order_args );

		return $response;

	}

	/**
	 * Update order statuses in Drip when statuses are changed in Woo
	 *
	 * @access  public
	 * @return  mixed Bool or WP_Error
	 */

	public function order_status_changed( $order_id, $contact_id, $status, $args ) {

		switch ( $status ) {

			case 'completed':
				$args['action'] = 'fulfilled';
				break;

			case 'cancelled':
				$args['action'] = 'canceled';
				break;

			case 'refunded':
				$args['action'] = 'refunded';
				break;

			default:
				return false;
		}

		$response = $this->add_order( $order_id, $contact_id, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

	/**
	 * Drip declares support for "products" so this method is required, but Drip
	 * doesn't have an API method for looking up products by ID, so we'll just return
	 * false.
	 *
	 * @since 1.23.2
	 *
	 * @param array $product_data The product data.
	 * @return bool False.
	 */
	public function get_product_id( $product_data ) {

		return false;

	}

	/**
	 * Register a product in Drip.
	 *
	 * @since 1.20.0
	 *
	 * @param array $product The product data.
	 * @return string|WP_Error The product ID or error.
	 */
	public function add_product( $product ) {

		$categories = array();

		if ( ! empty( $product['category_ids'] ) ) {
			foreach ( $product['category_ids'] as $category_id ) {
				$term = get_term_by( 'id', $category_id, 'product_cat' );
				if ( ! is_wp_error( $term ) ) {
					$categories[] = $term->name;
				}
			}
		}

		$product_data = array(
			'provider'           => 'woocommerce',
			'product_id'         => strval( $product['id'] ),
			'product_variant_id' => strval( $product['id'] ),
			'sku'                => isset( $product['sku'] ) ? $product['sku'] : false,
			'price'              => round( $product['price'], 2 ),
			'name'               => substr( $product['name'], 0, 255 ), // Drip throws an error if the name is longer than 255 characters.
			'title'              => substr( $product['name'], 0, 255 ), // Drip throws an error if the name is longer than 255 characters.
			'inventory'          => intval( $product['stock_quantity'] ),
			'product_url'        => get_permalink( $product['id'] ),
			'image_url'          => $product['image'],
			'categories'         => $categories,
		);

		// Clean up possible empty values
		$product_data = array_filter( $product_data );

		if ( ! isset( $product_data['price'] ) ) {
			$product_data['price'] = 0;
		}

		$log                    = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';
		$product_data['action'] = ! empty( $product['crm_product_id'] ) ? 'updated' : 'created';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in Drip:',
			array(
				'meta_array_nofilter' => $product_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$request        = 'https://api.getdrip.com/v3/' . wpf_get_option( 'drip_account' ) . '/shopper_activity/product';
		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $product_data );
		$response       = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $response->request_id ) ) {
			$product_id = $response->request_id;
		} elseif ( isset( $response->request_ids ) ) {
			$product_id = $response->request_ids[0];
		}

		return $product_id;

	}

}
