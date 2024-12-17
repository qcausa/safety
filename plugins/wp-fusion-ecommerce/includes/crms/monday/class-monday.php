<?php

class WPF_EC_Monday {

	/**
	 * Lets other integrations know which features are supported by the CRM
	 */

	public $supports = array( 'refunds', 'products' );

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'validate_field_deals_enabled', array( $this, 'validate_deals_enabled' ) );
		add_filter( 'validate_field_deep_data_reset', array( $this, 'validate_deep_data_reset' ) );

		add_action( 'wpf_sync', array( $this, 'sync' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_activecampaign_customers_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_activecampaign_customers', array( $this, 'batch_step' ) );

		// Sync data on first run
		$pipelines_stages = get_option( 'wpf_available_lists', array() );
		BugFu::log($pipelines_stages);

		if ( $pipelines_stages != null && ! is_array( $pipelines_stages ) ) {
			$this->sync();
		}

	}

	/**
	 * Compatibility checks
	 *
	 * @access public
	 * @return array Notices
	 */

	public function compatibility_notices( $notices ) {

		if ( is_plugin_active( 'activecampaign-for-woocommerce/activecampaign-for-woocommerce.php' ) ) {

			$notices['ac-woo-plugin'] = 'The <strong>ActiveCampaign for WooCommerce</strong> plugin is active. You may get duplicate orders in ActiveCampaign if you\'re using WP Fusion\'s Enhanced Ecommerce addon and ActiveCampaign for WooCommerce at the same time.';

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
		BugFu::log($options);

		if ( ! isset( $options['deals_enabled'] ) ) {
			$options['deals_enabled'] = false;
		}

		$settings['ecommerce_header'] = array(
			'title'   => __( 'Monday Deep Data Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$desc = '';

		if ( wpf_get_option( 'deep_data_enabled' ) ) {

			$connection_id = wp_fusion()->crm->get_connection_id();

			if ( ! empty( $connection_id ) ) {

				$desc = '<span class="label label-success">' . sprintf( __( 'Connected with ID %s', 'wp-fusion' ), $connection_id ) . '</span>';

			} elseif ( false == $connection_id ) {

				$desc = '<span class="label label-danger">Upgrade your ActiveCampaign account to enable Deep Data</span>';

			}
		} else {

			// Don't delete the connection when the settings are being set.

			$desc = '<span class="label label-default">' . __( 'Disconnected', 'wp-fusion' ) . '</span>';

		}

		$settings['deep_data_enabled'] = array(
			'title'   => __( 'Deep Data', 'wp-fusion' ),
			'desc'    => __( 'Use WP Fusion\'s Deep Data integration with Monday for ecommerce data.', 'wp-fusion' ) . ' ' . $desc,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'lock'    => array( 'total_revenue_field' ),
			'tooltip' => __( 'Note that by enabling Deep Data, Monday will begin applying a tag <strong>WP Fusion-customer</strong> to new customers. It\'s not possible to turn this off, but if you don\'t want the extra tag it can be removed via an automation.', 'wp-fusion' ),
		);

		$settings['deals_header'] = array(
			'title'   => __( 'Monday Deals', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		// if ( ! empty( $options['available_lists'] ) ) {

			$settings['deals_enabled'] = array(
				'title'   => __( 'Deals', 'wp-fusion' ),
				'desc'    => __( 'Add individual sales as Orders in Monday.', 'wp-fusion' ),
				'type'    => 'checkbox',
				'section' => 'ecommerce',
				'unlock'  => array( 'deals_pipeline_stage', 'deals_owner' ),
			);

			$settings['deals_pipeline_stage'] = array(
				'title'       => __( 'Pipeline / Stage', 'wp-fusion' ),
				'type'        => 'select',
				'section'     => 'ecommerce',
				'placeholder' => 'Select a Pipeline / Stage',
				'choices'     => get_option( 'wpf_available_lists', array() ),
				'disabled'    => ( $options['deals_enabled'] == 0 ? true : false ),
			);

			if ( ! isset( $options['ac_owners'] ) ) {
				$options['ac_owners'] = array();
			}

			$settings['deals_owner'] = array(
				'title'       => __( 'Default Owner', 'wp-fusion' ),
				'type'        => 'select',
				'section'     => 'ecommerce',
				'placeholder' => 'Select an owner',
				'choices'     => $options['ac_owners'],
				'disabled'    => ( $options['deals_enabled'] == 0 ? true : false ),
				'desc'        => __( 'Select a default owner for deals.' ),
			);

		// } else {

		// 	$settings['deals_header']['desc'] = __( 'No pipelines or stages were detected in Monday, so deal creation is disabled. To create deals with WP Fusion, first create a pipeline in Monday and then click Resynchronize Available Tags & Fields from the Setup tab to load them.', 'wp-fusion' );

		// }

		if ( wpf_get_option( 'deep_data_enabled' ) ) {

			$reset = array(
				'deep_data_reset' => array(
					'title'   => __( 'Reset Deep Data', 'wp-fusion' ),
					'desc'    => sprintf( __( 'Disable and delete Deep Data connection ID %d.', 'wp-fusion' ), $connection_id ),
					'type'    => 'checkbox',
					'section' => 'advanced',
					'tooltip' => __( 'Note that by changing the Deep Data connection ID, you will lose the ability to update orders created with the old ID.', 'wp-fusion' ),
				),
			);

			$settings = wp_fusion()->settings->insert_setting_before( 'reset_options', $settings, $reset );

		}

		return $settings;

	}

	/**
	 * Validate deals/pipelines/stages to make sure it's not empty
	 *
	 * @access public
	 * @return string Input
	 */

	public function validate_deals_enabled( $input ) {

		if ( $input && empty( $_POST['wpf_options']['deals_pipeline_stage'] ) ) {
			return new WP_Error( 'error', 'You must specify an initial Pipeline and Stage to send Deals to Monday' );
		} else {
			return $input;
		}

	}

	/**
	 * Reset the Deep Data connection.
	 *
	 * @since 1.19.3
	 *
	 * @param bool $input The input.
	 */
	public function validate_deep_data_reset( $input ) {

		if ( $input ) {

			wp_fusion()->crm->delete_connection( get_option( 'wpf_ac_connection_id' ) );
			add_filter( 'validate_field_deep_data_enabled', '__return_false' );

		}

		return false;

	}


	/**
	 * Syncs deals and pipelines on plugin install or when Resynchronize is clicked
	 *
	 * @since 1.0
	 * @return void
	 */

	public function sync() {

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error initializing sync: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		$url = wpf_get_option( 'ac_url' );
		$key = wpf_get_option( 'ac_key' );

		$pipelines = array();

		$continue = true;
		$page     = 1;

		while ( $continue ) {

			$count = 0;

			$request = $url . '/admin/api.php?api_action=deal_pipeline_list&api_output=json&api_key=' . $key . '&page=' . $page;

			$response = wp_remote_get( $request );
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! empty( $response ) ) {

				foreach ( $response as $result ) {

					if ( is_object( $result ) ) {

						$pipelines[ $result->id ] = $result->title;

						$count++;

					}
				}
			}

			if ( $count < 20 ) {
				$continue = false;
			} else {
				$page++;
			}
		}

		$choices = array();

		$continue = true;
		$page     = 1;

		while ( $continue ) {

			$count = 0;

			$request = $url . '/admin/api.php?api_action=deal_stage_list&api_output=json&api_key=' . $key . '&page=' . $page;

			$response = wp_remote_get( $request );
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! empty( $response ) ) {

				foreach ( $response as $result ) {

					if ( is_object( $result ) ) {

						$choices[ $result->pipeline . ',' . $result->id ] = $pipelines[ $result->pipeline ] . ' &raquo; ' . $result->title;

						$count++;

					}
				}
			}

			if ( $count < 20 ) {
				$continue = false;
			} else {
				$page++;
			}
		}

		wp_fusion()->settings->set( 'ac_pipelines_stages', $choices );

		// Get deal owners
		$owners = array();

		$results = wp_fusion()->crm->app->api( 'user/list?ids=all' );

		foreach ( $results as $result ) {

			if ( is_object( $result ) && isset( $result->id ) ) {

				$owners[ $result->id ] = $result->first_name . ' ' . $result->last_name;

			}
		}

		wp_fusion()->settings->set( 'ac_owners', $owners );

	}


	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		// Convert date to GMT
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		try {
			$order_date = new DateTime( date( 'c', $order_date ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'error', 'Error adding order: ' . $e->getMessage() );
		}

		// DateTimeZone throws an error with 0 as the timezone
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		$order_args['order_date'] = $order_date;

		if ( ! wpf_get_option( 'deep_data_enabled' ) && ! wpf_get_option( 'deep_data_enabled' ) ) {
			return new WP_Error( 'notice', 'Neither Deep Data nor Deals are enabled on the Enhanced Ecommerce settings tab, so no data was synced to Monday.' );
		}

		// Sync deals
		if ( wpf_get_option( 'deals_enabled' ) ) {
			$result = $this->add_deal( $order_id, $contact_id, $order_args );
		}

		// Sync Deep Data
		if ( wpf_get_option( 'deep_data_enabled' ) ) {
			$result = $this->add_deep_data( $order_id, $contact_id, $order_args );
		}

		return $result;

	}


	/**
	 * Sync deal
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_deal( $order_id, $contact_id, $order_args ) {

		$pipeline_stage = wpf_get_option( 'deals_pipeline_stage' );

		if ( empty( $pipeline_stage ) ) {
			return;
		}

		$pipeline_stage = explode( ',', $pipeline_stage );

		$pipeline = $pipeline_stage[0];
		$stage    = $pipeline_stage[1];

		$data = array(
			'title'     => $order_args['order_label'],
			'value'     => $order_args['total'],
			'currency'  => strtolower( $order_args['currency'] ),
			'pipeline'  => $pipeline,
			'stage'     => $stage,
			'contactid' => $contact_id,
		);

		$owner = wpf_get_option( 'deals_owner' );

		if ( ! empty( $owner ) ) {
			$data['owner'] = $owner;
		}

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error adding deal: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		/**
		 * Filters the deal data.
		 *
		 * @since 1.0.0
		 *
		 * @link https://wpfusion.com/documentation/ecommerce-tracking/activecampaign-ecommerce/#deal-filter
		 *
		 * @param array $data     The deal.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_activecampaign_add_deal', $data, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Syncing Deal for <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$result = wp_fusion()->crm->app->api( 'deal/add', $data );

		if ( true != $result->success ) {
			return new WP_Error( 'error', $result->message );
		}

		// Add note

		$note = 'Product(s) purchased:' . PHP_EOL;

		foreach ( $order_args['products'] as $product ) {
			$note .= $product['name'] . ' - ' . $order_args['currency'] . ' ' . number_format( $product['price'], 2, '.', '' ) . ( $product['qty'] > 1 ? ' (x' . $product['qty'] . ')' : '' ) . PHP_EOL;
		}

		$note .= PHP_EOL . $order_args['order_edit_link'];

		/**
		 * Filter the deal note.
		 *
		 * @since 1.17.9
		 *
		 * @link  https://wpfusion.com/documentation/ecommerce-tracking/activecampaign-ecommerce/#deal-note-filter
		 *
		 * @param string $note     The note content.
		 * @param int    $order_id ID of the order.
		 */

		$note = apply_filters( 'wpf_ecommerce_activecampaign_add_deal_note', $note, $order_id );

		$note_result = wp_fusion()->crm->app->api(
			'deal/note_add',
			array(
				'dealid' => $result->id,
				'note'   => $note,
				'owner'  => $result->owner,
			)
		);

		update_post_meta( $order_id, 'wpf_ac_deal', $result->id );

		do_action( 'wpf_ecommerce_activecampaign_deal_added', $result->id, $data );

		return $result->id;

	}

	/**
	 * Searches for a deep data order by "externalid", i.e. the WooCommerce order ID
	 *
	 * @access  public
	 * @return  mixed Order ID or false
	 */

	public function get_order_id( $order_id ) {

		$params   = wp_fusion()->crm->get_params();
		$response = wp_remote_get( wp_fusion()->crm->api_url . 'api/3/ecomOrders/?filters[externalid]=' . $order_id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $response->ecomOrders ) ) { // @phpcs:ignore
			return false;
		}

		return $response->ecomOrders[0]->id; // @phpcs:ignore

	}

	/**
	 * Syncs Deep Data
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_deep_data( $order_id, $contact_id, $order_args ) {

		$connection_id = wp_fusion()->crm->get_connection_id();
		$customer_id   = wp_fusion()->crm->get_customer_id( $contact_id, $connection_id );

		if ( false == $customer_id ) {

			wpf_log( 'info', $order_args['user_id'], 'Unable to sync order <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>, couldn\'t find or create Customer in ActiveCampaign.', array( 'source' => 'wpf-ecommerce' ) );
			return false;

		}

		$product_objects = array();

		foreach ( $order_args['products'] as $product ) {

			$product_data = array(
				'externalid' => $product['id'],
				'name'       => $product['name'],
				'price'      => floatval( $product['price'] ) * 100,
				'quantity'   => $product['qty'],
				'productUrl' => get_permalink( $product['id'] ),
			);

			if ( ! empty( $product['image'] ) ) {
				$product_data['imageUrl'] = $product['image'];
			}

			if ( ! empty( $product['sku'] ) ) {
				$product_data['sku'] = $product['sku'];
			}

			if ( ! empty( $product['description'] ) ) {
				$product_data['description'] = $product['description'];
			}

			$product_objects[] = (object) $product_data;

		}

		// Line items with prices
		foreach ( $order_args['line_items'] as $line_item ) {

			// Only sync addons (if enabled). Taxes / shipping / discounts have their own key in the order payload
			if ( $line_item['type'] == 'addon' ) {

				$product_data = array(
					'externalid' => $line_item['id'],
					'name'       => $line_item['title'],
					'price'      => $line_item['price'] * 100,
					'quantity'   => 1,
				);

				$product_objects[] = (object) $product_data;

			}
		}

		$body = array(
			'ecomOrder' => array(
				'externalid'    => strval( $order_id ),
				'source'        => '1',
				'email'         => $order_args['user_email'],
				'orderNumber'   => $order_args['order_number'],
				'orderProducts' => $product_objects,
				'orderUrl'      => $order_args['order_edit_link'],
				'orderDate'     => $order_args['order_date']->format( 'c' ),
				'totalPrice'    => ( $order_args['total'] * 100 ),
				'currency'      => strtoupper( $order_args['currency'] ),
				'connectionid'  => $connection_id,
				'customerid'    => $customer_id,
			),
		);

		// Get shipping and discounts

		foreach ( $order_args['line_items'] as $line_item ) {

			if ( isset( $line_item['type'] ) && $line_item['type'] == 'shipping' ) {

				$body['ecomOrder']['shippingMethod'] = $line_item['description'];
				$body['ecomOrder']['shippingAmount'] = ( $line_item['price'] * 100 );

			} elseif ( isset( $line_item['type'] ) && $line_item['type'] == 'discount' ) {

				$body['ecomOrder']['discountAmount'] = abs( $line_item['price'] * 100 );

				if ( ! isset( $body['ecomOrder']['orderDiscounts'] ) ) {
					$body['ecomOrder']['orderDiscounts'] = array();
				}

				$body['ecomOrder']['orderDiscounts'][] = array(
					'name'           => ( $line_item['code'] ? $line_item['code'] : 'unknown' ),
					'type'           => 'order',
					'discountAmount' => abs( $line_item['price'] ) * 100,
				);

			} elseif ( isset( $line_item['type'] ) && $line_item['type'] == 'tax' ) {

				$body['ecomOrder']['taxAmount'] = ( $line_item['price'] * 100 );

			}
		}

		/**
		 * Filters the Deep Data data.
		 *
		 * @since 1.17.8
		 *
		 * @link https://wpfusion.com/documentation/ecommerce-tracking/activecampaign-ecommerce/#deep-data-filter
		 *
		 * @param array $body     The Deep Data body.
		 * @param int   $order_id ID of the order.
		 */

		$body = apply_filters( 'wpf_ecommerce_activecampaign_add_deep_data', $body, $order_id );

		// Logging.

		wpf_log(
			'info',
			$order_args['user_id'],
			'Syncing Deep Data for <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $body,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $body );

		if ( function_exists( 'wp_fusion_abandoned_cart' ) && isset( wp_fusion_abandoned_cart()->carts ) ) {
			$cart = wp_fusion_abandoned_cart()->carts->get_cart( $contact_id ); // v1.7+
		} else {
			$cart = get_transient( 'wpf_abandoned_cart_' . $contact_id );
		}

		if ( ! empty( $cart ) && ! empty( $cart['order_id'] ) ) {

			// Maybe update an existing order that was sent as a cart
			$params['method'] = 'PUT';

			$response = wp_remote_request( wp_fusion()->crm->api_url . 'api/3/ecomOrders/' . $cart['order_id'], $params );

			delete_transient( 'wpf_abandoned_cart_' . $contact_id );

		} else {

			// Log a completely new order
			$response = wp_remote_post( wp_fusion()->crm->api_url . 'api/3/ecomOrders', $params );

		}

		if ( is_wp_error( $response ) && 'related_missing' === $response->get_error_code() && ! empty( $order_args['user_id'] ) ) {

			// Maybe try to clear out the ecom customer ID and try again if it's a registered user

			$customer_id = get_user_meta( $order_args['user_id'], 'wpf_ac_customer_id', true );

			if ( ! empty( $customer_id ) ) {

				wpf_log(
					'notice',
					$order_args['user_id'],
					'Error adding order, "The related ecomCustomer does not exist", for ecomCustomer #' . $customer_id . '. Clearing cached customer ID and trying again...',
					array(
						'source' => 'wpf-ecommerce',
					)
				);

				delete_user_meta( $order_args['user_id'], 'wpf_ac_customer_id' );

				$result = $this->add_deep_data( $order_id, $contact_id, $order_args );

				return $result;

			} else {

				return $response;

			}
		} elseif ( is_wp_error( $response ) && 'duplicate' === $response->get_error_code() ) {

			// There's an existing order, let's try and update it instead

			wpf_log(
				'notice',
				$order_args['user_id'],
				'Error adding order, "The ecomOrder already exists in the system", proceeding to try to update the existing order...',
				array(
					'source' => 'wpf-ecommerce',
				)
			);

			$ecom_order_id = $this->get_order_id( $order_id );

			if ( is_wp_error( $ecom_order_id ) ) {

				// Error doing the search

				wpf_log(
					'error',
					$order_args['user_id'],
					'Error searching for existing ecommerce order: ' . $ecom_order_id->get_error_message(),
					array(
						'source' => 'wpf-ecommerce',
					)
				);

				return $ecom_order_id;

			} elseif ( false === $ecom_order_id ) {

				// Couldn't find the order

				wpf_log(
					'error',
					$order_args['user_id'],
					'Unable to find an existing order ID to update with externalid ' . $order_id,
					array(
						'source' => 'wpf-ecommerce',
					)
				);

				return $response;

			} else {

				// Success, let's update it

				wpf_log(
					'info',
					$order_args['user_id'],
					'Updating existing Deep Data order with ID #' . $ecom_order_id,
					array(
						'source' => 'wpf-ecommerce',
					)
				);

				$params['method'] = 'PUT';
				$response         = wp_remote_request( wp_fusion()->crm->api_url . 'api/3/ecomOrders/' . $ecom_order_id, $params );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				return $ecom_order_id;

			}
		} elseif ( is_wp_error( $response ) && 'not_found' === $response->get_error_code() ) {

			// This can happen if the customer checks out with a new email
			// address that doesn't match their customer record. AC adds and
			// updates the order but still returns an error, so we need to
			// search it to get the order ID.

			$contact = wp_fusion()->crm->load_contact( $contact_id );

			if ( is_wp_error( $contact ) ) {

				// If the contact isn't found, or other error.
				return $contact;
			}

			if ( $body['ecomOrder']['email'] !== $contact['user_email'] ) {

				wpf_log(
					'notice',
					$order_args['user_id'],
					'Error adding order, "No Result found for Subscriber with id 1" adding order to email <strong>' . $body['ecomOrder']['email'] . '</strong>. This can sometimes happen when someone checks out with a different email address from their contact record. Going to resend using email <strong>' . $contact['user_email'] . '</strong>.',
					array(
						'source' => 'wpf-ecommerce',
					)
				);

				$order_args['user_email'] = $contact['user_email'];

				return $this->add_deep_data( $order_id, $contact_id, $order_args );

			} else {
				return $response;
			}
		} elseif ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $body ) && isset( $body['ecomOrder']['id'] ) ) {

			do_action( 'wpf_ecommerce_activecampaign_order_added', $body['ecomOrder']['id'], $body );

			return $body['ecomOrder']['id'];

		} else {

			return new WP_Error( 'error', wp_remote_retrieve_body( $response ) );

		}

	}


	/**
	 * Refund an order.
	 *
	 * @since 1.19.0
	 *
	 * @param int    $transaction_id The transaction ID.
	 * @param int    $final_amount   The final order amount.
	 * @param int    $refund_amount  The refund amount.
	 * @param array  $order_args     The original order args.
	 * @param string $contact_id     The contact ID in the CRM.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function refund_order( $transaction_id, $final_amount, $refund_amount, $order_args, $contact_id ) {

		$params = wp_fusion()->crm->get_params();

		$body = array(
			'ecomOrder' => array(
				'totalPrice' => $final_amount * 100,
			),
		);

		$params['method'] = 'PUT';
		$params['body']   = wp_json_encode( $body );
		$response         = wp_remote_request( wp_fusion()->crm->api_url . 'api/3/ecomOrders/' . $transaction_id, $params );
		$body             = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_object( $body ) && isset( $body->errors ) ) {
			return new WP_Error( 'error', $body->errors[0]->title );
		}

		return true;

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds option to batch tools list
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['activecampaign_customers'] = array(
			'label'   => __( 'ActiveCampaign Deep Data customer IDs', 'wp-fusion' ),
			'title'   => __( 'Customers', 'wp-fusion' ),
			'tooltip' => __( 'Look up the ActiveCampaign customer IDs for each user based on email address.', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Counts total number of users to be processed
	 *
	 * @access public
	 * @return int Count
	 */

	public function batch_init() {

		$connection_id = get_option( 'wpf_ac_connection_id' );

		if ( empty( $connection_id ) ) {
			return array();
		}

		$args = array( 'fields' => 'ID' );

		$users = get_users( $args );

		wpf_log( 'info', 0, 'Beginning <strong>ActiveCampaign Deep Data customers</strong> batch operation on ' . count( $users ) . ' users', array( 'source' => 'batch-process' ) );

		return $users;

	}

	/**
	 * Processes users in steps
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_id ) {

		$customer_id = false;

		$api_url       = wpf_get_option( 'ac_url' );
		$connection_id = get_option( 'wpf_ac_connection_id' );

		$user = get_userdata( $user_id );

		$response = wp_remote_get( $api_url . 'api/3/ecomCustomers?filters[email]=' . $user->user_email . '&filters[connectionid]=' . $connection_id, wp_fusion()->crm->get_params() );

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body->ecomCustomers ) ) {

			$customer_id = $body->ecomCustomers[0]->id;
			wpf_log( 'info', $user_id, 'Loaded customer ID ' . $customer_id . ' for email ' . $user->user_email . ' on connection ID #' . $connection_id . '.' );

		} else {

			wpf_log( 'info', $user_id, 'No customer ID found for ' . $user->user_email . ' on connection ID #' . $connection_id . '.' );

		}

		if ( false !== $customer_id ) {
			update_user_meta( $user_id, 'wpf_ac_customer_id', $customer_id );
		}

	}

}
