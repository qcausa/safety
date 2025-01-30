<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * SureCart integration class.
 *
 * @since 3.40.48
 */

class WPF_SureCart extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available at
	 * wp_fusion()->integrations->{'my-plugin-slug'}
	 *
	 * @var  string
	 * @since 3.40.48
	 */
	public $slug = 'surecart';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 3.40.48
	 */
	public $name = 'SureCart';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.40.48
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/surecart/';

	public $apply_tags;

	public $remove_tags;

	/**
	 * Get things started.
	 *
	 * @since 3.40.48
	 * @since 3.42.15 Added format_fields method and sync custom fields method.
	 */
	public function init() {

		$this->includes();

		$this->apply_tags  = ( new \WPFusion\Integrations\Apply_Tags() )->bootstrap();
		$this->remove_tags = ( new \WPFusion\Integrations\Remove_Tags() )->bootstrap();

		// Custom Fields.
		add_action( 'surecart/checkout_confirmed', array( $this, 'sync_custom_fields' ) );

		// WPF Stuff.
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
	}

	/**
	 * Includes.
	 *
	 * @since 3.41.46
	 */
	public function includes() {

		require_once __DIR__ . '/class-apply-tags.php';
		require_once __DIR__ . '/class-remove-tags.php';
	}

	/**
	 * Add Meta Field Group
	 * Adds the field group for SureCart checkout.
	 *
	 * @since 3.42.15
	 *
	 * @param array $field_groups Field groups.
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['surecart'] = array(
			'title'  => 'SureCart',
			'fields' => array(),
		);

		return $field_groups;
	}

	/**
	 * Prepare Meta Fields
	 * Sets field labels and types for SureCart custom fields.
	 *
	 * @since 3.42.15
	 *
	 * @param array $meta_fields Meta fields.
	 * @return  array Meta fields
	 */
	public function prepare_meta_fields( $meta_fields ) {

		$api = new SureCart\Models\ApiToken();

		$meta_fields['line_1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['line_2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['city'] = array(
			'label' => 'City',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['state'] = array(
			'label' => 'State',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['country'] = array(
			'label' => 'Country',
			'type'  => 'text',
			'group' => 'surecart',
		);

		$meta_fields['postal_code'] = array(
			'label' => 'Postcode',
			'type'  => 'text',
			'group' => 'surecart',
		);

		// Custom Fields.

		// Get the custom fields via an API call to the checkouts.
		$api_token = $api->get();

		$params = array(
			'headers' => array(
				'authorization' => 'Bearer ' . $api_token,
			),
		);

		// Don't get the custom fields if they're already in the transient.
		if ( get_transient( 'surecart_custom_fields' ) ) {
			$custom_fields = get_transient( 'surecart_custom_fields' );

		} else {
			// Only get the first, most recent checkout.
			$response      = wp_safe_remote_get( 'https://api.surecart.com/v1/checkouts?limit=1', $params );
			$custom_fields = json_decode( wp_remote_retrieve_body( $response ), true )['data'][0]['metadata'];

			// Cache the custom fields for 24 hours.
			set_transient( 'surecart_custom_fields', $custom_fields, 60 * 60 * 24 );

		}

		// Map custom fields to $meta_fields.
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $field_key => $field_value ) {

				// Skip the wp_created_by field.
				if ( 'wp_created_by' === $field_key ) {
					continue;
				}

				$meta_fields[ $field_key ] = array(
					'label' => ucwords( str_replace( '_', ' ', $field_key ) ),
					'type'  => is_numeric( $field_key ) ? 'integer' : 'text',
					'group' => 'surecart',
				);
			}
		}

		return $meta_fields;
	}

	/**
	 * Sync Custom Fields
	 * Syncs custom fields to the CRM when a purchase is created.
	 *
	 * @since 3.42.15
	 *
	 * @param \SureCart\Models\Checkout $checkout The checkout data.
	 */
	public function sync_custom_fields( $checkout ) {

		$checkout_meta = $checkout->getAttributes();

		$user_meta = array(
			'first_name'  => $checkout_meta['customer']['first_name'],
			'last_name'   => $checkout_meta['customer']['last_name'],
			'line_1'      => isset( $checkout_meta['shipping_address']['line_1'] ) ? $checkout_meta['shipping_address']['line_1'] : '',
			'line_2'      => isset( $checkout_meta['shipping_address']['line_2'] ) ? $checkout_meta['shipping_address']['line_2'] : '',
			'city'        => isset( $checkout_meta['shipping_address']['city'] ) ? $checkout_meta['shipping_address']['city'] : '',
			'state'       => isset( $checkout_meta['shipping_address']['state'] ) ? $checkout_meta['shipping_address']['state'] : '',
			'country'     => isset( $checkout_meta['shipping_address']['country'] ) ? $checkout_meta['shipping_address']['country'] : '',
			'postal_code' => isset( $checkout_meta['shipping_address']['postal_code'] ) ? $checkout_meta['shipping_address']['postal_code'] : '',
		);

		// Custom Fields.
		foreach ( $checkout_meta['metadata'] as $key => $value ) {
			$user_meta[ $key ] = $value;
		}

		$user = get_user_by( 'email', $checkout_meta['email'] );

		wp_fusion()->user->push_user_meta( $user->ID, $user_meta );
	}
}

new WPF_SureCart();
