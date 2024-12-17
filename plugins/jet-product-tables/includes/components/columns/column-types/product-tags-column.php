<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product tags in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product tags.
 */
class Product_Tags_Column extends Product_Taxonomy_Column {
	/**
	 * Returns the unique identifier of the product tags column.
	 *
	 * @return string The unique identifier for the product tags column.
	 */
	public function get_id() {
		return 'product-tags';
	}

	/**
	 * Returns the display name of the product date column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product date column.
	 */
	public function get_name() {
		return __( 'Tags', 'jet-wc-product-table' );
	}

	/**
	 * Renders the product name for a given product.
	 * This method extracts and returns the name of the product, which will be displayed in the product table.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs   Additional attributes or data that might affect rendering.
	 *
	 * @return string The name of the product.
	 */
	protected function render( $product, $attrs = [] ) {

		$attrs['taxonomy'] = 'product_tag';
		$terms = $this->render_taxonomy_terms( $product, $attrs );

		return $terms;
	}

	/**
	 * Provides additional settings specific to the Product Tags column.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		return [
			'linked' => [
				'label'       => __( 'Linked', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'description' => 'Toggle to link the tags to the tag pages.',
				'default'     => true,
			],
			'delimiter' => [
				'label'       => __( 'Delimiter', 'jet-wc-product-table' ),
				'type'        => 'text',
				'description' => 'Specify a delimiter to separate multiple tags.',
				'default'     => ', ',
			],
		];
	}
}
