<?php

class WPF_EC_Infusionsoft_iSDK {

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

		if ( get_option( 'wpf_infusionsoft_products' ) == false ) {
			$this->sync_products();
		}

		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );
		add_action( 'init', array( $this, 'get_affiliate' ) );

	}

	/**
	 * Compatibility checks
	 *
	 * @access public
	 * @return array Notices
	 */

	public function compatibility_notices( $notices ) {

		if ( is_plugin_active( 'infusedwooPRO/infusedwooPRO.php' ) ) {

			$notices['infusedwoo-plugin'] = 'The <strong>InfusedWoo</strong> plugin is active. You may get duplicate orders in Infusionsoft if you\'re using WP Fusion\'s Enhanced Ecommerce addon and InfusedWoo at the same time.';

		}

		return $notices;

	}

	/**
	 * Gets affiliate tracking data if available
	 *
	 * @access  public
	 * @since   1.2
	 */

	public function get_affiliate() {

		if ( ! empty( $_GET['affiliate'] ) ) {
			setcookie( 'is_aff', intval( $_GET['affiliate'] ), time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}

		if ( ! empty( $_GET['aff'] ) ) {
			setcookie( 'is_affcode', $_GET['aff'], time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}

	}

	/**
	 * Syncs available products and product IDs from Infusionsoft and stores them locally
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function sync_products() {

		$fields   = array( 'Id', 'ProductName' );
		$query    = array( 'Id' => '%' );
		$products = array();

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error syncing products: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		$result = wp_fusion()->crm->app->dsQuery( 'Product', 1000, 0, $query, $fields );

		foreach ( (array) $result as $product ) {
			if ( isset( $product['ProductName'] ) ) {
				$products[ $product['Id'] ] = $product['ProductName'];
			}
		}

		$result = update_option( 'wpf_infusionsoft_products', $products, false );

	}

	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  int Invoice ID
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		$calc_totals = 0;

		// Convert date to GMT
		$offset      = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$order_date -= $offset;

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), $order_args['user_id'], 'Error adding order: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		// Add affiliate referral if present
		if ( isset( $_COOKIE['is_aff'] ) || isset( $_COOKIE['is_affcode'] ) ) {

			if ( ! empty( $_COOKIE['is_aff'] ) ) {

				$is_aff = (int) $_COOKIE['is_aff'];

			} elseif ( ! empty( $_COOKIE['is_affcode'] ) ) {

				// look up the affiliate ID by referral code.

				$returnfields = array( 'Id' );
				$affiliate    = wp_fusion()->crm->app->dsFind( 'Affiliate', 1, 0, 'AffCode', $_COOKIE['is_affcode'], $returnfields );
				$affiliate    = $affiliate[0];
				$is_aff       = (int) $affiliate['Id'];

			}

			if ( ! empty( $is_aff ) ) {

				wpf_log( 'info', $order_args['user_id'], 'Setting referral affiliate ID <strong>' . $is_aff . '</strong> for contact #' . $contact_id, array( 'source' => 'wpf-ecommerce' ) );

				wp_fusion()->crm->app->dsAdd(
					'Referral',
					array(
						'ContactId'   => $contact_id,
						'AffiliateId' => $is_aff,
						'IPAddress'   => $_SERVER['REMOTE_ADDR'],
						'Type'        => 0,
						'DateSet'     => date( 'Y-m-d' ),
					)
				);
			}
		} else {
			$is_aff = 0;
		}

		$order_date = gmdate( 'Ymd\TH:i:s', $order_date );

		$invoice_id = (int) wp_fusion()->crm->app->blankOrder( $contact_id, $order_args['order_label'], $order_date, 0, $is_aff );

		if ( is_wp_error( $invoice_id ) ) {
			return $invoice_id;
		}

		// Create products and add to order.

		foreach ( $order_args['products'] as $product ) {

			// Fix ampersands in product name.
			$product['name'] = str_replace( '&', '&amp;', $product['name'] );

			if ( empty( $product['crm_product_id'] ) ) {

				$product['crm_product_id'] = $this->get_product_id( $product );

				// Create each product if it doesn't exist yet.

				if ( false === $product['crm_product_id'] ) {
					$product['crm_product_id'] = $this->add_product( $product );
				}

				if ( is_wp_error( $product['crm_product_id'] ) ) {
					wpf_log( 'error', wpf_get_current_user_id(), 'Error adding item to order: ' . $product['crm_product_id']->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					continue;
				}

			}

			// $product should have $product['name'], $product['id'], $product['sku'], $product['qty'], $product['price'].
			$result = wp_fusion()->crm->app->addOrderItem( $invoice_id, $product['crm_product_id'], 4, floatval( $product['price'] ), $product['qty'], $product['name'], '' );

			if ( is_wp_error( $result ) ) {
				return new WP_Error( 'error', 'Error adding order item to invoice: ' . $result->get_error_message() );
			}

			$calc_totals += ( floatval( $product['price'] ) * $product['qty'] );

		}

		// Add each line item (not products) to the order.
		foreach ( $order_args['line_items'] as $line_item ) {

			if ( $line_item['type'] == 'discount' ) {
				$type = 7;
			} elseif ( $line_item['type'] == 'tax' ) {
				$type = 2;
			} elseif ( $line_item['type'] == 'shipping' ) {
				$type = 1;
			} elseif ( $line_item['type'] == 'fee' ) {
				$type = 3;
			} else {
				continue;
			}

			wp_fusion()->crm->app->addOrderItem( $invoice_id, 0, $type, floatval( $line_item['price'] ), 1, $line_item['title'], $line_item['description'] );

			$calc_totals += floatval( $line_item['price'] );

		}

		$order_data = array(
			'products'   => $order_args['products'],
			'line_items' => $order_args['line_items'],
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

		// Mark order as paid
		$result = wp_fusion()->crm->app->manualPmt( $invoice_id, $calc_totals, $order_date, $order_args['payment_method'], $order_args['order_label'], true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Add Order Notes
		$job = wp_fusion()->crm->app->dsLoad( 'Invoice', $invoice_id, array( 'JobId' ) );

		if ( is_wp_error( $job ) ) {
			return $job;
		}

		wp_fusion()->crm->app->dsUpdate( 'Job', $job['JobId'], array( 'OrderType' => 'Online' ) );

		return $invoice_id;

	}

	/**
	 * Search for product in CRM.
	 *
	 * @since 1.23.0
	 *
	 * @param array     $product Product attributes.
	 * @return int|bool Product ID if found, false if not.
	 */
	public function get_product_id( $product ) {

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error connecting to Infusionsoft: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		// Try and find an existing product by name.
		$infusionsoft_product = wp_fusion()->crm->app->dsFind( 'Product', 1, 0, 'ProductName', $product['name'], array( 'Id' ) );

		if ( is_wp_error( $infusionsoft_product ) ) {
			wpf_log( $infusionsoft_product->get_error_code(), 0, 'Error looking up product ' . $product['name'] . ' in ' . wp_fusion()->crm->name . ': ' . $infusionsoft_product->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return false; // return false so we still try to create it.
		}

		// Try the lookup by SKU instead.
		if ( empty( $infusionsoft_product ) && ! empty( $product['sku'] ) ) {
			$infusionsoft_product = wp_fusion()->crm->app->dsFind( 'Product', 1, 0, 'Sku', $product['sku'], array( 'Id' ) );
		}

		// If we found a product, update it.
		if ( ! empty( $infusionsoft_product ) ) {
			return $infusionsoft_product[0]['Id'];
		} else {
			return false;
		}

	}



	/**
	 * Register products in Infusionsoft.
	 *
	 * @since 1.23.0
	 *
	 * @param array $product The product data.
	 * @return int|WP_Error The product ID or error message.
	 */
	public function add_product( $product ) {

		if ( ! wp_fusion()->crm->app ) {
			wp_fusion()->crm->connect();
		}

		$product['name'] = str_replace( '&', '&amp;', $product['name'] );

		$new_product = array(
			'ProductName'  => $product['name'],
			'ProductPrice' => $product['price'],
		);

		if ( ! empty( $product['sku'] ) ) {
			$new_product['Sku'] = $product['sku'];
		}

		/**
		 * Filters the product data.
		 *
		 * @since 1.20.0
		 *
		 * @param array $new_product The product data to send to the CRM.
		 * @param array $product     The product data from WordPress.
		 */
		$new_product = apply_filters( 'wpf_ecommerce_infusionsoft_add_product', $new_product, $product );

		$log = ! empty( $product['crm_product_id'] ) ? 'Updating' : 'Creating';

		wpf_log(
			'info',
			0,
			$log . ' product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . get_the_title( $product['id'] ) . '</a> in Infusionsoft:',
			array(
				'meta_array_nofilter' => $new_product,
				'source'              => 'wpf-ecommerce',
			)
		);

		if ( empty( $product['crm_product_id'] ) ) {
			// Add product.
			$infusionsoft_product_id = wp_fusion()->crm->app->dsAdd( 'Product', $new_product );
		} else {
			$infusionsoft_product_id = wp_fusion()->crm->app->dsUpdate( 'Product', $product['crm_product_id'], $new_product );
		}

		// Save the ID to the product.
		update_post_meta( $product['id'], 'infusionsoft_product_id', $infusionsoft_product_id );

		$infusionsoft_products                             = get_option( 'wpf_infusionsoft_products', array() );
		$infusionsoft_products[ $infusionsoft_product_id ] = $product['name'];
		update_option( 'wpf_infusionsoft_products', $infusionsoft_products );

		return $infusionsoft_product_id;

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

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error refunding order: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		// Convert date to GMT.
		$offset     = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$order_date = time() - $offset;
		$order_date = date( 'Ymd\TH:i:s', $order_date );

		$response = wp_fusion()->crm->app->manualPmt( intval( $transaction_id ), floatval( - $refund_amount ), $order_date, 'Refund', 'Refund', true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Now add a credit to set the balance to 0.

		$response = wp_fusion()->crm->app->manualPmt( intval( $transaction_id ), $refund_amount, $order_date, 'Credit', 'Credit for refund', true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
