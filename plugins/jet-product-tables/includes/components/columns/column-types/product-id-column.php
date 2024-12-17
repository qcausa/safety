<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product ids in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product ids.
 */
class Product_Id_Column extends Base_Column {

	/**
	 * Returns the unique identifier of the product id column.
	 *
	 * @return string The unique identifier for the product id column.
	 */
	public function get_id() {
		return 'product-id';
	}

	/**
	 * Returns the display name of the product id column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product id column.
	 */
	public function get_name() {
		return __( 'Product ID', 'jet-wc-product-table' );
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

		if ( ! $product ) {
			return '';
		}

		$product_id = $product->get_id();

		return (string) $product_id;
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
	 * Get value of the sortable propert from the product object.
	 * This method is used for query types which are returns plain array as result - Variations, Related query
	 *
	 * @param  WC_Product $product Product to get value of the sortable property from.
	 * @return mixed
	 */
	public function get_sort_prop_value( $product ) {
		return $product->get_id();
	}

	/**
	 * Return value which will be set into the order by property of the query
	 *
	 * @return string
	 */
	public function sort_by_prop() {
		return 'ID';
	}
}
