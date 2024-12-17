<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product dimensions in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product dimensions.
 */
class Product_Dimensions_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product dimensions column.
	 *
	 * @return string The unique identifier for the product dimensions column.
	 */
	public function get_id() {
		return 'product-dimensions';
	}

	/**
	 * Returns the display name of the product dimensions column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product dimensions column.
	 */
	public function get_name() {
		return __( 'Dimensions', 'jet-wc-product-table' );
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
		$dimensions = wc_format_dimensions( $product->get_dimensions( false ) );

		if ( empty( $dimensions ) ) {
			return __( 'No dimensions', 'jet-wc-product-table' );
		}

		return $dimensions;
	}
}
