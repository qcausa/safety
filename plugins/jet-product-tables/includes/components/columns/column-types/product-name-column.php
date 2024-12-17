<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product names in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product names.
 */
class Product_Name_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product name column.
	 *
	 * @return string The unique identifier for the product name column.
	 */
	public function get_id() {
		return 'product-name';
	}

	/**
	 * Returns the display name of the product name column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product name column.
	 */
	public function get_name() {
		return __( 'Product Name', 'jet-wc-product-table' );
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
		$content = esc_html( $product->get_name() );

		if ( ! empty( $attrs['linked'] ) && $attrs['linked'] ) {
			$content = '<a href="' . get_permalink( $product->get_id() ) . '">' . $content . '</a>';
		}

		return $content;
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
	 * Provides additional settings specific to the Product Name column.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		return [
			'linked' => [
				'label'       => __( 'Linked', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'description' => 'Toggle to link the product name to the product page.',
				'default'     => true,
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
		return $product->get_name();
	}

	/**
	 * Return value which will be set into the order by property of the query
	 *
	 * @return string
	 */
	public function sort_by_prop() {
		return 'name';
	}
}
