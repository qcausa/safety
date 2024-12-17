<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product attributes in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product attributes.
 */
class Product_Attributes_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product attributes column.
	 *
	 * @return string The unique identifier for the product attributes column.
	 */
	public function get_id() {
		return 'product-attributes';
	}

	/**
	 * Returns the display name of the product attributes column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product attributes column.
	 */
	public function get_name() {
		return __( 'Product Attributes', 'jet-wc-product-table' );
	}

	/**
	 * Renders the product attribute for a given product.
	 * This method extracts and returns the attribute of the product, which will be displayed in the product table.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The attribute value of the product.
	 */
	protected function render( $product, $attrs = [] ) {
		$attribute_name = $attrs['attribute_to_show'] ?? '';
		if ( ! $attribute_name ) {
			return '';
		}

		$attribute_value = $product->get_attribute( $attribute_name );

		if ( is_array( $attribute_value ) ) {
			return implode( ', ', $attribute_value );
		} elseif ( is_wp_error( $attribute_value ) ) {
			return 'Error retrieving attribute';
		}

		return (string) $attribute_value;
	}

	/**
	 * Fetches a list of available attributes for use in a select control.
	 *
	 * @return array Array of attributes with value and label.
	 */
	private function get_available_attributes() {
		$attributes = wc_get_attribute_taxonomies();
		$attribute_options = [];

		foreach ( $attributes as $attribute ) {
			$attribute_options[] = [
				'value' => wc_attribute_taxonomy_name( $attribute->attribute_name ),
				'label' => $attribute->attribute_label,
			];
		}

		return $attribute_options;
	}

	/**
	 * Provides additional settings specific to the Product Attributes column.
	 * Adds an option to select which attribute to display.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		$attributes = $this->get_available_attributes();
		return [
			'attribute_to_show' => [
				'label' => __( 'Attribute to Show', 'jet-wc-product-table' ),
				'type' => 'select',
				'description' => 'Select which attribute to display in this column.',
				'options' => $attributes,
				'default' => '',
			],
		];
	}
}
