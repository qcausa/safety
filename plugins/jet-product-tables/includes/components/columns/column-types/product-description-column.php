<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product description in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product descriptions.
 */
class Product_Description_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product description column.
	 *
	 * @return string The unique identifier for the product description column.
	 */
	public function get_id() {
		return 'product-description';
	}

	/**
	 * Returns the display name of the product description column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product description column.
	 */
	public function get_name() {
		return __( 'Full Description', 'jet-wc-product-table' );
	}

	/**
	 * Renders the product description for a given product.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs   Additional attributes or data that might affect rendering.
	 *
	 * @return string
	 */
	protected function render( $product, $attrs = [] ) {
		$name = $product->get_name();
		$description = $product->get_description();
		$length = isset( $attrs['length'] ) && '' !== $attrs['length'] ? (int) $attrs['length'] : null;

		if ( null !== $length && $length > 0 ) {
			$words = explode( ' ', $description );
			$description = implode( ' ', array_slice( $words, 0, $length ) );
		}

		return '<strong>' . esc_html( $name ) . '</strong>: ' . esc_html( $description );
	}

	/**
	 * Returns additional settings for the column.
	 */
	public function additional_settings(): array {
		return [
			'length' => [
				'label' => __( 'Description Length', 'jet-wc-product-table' ),
				'type' => 'text',
				'description' => 'Enter the number of words to display in the description.',
				'default' => '',
			],
		];
	}
}
