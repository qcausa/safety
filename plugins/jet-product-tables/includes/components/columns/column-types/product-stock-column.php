<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product stock in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product stock.
 */
class Product_Stock_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product stock column.
	 *
	 * @return string The unique identifier for the product stock column.
	 */
	public function get_id() {
		return 'product-stock';
	}

	/**
	 * Returns the display name of the product stock column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product stock column.
	 */
	public function get_name() {
		return __( 'Stock Status/Quantity', 'jet-wc-product-table' );
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

		$stock_status = $product->get_stock_status();
		$stock_quantity = $product->get_stock_quantity();

		switch ( $stock_status ) {
			case 'instock':
				$status = __( 'In Stock', 'jet-wc-product-table' );
				break;
			case 'outofstock':
				$status = __( 'Out of Stock', 'jet-wc-product-table' );
				break;
			case 'onbackorder':
				$status = __( 'On Backorder', 'jet-wc-product-table' );
				break;
			default:
				$status = __( 'Unknown', 'jet-wc-product-table' );
		}

		if ( $product->managing_stock() && null !== $stock_quantity ) {
			$quantity = $stock_quantity;
		} else {
			$quantity = __( 'N/A', 'jet-wc-product-table' );
		}

		return sprintf( '%s (%s)', $status, $quantity );
	}

	/**
	 * Specifies that this column type supports sorting.
	 *
	 * @return bool True, indicating sorting is supported by this column.
	 */
	public function support_sorting() {
		return true; // Enable sorting for the Product Name column
	}

	/**
	 * Get value of the sortable propert from the product object.
	 * This method is used for query types which are returns plain array as result - Variations, Related query
	 *
	 * @param  WC_Product $product Product to get value of the sortable property from.
	 * @return mixed
	 */
	public function get_sort_prop_value( $product ) {
		return floatval( $product->get_stock_quantity() );
	}

	/**
	 * Return value which will be set into the order by property of the query
	 *
	 * @return string
	 */
	public function sort_by_prop() {
		return '_stock';
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
