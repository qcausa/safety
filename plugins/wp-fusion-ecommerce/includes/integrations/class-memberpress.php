<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_MemberPress extends WPF_EC_Integrations_Base {

	/**
	 * The integration slug.
	 *
	 * @since 1.14.0
	 * @var string
	 */
	public $slug = 'memberpress';


	/**
	 * Transaction statuses.
	 *
	 * @since 1.23.2
	 * @var array The statuses.
	 */
	public $statuses = array(
		'complete',
		'pending',
		'failed',
		'refunded',
	);

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		// Settings
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 25, 2 );

		// Transaction status changed.
		foreach ( $this->statuses as $status ) {
			add_action( 'mepr-txn-status-' . $status, array( $this, 'transaction_status_change' ), 90 );
		}

		// Send transaction data
		add_action( 'mepr-txn-status-complete', array( $this, 'transaction_created' ), 15 ); // 15 so it's after WPF_MemberPress::apply_tags_checkout().
		add_action( 'mepr-txn-status-refunded', array( $this, 'transaction_refunded' ) );

		// Meta Box
		add_filter( 'wpf_memberpress_meta_box', array( $this, 'add_settings' ), 10, 2 );
		add_action( 'save_post_memberpressproduct', array( $this, 'save_post' ) );

		// Super secret admin / debugging tools
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_memberpress_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_memberpress_ecom', array( $this, 'batch_step' ) );

	}

	/**
	 * Add fields to settings page.
	 *
	 * @since 1.23.2
	 *
	 * @param array $settings Settings.
	 * @param array $options  Options.
	 * @return array Settings
	 */
	public function register_settings( $settings, $options ) {

		if ( isset( wp_fusion_ecommerce()->crm->supports ) ) {

			if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

				$settings['ec_memberpress_header_stages'] = array(
					'title'   => __( 'Memberpress Transaction Status Stages', 'wp-fusion' ),
					'type'    => 'heading',
					'section' => 'ecommerce',
					'desc'    => sprintf( __( 'For each transaction status in MemberPress, select a corresponding pipeline and stage in %s. When the transaction status is updated the deal\'s stage will also be changed.', 'wp-fusion' ), wp_fusion()->crm->name ),
				);

				if ( ! isset( $options[ wp_fusion()->crm->slug . '_pipelines' ] ) ) {
					$options[ wp_fusion()->crm->slug . '_pipelines' ] = array();
				}

				foreach ( $this->statuses as $key ) {

					$settings[ 'ec_membrepress_status_' . $key ] = array(
						'title'       => ucfirst( $key ),
						'type'        => 'select',
						'section'     => 'ecommerce',
						'placeholder' => __( 'Select a Pipeline / Stage', 'wp-fusion' ),
						'choices'     => $options[ wp_fusion()->crm->slug . '_pipelines' ],
					);

				}
			}
		}

		return $settings;

	}

	/**
	 * Gets the array of properties for an order.
	 *
	 * @since 1.19.2
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function get_order_args( $txn ) {

		$products = array(
			array(
				'id'             => $txn->product_id,
				'name'           => get_the_title( $txn->product_id ),
				'price'          => $txn->total,
				'qty'            => 1,
				'image'          => get_the_post_thumbnail_url( $txn->product_id, 'medium' ),
				'crm_product_id' => get_post_meta( $txn->product_id, wp_fusion()->crm->slug . '_product_id', true ),
			),
		);

		$mepr_options = MeprOptions::fetch();

		$userdata = get_userdata( $txn->user_id );

		if ( ! $userdata ) {
			wpf_log( 'notice', $txn->user_id, 'User ID ' . $txn->user_id . ' not found. Unable to sync transaction.' );
			$txn->update_meta( 'wpf_ec_complete', true );
			return false;
		}

		$order_args = array(
			'order_label'     => 'MemberPress Transaction #' . $txn->id,
			'order_number'    => $txn->id,
			'order_edit_link' => admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ),
			'payment_method'  => ( $txn->payment_method() ? $txn->payment_method()->name : 'manual' ),
			'user_email'      => $userdata->user_email,
			'products'        => $products,
			'line_items'      => array(),
			'total'           => $txn->total,
			'currency'        => $mepr_options->currency_code,
			'currency_symbol' => $mepr_options->currency_symbol,
			'order_date'      => strtotime( $txn->created_at ),
			'provider'        => 'memberpress',
			'user_id'         => $txn->user_id,
			'invoice_id'      => $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true ),
		);

		$order_args = apply_filters( 'wpf_ecommerce_order_args', $order_args, $txn->id );

		return $order_args;

	}


	/**
	 * Sends order data to CRM's ecommerce system.
	 *
	 * @since 1.14.0
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function transaction_created( $txn ) {

		// Fallback transactions are created with "complete" status when a
		// transaction is refunded, so we don't want to sync any data for those.

		if ( 'complete' !== $txn->status || 'payment' !== $txn->txn_type ) {
			return;
		}

		if ( false !== strpos( $txn->trans_num, 'mpwoo_' ) ) {
			return; // Fixes the Memberpress WooCommerce Plus plugin sending duplicate transactions.
		}

		$order_args = $this->get_order_args( $txn );

		if ( ! $order_args ) {
			return false; // Allow for cancelling.
		}

		$contact_id = wp_fusion()->user->get_contact_id( $txn->user_id );

		if ( empty( $contact_id ) ) {
			wpf_log( 'notice', $txn->user_id, 'No contact ID for user, failed to sync transaction.' );
			return false;
		}

		// Add order.
		$result = wp_fusion_ecommerce()->crm->add_order( $txn->id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', $txn->user_id, 'Error adding MemberPress transaction #' . $txn->id . ': ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return false;

		}

		if ( is_string( $result ) || is_numeric( $result ) ) {

			// CRMs with invoice IDs.
			$txn->update_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order.
		$txn->update_meta( 'wpf_ec_complete', true );

		do_action( 'wpf_ecommerce_complete', $txn->id, $result, $contact_id, $order_args );

	}

	/**
	 * Change stage when a transaction status is changed.
	 *
	 * @since 1.23.2
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function transaction_status_change( $txn ) {

		if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

			// HubSpot, Zoho, Sendinblue.
			$stage = wpf_get_option( 'ec_membrepress_status_' . $txn->status );

			if ( ! empty( $stage ) ) {

				$invoice_id = $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

				$result = wp_fusion_ecommerce()->crm->change_stage( $invoice_id, $stage, $txn->id );

				if ( is_wp_error( $result ) ) {
					wpf_log( 'error', $txn->user_id, 'Error changing status for MemberpPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id . '' ) . '" target="_blank">#' . $txn->id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );

				} else {

					$stage_label = wp_fusion_ecommerce()->crm_base->get_stage_label( $stage );

					wpf_log( 'info', $txn->user_id, 'MemberPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id . '' ) . '" target="_blank">#' . $txn->id . '</a> stage was updated to ' . $stage_label . ' in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );

				}
			}
		}
	}

	/**
	 * Mark the transaction refunded in the CRM.
	 *
	 * @since 1.18.0
	 *
	 * @param MeprTransaction $txn    The transaction.
	 */
	public function transaction_refunded( $txn ) {

		if ( ! in_array( 'refunds', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$invoice_id = $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

		if ( ! empty( $invoice_id ) ) {

			$contact_id = wp_fusion()->user->get_contact_id( $txn->user_id );
			$order_args = $this->get_order_args( $txn );

			$result = wp_fusion_ecommerce()->crm->refund_order( $invoice_id, 0, $txn->total, $order_args, $contact_id );

			if ( is_wp_error( $result ) ) {

				wpf_log( $result->get_error_code(), $txn->user_id, 'Error marking MemberPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> as refunded: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );

			} else {

				wpf_log( 'info', $txn->user_id, 'MemberPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> marked refunded in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );

			}
		}

	}

	/**
	 * Add product selection dropdown to MemberPress membership level settings
	 *
	 * @access  public
	 * @return  mixed Settings output
	 */

	public function add_settings( $settings, $product ) {

		if ( ! is_array( wp_fusion_ecommerce()->crm->supports ) || ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$product_id = get_post_meta( $product->ID, wp_fusion()->crm->slug . '_product_id', true );

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<br /><br /><label><strong>' . sprintf( __( '%s Product', 'wp-fusion' ), wp_fusion()->crm->name ) . ':</strong></label><br />';

		echo '<select id="wpf-ec-product" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id">';

			echo '<option></option>';

		foreach ( $available_products as $id => $name ) {

			echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . '</option>';

		}

		echo '</select>';

		echo '<br /><span class="description"><small>' . sprintf( __( 'Select a product in %s to use for orders containing this membership.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</small></span>';

	}

	/**
	 * Save product selection
	 *
	 * @access public
	 * @return void
	 */

	public function save_post( $post_id ) {

		if ( ! empty( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
			update_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ] );
		} else {
			delete_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id' );
		}

	}



	/**
	 * Allows resetting the wpf_complete flags on the transactions.
	 *
	 * @since 1.17.9
	 */
	public function settings_page_init() {

		if ( isset( $_GET['mp_reset_wpf_ec_complete'] ) ) {

			$transactions_db = MeprTransaction::get_all();
			$count           = 0;

			foreach ( $transactions_db as $transaction ) {

				if ( 'complete' != $transaction->status || empty( $transaction->user_id ) ) {
					continue; // If status is pending or user ID is unknown
				}

				$txn = new MeprTransaction( $transaction->id );

				if ( $txn->delete_meta( 'wpf_ec_complete' ) ) {
					$count++;
				}
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success: </strong><code>wpf_ec_complete</code> meta key removed from ' . $count . ' transactions.</p></div>';

		}

	}


	/*
	 *
	 * BATCH TOOLS
	 *
	 */

	/**
	 * Register the export option.
	 *
	 * @since  1.17.9
	 *
	 * @param  array $options The options.
	 * @return array The options.
	 */
	public function export_options( $options ) {

		$options['memberpress_ecom'] = array(
			'label'         => __( 'MemberPress transactions (Ecommerce addon)', 'wp-fusion' ),
			'title'         => __( 'Transactions', 'wp-fusion' ),
			'process_again' => true,
			'tooltip'       => sprintf( __( 'Syncs completed MemberPress transactions as invoices (or deals) to %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;

	}


	/**
	 * Get the transactions to be processed.
	 *
	 * @since  1.17.9
	 *
	 * @return array The transaction IDs.
	 */
	public function batch_init( $args ) {

		$transactions_db = MeprTransaction::get_all();
		$transactions    = array();

		foreach ( $transactions_db as $transaction ) {

			if ( 'complete' !== $transaction->status || empty( $transaction->user_id ) || 'payment' !== $transaction->txn_type ) {
				continue; // If status is pending, user ID is unknown, or type is some other kind (sub_account, fallbacl, subscription_confirmation, etc.)
			}

			$txn = new MeprTransaction( $transaction->id );

			if ( ! empty( $args['skip_processed'] ) && $txn->get_meta( 'wpf_ec_complete', true ) ) {
				continue; // If it's already been processed.
			}

			$transactions[] = $transaction->id;
		}

		return $transactions;

	}


	/**
	 * Process a single transaction.
	 *
	 * @since 1.17.9
	 *
	 * @param int $transaction_id The transaction ID.
	 */
	public function batch_step( $transaction_id ) {

		$txn = new MeprTransaction( $transaction_id );

		$this->transaction_created( $txn );

	}

}

new WPF_EC_MemberPress();
