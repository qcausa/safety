<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product reviews in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product reviews.
 */
class Product_Reviews_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product reviews column.
	 *
	 * @return string The unique identifier for the product reviews column.
	 */
	public function get_id() {
		return 'product-reviews';
	}

	/**
	 * Returns the display name of the product reviews column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product reviews column.
	 */
	public function get_name() {
		return __( 'Rating', 'jet-wc-product-table' );
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

		$format = $attrs['format'] ?? 'default';
		$average = $product->get_average_rating();
		$content = '';

		if ( 'number' === $format ) {
			$content = is_numeric( $average ) ? sprintf( '%.1f', $average ) : '';
		} else {
			$content = $this->get_star_rating_html( $product );
		}

		return $content;
	}

	/**
	 * Generates HTML for star rating based on the product's average rating.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 *
	 * @return string The HTML for star ratings.
	 */
	private function get_star_rating_html( $product ) {

		$average     = $product->get_average_rating();
		$rating_html = wc_get_rating_html( $average );

		return $rating_html ? $rating_html : '';
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
	 * Provides additional settings specific to the Product Reviews column.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		return [
			'format' => [
				'label' => __( 'Rating Format', 'jet-wc-product-table' ),
				'type' => 'select',
				'description' => 'Choose how to display the rating.',
				'default' => 'default',
				'options' => [
					[
						'value' => 'default',
						'label' => 'Default (stars)',
					],
					[
						'value' => 'number',
						'label' => 'Number (average rating)',
					],
				],
			],
		];
	}

	/**
	 * Get value of the sortable propert from the product object.
	 * This method is used for query types which are returns plain array as result - Variations, Related query
	 *
	 * @param  WC_Product $product Product to get value of the sortable property from.
	 * @return mixed
	 */
	public function get_sort_prop_value( $product ) {
		return floatval( $product->get_average_rating() );
	}

	/**
	 * Return value which will be set into the order by property of the query
	 *
	 * @return string
	 */
	public function sort_by_prop() {
		return '_wc_average_rating';
	}

	/**
	 * Sort table query by current column. Rewrite this to columns support sorting
	 *
	 * @param array     $args
	 * @param \WC_Query $query
	 */
	public function set_order_by_column( $args = [], $query = false ) {
		$query->set_query_prop( 'order', $args['order'] );
		$query->set_query_prop( 'orderby', 'meta_value_num' );
		$query->set_query_prop( 'meta_key', $this->sort_by_prop() );
	}
}
