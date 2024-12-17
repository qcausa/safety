<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product summary in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product summary.
 */
class Product_Summary_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product summary column.
	 *
	 * @return string The unique identifier for the product summary column.
	 */
	public function get_id() {
		return 'product-summary';
	}

	/**
	 * Returns the display name of the product summary column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product summary column.
	 */
	public function get_name() {
		return __( 'Summary', 'jet-wc-product-table' );
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
		$summary = $product->get_short_description();
		$length = isset( $attrs['length'] ) && '' !== $attrs['length'] ? (int) $attrs['length'] : null;

		if ( null !== $length && $length > 0 ) {
			$words = explode( ' ', $summary );
			$summary = implode( ' ', array_slice( $words, 0, $length ) );
		}

		if ( ! $summary ) {
			return __( 'No summary available', 'jet-wc-product-table' );
		}

		return $summary;
	}

	/**
	 * Returns additional settings for the column.
	 */
	public function additional_settings(): array {
		return [
			'length' => [
				'label' => __( 'Summary Length', 'jet-wc-product-table' ),
				'type' => 'text',
				'description' => 'Enter the number of words to display in the summary. Leave empty for full summary.',
				'default' => '',
			],
		];
	}
}
