<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product meta in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product meta.
 */
class Product_Meta_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product meta column.
	 *
	 * @return string The unique identifier for the product meta column.
	 */
	public function get_id() {
		return 'product-meta';
	}

	/**
	 * Returns the display name of the product meta column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product meta column.
	 */
	public function get_name() {
		return __( 'Product Meta', 'jet-wc-product-table' );
	}

	/**
	 * Renders the meta value for a given product based on the specified meta key and format.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The formatted meta value of the product.
	 */
	protected function render( $product, $attrs = [] ) {

		$meta_key = $attrs['meta_key'] ?? '';
		$type = $attrs['type'] ?? 'text';

		if ( empty( $meta_key ) ) {
			return 'Meta key is not set or invalid';
		}

		$meta_value = get_post_meta( $product->get_id(), $meta_key, true );

		if ( is_wp_error( $meta_value ) ) {
			return 'Error retrieving meta data';
		}

		/**
		 * Allow to filter meta value before print
		 *
		 * @var mixed
		 */
		$meta_value = apply_filters(
			'jet-wc-product-table/columns/product-meta/meta-value',
			$meta_value,
			$meta_key,
			$product,
			$attrs
		);

		switch ( $type ) {
			case 'number':
				return is_numeric( $meta_value ) ? (float) $meta_value : '';

			case 'date':
				$date_format = get_option( 'date_format' );
				$timestamp = strtotime( $meta_value );
				return $timestamp ? wp_date( $date_format, $timestamp ) : '';

			case 'timestamp':
				$date_format = get_option( 'date_format' );
				return is_numeric( $meta_value ) ? wp_date( $date_format, $meta_value ) : $meta_value;

			case 'text':
			default:
				if ( is_array( $meta_value ) ) {
					$meta_value = implode( ', ', $meta_value );
				}

				return wp_kses_post( (string) $meta_value );

		}
	}

	/**
	 * Specifies that this column type supports sorting.
	 *
	 * @return bool True, indicating sorting is supported by this column.
	 */
	public function support_sorting() {
		return true;
	}

	/**
	 * Provides additional settings specific to the Product Meta column.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		return [
			// phpcs:ignore
			'meta_key' => [
				'label' => __( 'Meta Key', 'jet-wc-product-table' ),
				'type' => 'text',
				'description' => 'Enter the meta key for the value you want to display.',
				'default' => '',
			],
			'type' => [
				'label' => __( 'Type', 'jet-wc-product-table' ),
				'type' => 'select',
				'description' => 'Select the type of the meta value.',
				'default' => 'text',
				'options' => [
					[
						'value' => 'text',
						'label' => 'Text',
					],
					[
						'value' => 'number',
						'label' => 'Number',
					],
					[
						'value' => 'date',
						'label' => 'Date',
					],
					[
						'value' => 'timestamp',
						'label' => 'Timestamp',
					],
				],
			],
		];
	}

	/**
	 * Sort table query by current column. Rewrite this to columns support sorting
	 *
	 * @param array     $args
	 * @param \WC_Query $query
	 */
	public function set_order_by_column( $args = [], $query = false ) {

		$meta_key = ! empty( $args['meta_key'] ) ? esc_attr( $args['meta_key'] ) : false;
		$type     = $args['type'] ?? 'text';

		if ( ! $meta_key ) {
			return;
		}

		if ( 'number' === $type ) {
			$order_by = 'meta_value_num';
		} else {
			$order_by = 'meta_value';
		}

		$query->set_query_prop( 'order', $args['order'] );
		$query->set_query_prop( 'orderby', $order_by );
		$query->set_query_prop( 'meta_key', $meta_key );

		add_filter(
			'jet-wc-product-table/products-list-sort/' . $meta_key,
			function ( $result, $product ) use ( $meta_key ) {
				return $product->get_meta( $meta_key );
			},
			10, 2
		);
	}
}
