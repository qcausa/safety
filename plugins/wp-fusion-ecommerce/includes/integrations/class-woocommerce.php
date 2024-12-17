<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Woocommerce extends WPF_EC_Integrations_Base {

	/**
	 * The integration slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $slug = 'woocommerce';

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 25, 2 );
		add_filter( 'wpf_initialize_options', array( $this, 'initialize_options' ) );
		add_filter( 'wpf_woocommerce_order_statuses_for_payment_complete', array( $this, 'order_statuses_for_payment_complete' ) );

		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'send_order_data' ), 10, 2 );
		add_action( 'woocommerce_new_order', array( $this, 'new_order' ), 15 ); // 15 so it runs after the core plugin.
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'after_calculate_totals' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'partially_refunded' ), 10, 2 );

		// Prevent timeouts when bulk editing.
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 5, 3 );

		// Product panels.
		add_action( 'wpf_woocommerce_panel', array( $this, 'product_panel' ), 20 );
		add_action( 'save_post_product', array( $this, 'save_meta_box_data' ) );

		// Variable product panels.
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'product_variation_panel' ), 16, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_fields' ), 10, 2 );

		// Super secret admin / debugging tools.
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );

		// Add/Update products to CRMs.
		add_action( 'woocommerce_new_product', array( $this, 'add_update_product' ) );
		add_action( 'woocommerce_update_product', array( $this, 'add_update_product' ) );

		// Export functions.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_woocommerce_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woocommerce_ecom', array( $this, 'batch_step' ) );

		// Batch Add/Update Products.
		add_filter( 'wpf_batch_woocommerce_ecom_products_init', array( $this, 'batch_init_products' ) );
		add_action( 'wpf_batch_woocommerce_ecom_products', array( $this, 'batch_step_products' ) );

		// Compatability Notices.
		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );
	}


	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['ecommerce'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'ecommerce' => __( 'Enhanced Ecommerce', 'wp-fusion' ) ) );
		}

		return $page;

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ec_woo_header'] = array(
			'title'   => __( 'Misc. Ecommerce Settings', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['ec_woo_attributes'] = array(
			'title'   => __( 'Sync Attributes', 'wp-fusion' ),
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'desc'    => __( 'Sync selected product attributes and meta data as separate line items.', 'wp-fusion' ),
			'tooltip' => __( 'This is useful when using addon plugins for WooCommerce that attach metadata to ordered products, for example WooCommerce Product Addons or FooEvents', 'wp-fusion' ),
		);

		if ( isset( wp_fusion_ecommerce()->crm->supports ) ) {

			if ( in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {

				$settings['ec_woo_edits'] = array(
					'title'   => __( 'Sync Product Edits', 'wp-fusion' ),
					'type'    => 'checkbox',
					'section' => 'ecommerce',
					'desc'    => sprintf( __( 'Sync WooCommerce products to %s when a product is created or updated (instead of at checkout).', 'wp-fusion' ), wp_fusion()->crm->name ),
				);

			}

			if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

				$settings['ec_woo_header_stages'] = array(
					'title'   => __( 'WooCommerce Order Status Stages', 'wp-fusion' ),
					'url'     => 'https://wpfusion.com/documentation/ecommerce-tracking/' . wp_fusion()->crm->slug . '-ecommerce/#woocommerce-order-statuses',
					'type'    => 'heading',
					'section' => 'ecommerce',
					'desc'    => sprintf( __( 'For each order status in WooCommerce, select a corresponding pipeline and stage in %s. When the order status is updated the deal\'s stage will also be changed.', 'wp-fusion' ), wp_fusion()->crm->name ),
				);

				if ( ! isset( $options[ wp_fusion()->crm->slug . '_pipelines' ] ) ) {
					$options[ wp_fusion()->crm->slug . '_pipelines' ] = array();
				}

				$statuses = wc_get_order_statuses();

				foreach ( $statuses as $key => $label ) {

					$settings[ 'ec_woo_status_' . $key ] = array(
						'title'       => $label,
						'type'        => 'select',
						'section'     => 'ecommerce',
						'placeholder' => 'Select a Pipeline / Stage',
						'choices'     => $options[ wp_fusion()->crm->slug . '_pipelines' ],
					);

					if ( 'wc-pending' === $key ) {
						$settings[ 'ec_woo_status_' . $key ]['desc']    = __( '<strong>Caution:</strong> it is recommended not to enable this setting. For more info, see the tooltip.', 'wp-fusion' );
						$settings[ 'ec_woo_status_' . $key ]['tooltip'] = sprintf( __( 'Syncing pending orders with %1$s will slow down your checkout because the order needs to be created and then immediately updated once the payment is received. It is recommended not to sync Pending orders with %2$s unless you have a strong reason to do so and understand the risks.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name );
					}
				}
			}
		}

		return $settings;

	}

	/**
	 * Set pipeline stages for Processing and Completed to default, if empty.
	 *
	 * @since  1.18.0
	 *
	 * @param  array $options The options.
	 * @return array The options.
	 */
	public function initialize_options( $options ) {

		if ( ! empty( $options[ wp_fusion()->crm->slug . '_pipeline_stage' ] ) ) {

			if ( empty( $options['ec_woo_status_wc-processing'] ) ) {
				$options['ec_woo_status_wc-processing'] = $options[ wp_fusion()->crm->slug . '_pipeline_stage' ];
			}

			if ( empty( $options['ec_woo_status_wc-completed'] ) ) {
				$options['ec_woo_status_wc-completed'] = $options[ wp_fusion()->crm->slug . '_pipeline_stage' ];
			}
		}

		return $options;

	}


	/**
	 * The core plugin won't trigger Enhanced Ecommerce (via
	 * wpf_woocommerce_payment_complete) if the order is unpaid, but that means
	 * that if we've set a deal stage for unpaid orders, they won't be synced.
	 *
	 * This ensures that any order statuses that have custom pipelines are
	 * processed by the core plugin.
	 *
	 * @since  1.18.6
	 *
	 * @param  array $statuses The statuses.
	 * @return array The statuses.
	 */
	public function order_statuses_for_payment_complete( $statuses ) {

		$all_statuses = wc_get_order_statuses();

		foreach ( $all_statuses as $key => $label ) {

			if ( ! empty( wpf_get_option( "ec_woo_status_{$key}" ) ) ) {
				$statuses[] = str_replace( 'wc-', '', $key );
			}
		}

		$statuses = array_unique( $statuses );

		return $statuses;

	}

	/**
	 * Shows configured CRM product corresponding to Woo product (simple products)
	 *
	 * @access  public
	 * @return  mixed
	 */

	public function product_panel() {

		if ( ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		global $post;

		$product_id         = get_post_meta( $post->ID, wp_fusion()->crm->slug . '_product_id', true );
		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<p class="form-field hide_if_variable"><label><strong>Enhanced Ecommerce</strong></label></p>';

		echo '<p class="form-field hide_if_variable"><label for="wpf-ec-product">' . wp_fusion()->crm->name . ' Product</label>';

		echo '<select id="wpf-ec-product" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id">';

			echo '<option></option>';

			echo '<option>' . __( 'None', 'wp-fusion' ) . '</option>';

		foreach ( $available_products as $id => $name ) {

			echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . ' (#' . $id . ')</option>';
		}

		echo '</select>';

		echo '</p>';

		echo '<p class="form-field show_if_variable"><label></label>' . wp_fusion()->crm->name . ' product assignment for variations can be configured within the Variations tab.</p>';

	}

	/**
	 * Adds product select to variable fields
	 *
	 * @access public
	 * @return mixed
	 */

	public function product_variation_panel( $loop, $variation_data, $variation ) {

		if ( ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$product_id         = get_post_meta( $variation->ID, wp_fusion()->crm->slug . '_product_id', true );
		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<div><p class="form-row form-row-full">';

			echo '<label for="wpf-ec-product-variation-' . $variation->ID . '">' . wp_fusion()->crm->name . ' product:</label>';

			echo '<select id="wpf-ec-product-variation-' . $variation->ID . '" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id[' . $variation->ID . ']">';

				echo '<option></option>';

		foreach ( $available_products as $id => $name ) {
			echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . '</option>';
		}

			echo '</select>';

		echo '</p></div>';

	}

	/**
	 * Saves variable field data to product
	 *
	 * @access public
	 * @return void
	 */


	public function save_variable_fields( $variation_id, $i ) {

		if ( isset( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {

			update_post_meta( $variation_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ][ $variation_id ] );

		}

	}

	/**
	 * Saves CRM product ID selected in dropdown
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_woo_nonce'] ) || ! isset( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_nonce'], 'wpf_meta_box_woo' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] == 'revision' ) {
			return;
		}

		update_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ] );

	}


	/**
	 * Gets product data from the product and order item.
	 *
	 * @since 1.20.0
	 *
	 * @param int           $product_id The product ID.
	 * @param WC_Order_Item $item The order item.
	 * @return array The product data.
	 */
	public function get_product_data( $product_id, $item = false ) {

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		if ( $item ) {
			$name = $item->get_name(); // handles variations.
		} else {
			$name = $product->get_name();
		}

		$name = str_replace( ' &ndash;', ': ', $name );
		$name = wp_strip_all_tags( $name );

		$product_data = array(
			'id'             => $product_id,
			'name'           => $name,
			'crm_product_id' => get_post_meta( $product_id, wp_fusion()->crm->slug . '_product_id', true ),
			'sku'            => get_post_meta( $product_id, '_sku', true ),
			'image'          => get_the_post_thumbnail_url( $product_id, 'medium' ),
			'price'          => floatval( $product->get_price( false ) ),
			'stock_quantity' => $product->get_stock_quantity( false ),
			'categories'     => array(),
		);

		// Add categories.
		$categories = get_the_terms( $product_id, 'product_cat' );

		if ( ! empty( $categories ) ) {
			$product_data['categories'] = wp_list_pluck( $categories, 'name' );
		}

		// Treat variations as separate products.

		if ( $item && $item->get_variation_id() ) {

			$product_data['id']             = $item->get_variation_id();
			$product_data['parent_id']      = $product_id;
			$product_data['sku']            = get_post_meta( $item->get_variation_id(), '_sku', true );
			$product_data['crm_product_id'] = get_post_meta( $item->get_variation_id(), wp_fusion()->crm->slug . '_product_id', true );

			$image = get_the_post_thumbnail_url( $item->get_variation_id(), 'medium' );

			if ( ! empty( $image ) ) {
				$product_data['image'] = $image;
			}
		}

		// Make sure CRM product ID still exists.
		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		if ( ! empty( $product_data['crm_product_id'] ) && ! isset( $available_products[ $product_data['crm_product_id'] ] ) ) {
			$product_data['crm_product_id'] = false;
		}

		// If it's not set try and find a match.
		if ( empty( $product_data['crm_product_id'] ) && is_array( $available_products ) ) {
			$product_data['crm_product_id'] = array_search( $product_data['name'], $available_products );
		}

		return $product_data;
	}

	/**
	 * Gets order args for creating / updating orders
	 *
	 * @access  public
	 * @return  array Order Args
	 */

	public function get_order_args( $order ) {

		// Build array of products / subscriptions.
		$products = array();

		// Array of line items.
		$line_items = array();

		foreach ( $order->get_items() as $item_id => $item ) {

			$product_id = $item->get_product_id();

			// Deal with deleted products.
			if ( empty( $product_id ) ) {
				$product_id = $item_id;
			}

			$product_data = $this->get_product_data( $product_id, $item );

			if ( empty( $product_data ) ) {
				continue; // deleted products.
			}

			$product_data['qty']   = $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id );
			$product_data['tax']   = $item->get_total_tax();
			$product_data['price'] = $order->get_item_subtotal( $item, false, true ); // fixes issue with variable prices not coming through.

			// Adjustments added to an order item on a manually created order need to be recorded as line items.
			if ( round( $item->get_subtotal(), 2 ) !== round( $item->get_total(), 2 ) && empty( $order->get_coupons() ) ) {

				$line_items[] = array(
					'type'        => 'discount',
					'price'       => - ( $item->get_subtotal() - $item->get_total() ),
					'title'       => 'Discount',
					'description' => 'Manual order adjustment',
					'code'        => false,
				);
			}

			$products[] = $product_data;

			if ( wpf_get_option( 'ec_woo_attributes' ) ) {

				// Add meta (for WC Addons).
				$item_meta = $item->get_meta_data();

				if ( ! empty( $item_meta ) ) {

					foreach ( $item_meta as $meta ) {

						if ( is_a( $meta, 'WC_Meta_Data' ) ) {

							$data = $meta->get_data();

							if ( empty( $data['id'] ) || empty( $data['key'] ) ) {
								continue;
							}

							// Ignore hidden fields
							if ( substr( $data['key'], 0, 1 ) === '_' || is_array( $data['key'] ) || is_array( $data['value'] ) ) {
								continue;
							}

							$data['key'] = str_replace( '&#36;', '$', $data['key'] );

							$product_data = array(
								'id'       => $data['id'],
								'title'    => $data['key'] . ': ' . $data['value'],
								'qty'      => 1,
								'subtotal' => 0,
								'sku'      => '',
								'type'     => 'addon',
								'price'    => 0,
							);

							$line_items[] = $product_data;

						}
					}
				}
			}
		}

		// Add line item for taxes
		if ( $order->get_total_tax() > 0.0 ) {
			$line_items[] = array(
				'type'        => 'tax',
				'price'       => $order->get_total_tax(),
				'title'       => 'Tax',
				'description' => 'Tax',
			);
		}

		// Add line item for shipping
		$shipping_total = $order->get_shipping_total();

		if ( $shipping_total > 0.0 ) {

			$shipping_methods = $order->get_items( 'shipping' );

			if ( ! empty( $shipping_methods ) ) {
				$shipping_title = array_values( $shipping_methods )[0]->get_method_title();
			} else {
				$shipping_title = __( 'Shipping', 'wp-fusion' );
			}

			$line_items[] = array(
				'type'        => 'shipping',
				'price'       => $shipping_total,
				'title'       => $shipping_title,
				'description' => $order->get_shipping_method(),
			);
		}

		// Add coupons.

		foreach ( $order->get_coupons() as $coupon ) {

			$line_items[] = array(
				'type'        => 'discount',
				'price'       => - $coupon->get_discount(),
				'title'       => 'Discount',
				'description' => 'Woocommerce Coupon Code ' . $coupon->get_code(),
				'code'        => $coupon->get_code(),
			);

		}

		// Get fees.

		foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {

			if ( empty( $item->get_total() ) ) {
				continue;
			}

			if ( $item->get_total() >= 0 ) {

				// Fees

				$line_items[] = array(
					'id'          => $item_id,
					'type'        => 'fee',
					'price'       => $item->get_total(),
					'title'       => ( $item->get_name() ? $item->get_name() : 'unknown' ),
					'description' => ( $item->get_name() ? $item->get_name() : 'unknown' ),
				);

			} elseif ( $item->get_total() < 0 ) {

				// Discounts

				$line_items[] = array(
					'id'          => $item_id,
					'type'        => 'discount',
					'price'       => $item->get_total(),
					'title'       => ( $item->get_name() ? $item->get_name() : 'unknown' ),
					'description' => ( $item->get_name() ? $item->get_name() : 'unknown' ),
				);

			}
		}

		// Date.
		$order_date = $order->get_date_completed();

		if ( empty( $order_date ) ) {
			$order_date = $order->get_date_paid();
		}

		if ( ! empty( $order_date ) ) {
			$order_date = $order_date->date_i18n( 'U' );
		} else {
			$order_date = get_the_date( 'U', $order->get_id() ); // if the order isn't paid or completed, we'll get the date it was created.
		}

		$currency = $order->get_currency();
		$user_id  = $order->get_user_id();

		if ( ! empty( $user_id ) ) {
			$user  = get_userdata( $user_id );
			$email = $user->user_email;
		} else {
			$email = $order->get_billing_email();
		}

		// Getting the checkout url for Cartflows or WooFunnels.

		if ( $order instanceof WC_Order && $order->get_meta( '_wfacp_post_id' ) ) {
			$checkout_url = get_permalink( $order->get_meta( ( '_wfacp_post_id' ) ) );
		} elseif ( $order instanceof WC_Order && $order->get_meta( '_wcf_checkout_id' ) ) {
			$checkout_url = get_permalink( $order->get_meta( '_wcf_checkout_id' ) );
		} else {
			$checkout_url = wc_get_checkout_url();
		}

		$order_args = array(
			'order_label'     => 'WooCommerce Order #' . $order->get_order_number(),
			'order_number'    => $order->get_order_number(),
			'order_edit_link' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'payment_method'  => $order->get_payment_method_title(),
			'products'        => $products,
			'user_email'      => $email,
			'line_items'      => $line_items,
			'total'           => $order->get_total(),
			'currency'        => $currency,
			'currency_symbol' => get_woocommerce_currency_symbol( $currency ),
			'order_date'      => $order_date,
			'provider'        => 'woocommerce',
			'status'          => $order->get_status(),
			'user_id'         => $user_id,
			'invoice_id'      => $order->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id' ),
			'checkout_url'    => $checkout_url,
		);

		$order_args = apply_filters( 'wpf_ecommerce_order_args', $order_args, $order->get_id() );

		return $order_args;

	}

	/**
	 * Send an order to the CRM.
	 *
	 * @since unknown
	 *
	 * @param int|WC_Order $order      The order ID or order.
	 * @param string       $contact_id The contact ID.
	 * @return bool True if successful, false otherwise.
	 */
	public function send_order_data( $order, $contact_id = '' ) {

		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order );
		}

		$order_id = $order->get_id();

		if ( doing_action( 'wpf_batch_woocommerce' ) && $order->get_meta( 'wpf_ec_complete' ) ) {
			wpf_log( 'notice', $order->get_user_id(), 'This order was already processed by Enhanced Ecommerce on ' . $order->get_meta( 'wpf_ec_complete' ) . '. It won\'t be synced again. To force-sync the order to ' . wp_fusion()->crm->name . ', run the <strong>WooCommerce Orders (Ecommerce addon)</strong> operation.', array( 'source' => 'wpf-ecommerce' ) );
			return; // if the order was already synced by Enhanced Ecom, and we're running a WooCommerce orders operation, don't sync it again.
		}

		$order_args = $this->get_order_args( $order );

		if ( empty( $contact_id ) ) {

			// Get the contact ID, creating it if necessary.

			$contact_id = wp_fusion()->integrations->woocommerce->get_contact_id_from_order( $order );

			if ( false === $contact_id ) {

				$contact_id = wp_fusion()->integrations->woocommerce->create_update_customer( $order );

				if ( empty( $contact_id ) ) {
					wpf_log( 'error', $order->get_user_id(), 'Error adding WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: unable to create contact record.', array( 'source' => 'wpf-ecommerce' ) );
					return false;
				}
			}
		}

		// Add order.
		$result = wp_fusion_ecommerce()->crm->add_order( $order_id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', $order->get_user_id(), 'Error adding WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			$order->add_order_note( 'Error creating order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

			return false;
		}

		if ( true === $result ) {

			$order->add_order_note( wp_fusion()->crm->name . ' invoice successfully created.' );

		} elseif ( ! is_null( $result ) ) {

			$string = ! empty( $order_args['invoice_id'] ) ? 'updated' : 'created';

			// CRMs with invoice IDs
			$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $result . ' successfully ' . $string . '.' );
			$order->update_meta_data( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order.
		$order->update_meta_data( 'wpf_ec_complete', current_time( 'Y-m-d H:i:s' ) );
		$order->save();

		// Don't sync any additional status changes in this request.
		remove_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );

		do_action( 'wpf_ecommerce_complete', $order_id, $result, $contact_id, $order_args );

		return true;

	}

	/**
	 * Insert new order.
	 *
	 * An order status transition does not always happen when a new pending
	 * order is inserted, this ensures deals can be created for pending orders.
	 *
	 * @since 1.23.4
	 *
	 * @param int $order_id The order ID.
	 */
	public function new_order( $order_id ) {

		if ( did_action( 'wpf_woocommerce_payment_complete' ) ) {
			return; // was already processed by the core plugin.
		}

		$order  = wc_get_order( $order_id );
		$status = $order->get_status();

		if ( wpf_get_option( 'ec_woo_status_wc-' . $status ) ) {

			// If a stage is set for pending / on-hold orders, sync it.
			$this->send_order_data( $order_id );

		}

	}

	/**
	 * Update an order.
	 *
	 * If an order is saved in the Pending status, and has an invoice, re-sync the order
	 * to the CRM (in case the line items were edited).
	 *
	 * @since 1.23.4
	 *
	 * @param bool     $and_taxes Whether the total contains taxes (?)
	 * @param WC_Order $order     The order.
	 */
	public function after_calculate_totals( $and_taxes, $order ) {

		// Don't do it twice.
		remove_action( 'woocommerce_order_after_calculate_totals', array( $this, 'after_calculate_totals' ) );

		if ( 'pending' === $order->get_status() && $order->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id' ) ) {

			// We have to pass the $order here because it contains the calculated totals.
			$this->send_order_data( $order );

		}

	}

	/**
	 * Handles changed order statuses
	 *
	 * @access  public
	 * @return  void
	 */

	public function order_status_changed( $order_id, $from_status, $to_status, $order ) {

		if ( empty( wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$invoice_id = $order->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id' );

		if ( empty( $invoice_id ) ) {
			return; // can't update an order without an invoice ID.
		}

		$user_id = $order->get_user_id();

		$order_args = $this->get_order_args( $order );

		if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

			// HubSpot, Zoho, Sendinblue.

			$stage = wpf_get_option( 'ec_woo_status_wc-' . $to_status );

			if ( ! empty( $stage ) ) {

				$result = wp_fusion_ecommerce()->crm->change_stage( $invoice_id, $stage, $order_id );

				if ( is_wp_error( $result ) ) {

					wpf_log( 'error', $user_id, 'Error changing status for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( 'Error changing status for order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

				} else {

					$stage_label = wp_fusion_ecommerce()->crm_base->get_stage_label( $stage );

					wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> stage was updated to ' . $stage_label . ' in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' deal stage updated to ' . $stage_label . '.' );

				}
			}
		}

		if ( in_array( 'status_changes', wp_fusion_ecommerce()->crm->supports ) ) {

			// Drip.

			$order_args['refund_amount'] = $order->get_total_refunded();

			if ( ! empty( $order_args['refund_amount'] ) ) {

				// Get the last refunded date to use as the order date. This is how Drip wants it.
				$refunds                  = $order->get_refunds();
				$order_args['order_date'] = $refunds[0]->get_date_created()->date_i18n( 'U' );

			}

			$contact_id = wp_fusion()->integrations->woocommerce->get_contact_id_from_order( $order );

			if ( empty( $contact_id ) ) {
				wpf_log( 'error', $user_id, 'Error updating order status in ' . wp_fusion()->crm->name . ' to ' . $to_status . ' for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: Unable to find contact ID for customer.', array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( 'Error updating order status in ' . wp_fusion()->crm->name . ': Unable to find contact ID for customer.' );
				return;
			}

			$result = wp_fusion_ecommerce()->crm->order_status_changed( $order_id, $contact_id, $to_status, $order_args );

			if ( is_wp_error( $result ) ) {

				wpf_log( 'error', $user_id, 'Error updating status in ' . wp_fusion()->crm->name . ' to ' . $to_status . ' for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( 'Error updating order status in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

			} elseif ( false !== $result ) {

				wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> status updated to ' . $to_status . ' in ' . wp_fusion()->crm->name . '.', array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' successfully marked as ' . $to_status . '.' );

			}
		} elseif ( ( 'cancelled' === $to_status || 'refunded' === $to_status ) && in_array( 'refunds', wp_fusion_ecommerce()->crm->supports ) ) {

			// ActiveCampaign, AgileCRM, HubSpot, Infusionsoft, NationBuilder, Ontraport, Zoho.

			// This is now an elseif so that with Drip, the order status isn't updated to refunded twice.

			$contact_id   = wp_fusion()->integrations->woocommerce->get_contact_id_from_order( $order );
			$final_amount = floatval( $order->get_total() ) - floatval( $order->get_total_refunded() );

			if ( 'refunded' === $to_status ) {

				// Get the most recent refund amount, filtering out $0 refunds.

				$refunds = $order->get_refunds();

				foreach ( $refunds as $i => $refund ) {

					if ( 0 === intval( $refund->get_amount() ) ) {
						unset( $refunds[ $i ] );
					}
				}

				if ( empty( $refunds ) ) {
					return; // nothing was actually refunded.
				}

				$refunds         = array_values( $refunds );
				$refunded_amount = round( $refunds[0]->get_amount(), 2 );

			} elseif ( 'cancelled' === $to_status ) {

				$final_amount    = 0;
				$refunded_amount = round( $order->get_total(), 2 );

			}

			$result = wp_fusion_ecommerce()->crm->refund_order( $invoice_id, $final_amount, $refunded_amount, $order_args, $contact_id );

			if ( is_wp_error( $result ) ) {

				wpf_log( 'error', $user_id, 'Error marking WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> as ' . $to_status . ': ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( 'Error refunding order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

			} else {

				wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> marked ' . $to_status . ' in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' successfully marked as refunded.' );

			}
		}

	}

	/**
	 * Handle partial refunds.
	 *
	 * @since 1.19.0
	 *
	 * @param int $order_id  The order ID.
	 * @param int $refund_id The refund ID.
	 */
	public function partially_refunded( $order_id, $refund_id ) {

		if ( empty( wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$user_id = $order->get_user_id();

		$order_args = $this->get_order_args( $order );

		$invoice_id = $order->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

		$refund = new WC_Order_Refund( $refund_id );

		if ( 0 === intval( $refund->get_amount() ) ) {
			return; // don't sync $0 refunds.
		}

		$contact_id = wp_fusion()->integrations->woocommerce->get_contact_id_from_order( $order );

		if ( ! empty( $invoice_id ) ) {

			$final_amount = floatval( $order->get_total() ) - floatval( $order->get_total_refunded() );
			$result       = wp_fusion_ecommerce()->crm->refund_order( $invoice_id, round( $final_amount, 2 ), round( $refund->get_amount(), 2 ), $order_args, $contact_id );

			if ( is_wp_error( $result ) ) {

				wpf_log( 'error', $user_id, 'Error partially refunding WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> : ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( 'Error partially refunding order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

			} else {

				wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> partially refunded in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );
				$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' successfully partially refunded.' );

			}
		}
	}

	/**
	 * Sync a product to the CRM on save.
	 *
	 * @since 1.20.0
	 * @since 1.23.0 Moved hooks from save_post to woocommerce_new_product and woocommerce_update_product.
	 *
	 * @param int $post_id The post ID.
	 */
	public function add_update_product( $post_id ) {

		if ( ! wpf_get_option( 'ec_woo_edits' ) && ! doing_action( 'wpf_batch_woocommerce_ecom_products' ) ) {
			return;
		}

		if ( 1 === did_action( 'woocommerce_update_product' ) ) {
			return; // don't do it twice.
		}

		$product_data = $this->get_product_data( $post_id );

		if ( empty( $product_data['crm_product_id'] ) ) {
			$product_data['crm_product_id'] = wp_fusion_ecommerce()->crm->get_product_id( $product_data ); // Check if the product already exists.
		}

		$product_id = wp_fusion_ecommerce()->crm->add_product( $product_data );

		if ( is_wp_error( $product_id ) ) {
			wpf_log(
				'error',
				false,
				'Error syncing product to ' . wp_fusion_ecommerce()->crm->name . ': ' . $product_id->get_error_message(),
				array(
					'source'              => 'wpf-ecommerce',
					'meta_array_nofilter' => $product_data,
				)
			);
		} else {

			// Add it to the global list.
			$products                = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );
			$products[ $product_id ] = $product_data['name'];
			update_option( 'wpf_' . wp_fusion()->crm->slug . '_products', $products, false );

			update_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id', $product_id );

		}

	}

	/**
	 * Handle bulk actions.
	 *
	 * Unhook the status change watch when bulk-editing more than 20 orders in
	 * the admin, to prevent a timeout.
	 *
	 * @since  1.18.1
	 *
	 * @param  string $redirect_to The redirect URL.
	 * @param  string $action      The action.
	 * @param  array  $ids         The order IDs.
	 * @return string The redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {

		if ( count( $ids ) > 20 ) {

			remove_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );

			if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

				$msg = 'To prevent a timeout, deal stages will not be updated in ' . wp_fusion()->crm->name;

			} elseif ( 'mark_cancelled' === $action && in_array( 'refunds', wp_fusion_ecommerce()->crm->supports ) ) {

				$msg = 'To prevent a timeout, orders will not be marked cancelled in ' . wp_fusion()->crm->name;

			} elseif ( in_array( 'status_changes', wp_fusion_ecommerce()->crm->supports ) ) {

				$msg = 'To prevent a timeout, status changes will not be synced to ' . wp_fusion()->crm->name;

			}

			if ( isset( $msg ) ) {
				wpf_log( 'notice', 0, 'Bulk order status change detected for ' . count( $ids ) . ' orders. ' . $msg, array( 'source' => 'wpf-ecommerce' ) );
			}
		}

		return $redirect_to;

	}

	/**
	 * Support utilities
	 *
	 * @access public
	 * @return void
	 */

	public function settings_page_init() {

		if ( isset( $_GET['woo_reset_wpf_ec_complete'] ) ) {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'shop_order',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'wpf_ec_complete',
						'compare' => 'EXISTS',
					),
				),
			);

			$orders = get_posts( $args );

			foreach ( $orders as $order_id ) {
				delete_post_meta( $order_id, 'wpf_ec_complete' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success: </strong><code>wpf_ec_complete</code> meta key removed from ' . count( $orders ) . ' orders.</p></div>';

		}

		if ( isset( $_GET['woo_reset_wpf_product_ids'] ) ) {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'product',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => wp_fusion()->crm->slug . '_product_id',
						'compare' => 'EXISTS',
					),
				),
			);

			$products = get_posts( $args );

			foreach ( $products as $product_id ) {
				delete_post_meta( $product_id, wp_fusion()->crm->slug . '_product_id' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success: </strong> ' . count( $products ) . ' products reset.</p></div>';

		}

	}

	/**
	 * Compatibility Notices
	 * Sends a notice to the admin when Reepay is misconfigured.
	 *
	 * @since 3.41.33
	 *
	 * @param array $notices Array of notices.
	 */
	public function compatibility_notices( $notices ) {

		if ( is_plugin_active( 'reepay-woocommerce-payment/reepay-woocommerce-payment.php' ) ) {

			$reepay = WC_ReepayCheckout::get_instance();
			$url    = admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=reepay_checkout';

			if ( 'wc-on-hold' !== $reepay->get_setting( 'status_authorized' ) || 'completed' === $reepay->get_setting( 'status_authorized' ) ) {

				$notices['reepay_plugin'] = sprintf( __( '<strong>Heads up!</strong> Reepay isn\'t configured correctly. We recommend setting the Authorized status to On Hold so that the price is properly synced to %1$s. You can do that %2$shere%3$s.', 'wp-fusion' ), wp_fusion()->crm->name, '<a href="' . $url . '" target="_blank">', '</a>' );
			}
		}

		return $notices;
	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds WooCommerce checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['woocommerce_ecom'] = array(
			'label'         => __( 'WooCommerce orders (Ecommerce addon)', 'wp-fusion' ),
			'title'         => 'Orders',
			'process_again' => true,
			'tooltip'       => sprintf( __( 'Syncs your WooCommerce invoices to %s. Does not apply any tags or update any contact fields.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		if ( in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {

			$options['woocommerce_ecom_products'] = array(
				'label'         => __( 'WooCommerce products (Ecommerce addon)', 'wp-fusion' ),
				'title'         => 'Products',
				'process_again' => true,
				'tooltip'       => sprintf( __( 'Syncs your WooCommerce products to %s. Products will first be looked up by name to avoid creating duplicates.', 'wp-fusion' ), wp_fusion()->crm->name ),
			);
		}

		return $options;

	}

	/**
	 * Gets the orders to be exported.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $args   The search arguments.
	 * @return array The order IDs.
	 */
	public function batch_init( $args ) {

		$statuses_to_export = array( 'wc-processing', 'wc-completed' );
		$statuses           = wc_get_order_statuses();

		foreach ( $statuses as $key => $label ) {

			if ( ! empty( wpf_get_option( "ec_woo_status_{$key}" ) ) ) {
				$statuses_to_export[] = $key;
			}
		}

		$statuses_to_export = array_unique( $statuses_to_export );

		$query_args = array(
			'numberposts' => - 1,
			'post_type'   => 'shop_order',
			'post_status' => $statuses_to_export,
			'fields'      => 'ids',
			'order'       => 'ASC',
		);

		if ( ! empty( $args['skip_processed'] ) ) {

			$query_args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'wpf_ec_complete',
					'compare' => 'NOT EXISTS',
				),
			);

			if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

				// If deal stages, also check for a missing invoice ID.

				$query_args['meta_query'][] = array(
					array(
						'key'     => 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id',
						'compare' => 'NOT EXISTS',
					),
				);
			}
		}

		$order_ids = get_posts( $query_args );

		return $order_ids;

	}


	/**
	 * Processes order actions in batches
	 *
	 * @access public
	 * @return void
	 */
	public function batch_step( $order_id ) {

		$this->send_order_data( $order_id );

	}

	/**
	 * Gets the products to be exported.
	 *
	 * @since  1.20.0
	 *
	 * @param  array $args   The search arguments.
	 * @return array The product IDs.
	 */
	public function batch_init_products( $args ) {

		$query_args = array(
			'numberposts' => - 1,
			'post_type'   => 'product',
			'post_status' => 'publish',
			'fields'      => 'ids',
			'order'       => 'ASC',
		);

		if ( ! empty( $args['skip_processed'] ) ) {

			$query_args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => wp_fusion()->crm->slug . '_product_id',
					'compare' => 'NOT EXISTS',
				),
			);

		}

		$product_ids = get_posts( $query_args );

		if ( empty( $args['skip_processed'] ) ) {

			// If we are re-processing old products let's clear out the saved ID
			// and look it up again (in case the product was deleted in the CRM).

			foreach ( $product_ids as $product_id ) {
				delete_post_meta( $product_id, wp_fusion()->crm->slug . '_product_id' );
			}
		}

		return $product_ids;

	}


	/**
	 * Sync a single product.
	 *
	 * @since  1.20.0
	 *
	 * @param  int $product_id   The product ID.
	 */
	public function batch_step_products( $product_id ) {

		$this->add_update_product( $product_id );

	}




}

new WPF_EC_Woocommerce();
