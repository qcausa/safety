<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product date in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product date.
 */
class Product_Date_Column extends Base_Column {

	/**
	 * Returns the unique identifier of the product date column.
	 *
	 * @return string The unique identifier for the product date column.
	 */
	public function get_id() {
		return 'product-date';
	}

	/**
	 * Returns the display name of the product date column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product date column.
	 */
	public function get_name() {
		return __( 'Date', 'jet-wc-product-table' );
	}

	/**
	 * Renders the product name for a given product.
	 * This method extracts and returns the name of the product, which will be displayed in the product table.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering. Not used in this implementation but can be utilized for extending functionality.
	 *
	 * @return string The name of the product.
	 */
	protected function render( $product, $attrs = [] ) {

		$date_type   = $attrs['date_type'] ?? 'publication';
		$date_format = $attrs['date_format'] ?? get_option( 'date_format' );

		$date = ( 'modification' === $date_type ) ? $product->get_date_modified() : $product->get_date_created();

		if ( $date instanceof \DateTime ) {
			return $date->date( $date_format );
		} elseif ( is_array( $date ) ) {
			return implode( ', ', $date );
		} elseif ( is_wp_error( $date ) ) {
			return 'Error retrieving date';
		}

		return (string) $date;
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
	 * Process success and errors for the add to cart handler of variable products
	 */
	public function global_init() {

		add_filter(
			'jet-wc-product-table/products-list-sort/date',
			function ( $result, $product ) {
				return $product->get_date_created()->getTimestamp();
			},
			10, 2
		);

		add_filter(
			'jet-wc-product-table/products-list-sort/modified',
			function ( $result, $product ) {
				return $product->get_date_modified()->getTimestamp();
			},
			10, 2
		);
	}

	/**
	 * Sort table query by current column.
	 *
	 * @param array  $args  Column arguments.
	 * @param object $query Query object to set sorting for.
	 */
	public function set_order_by_column( $args = [], $query = false ) {

		$query->set_query_prop( 'order', $args['order'] );
		$date_type = $args['date_type'] ?? 'publication';

		switch ( $date_type ) {
			case 'modification':
				$query->set_query_prop( 'orderby', 'modified' );
				break;

			default:
				$query->set_query_prop( 'orderby', 'date' );
				break;
		}
	}

	/**
	 * Returns additional settings of the column type
	 *
	 * @return array
	 */
	public function additional_settings(): array {
		return [
			'date_type' => [
				'label' => __( 'Date Type', 'jet-wc-product-table' ),
				'type' => 'select',
				'description' => 'Choose whether to display the publication date or the modification date.',
				'default' => 'publication',
				'options' => [
					[
						'value' => 'publication',
						'label' => 'Publication Date',
					],
					[
						'value' => 'modification',
						'label' => 'Modification Date',
					],
				],
			],
			'date_format' => [
				'label' => __( 'Date Format', 'jet-wc-product-table' ),
				'type' => 'text',
				'description' => 'Enter the PHP date format to display the date.',
				'default' => get_option( 'date_format' ),
			],
		];
	}
}
