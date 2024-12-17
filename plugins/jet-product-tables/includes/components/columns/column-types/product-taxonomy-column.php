<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Base class for displaying taxonomy terms in the product table.
 */
class Product_Taxonomy_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product taxonomy column.
	 * This method can be overridden in child classes for specific taxonomy terms.
	 *
	 * @return string The unique identifier for the taxonomy column.
	 */
	public function get_id() {
		return 'product-taxonomy';
	}

	/**
	 * Returns the display name of the product taxonomy column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the taxonomy column.
	 */
	public function get_name() {
		return __( 'Custom Taxonomy Terms', 'jet-wc-product-table' );
	}

	/**
	 * Renders taxonomy terms for a given product.
	 * This method should be implemented to handle the specific taxonomy logic.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Attributes containing settings like 'taxonomy', 'delimiter', 'linked'.
	 *
	 * @return string Formatted list of taxonomy terms.
	 */
	protected function render_taxonomy_terms( $product, $attrs ) {

		$terms = get_the_terms( $product->get_id(), $attrs['taxonomy'] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$delimiter = isset( $attrs['delimiter'] ) ? $attrs['delimiter'] : ', ';
		$linked = isset( $attrs['linked'] ) ? $attrs['linked'] : true;

		$links = array_map(function ( $term ) use ( $linked ) {
			$link = get_term_link( $term );
			return $linked ? "<a href='{$link}'>{$term->name}</a>" : $term->name;
		}, $terms);

		return implode( $delimiter, $links );
	}

	/**
	 * Renders the taxonomy terms for a given product.
	 * This method extracts and returns the taxonomy terms of the product, which will be displayed in the product table.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The taxonomy terms of the product.
	 */
	protected function render( $product, $attrs = [] ) {
		if ( empty( $attrs['taxonomy'] ) ) {
			return __( 'Taxonomy not specified', 'jet-wc-product-table' );
		}

		return $this->render_taxonomy_terms( $product, $attrs );
	}

	/**
	 * Provides additional settings specific to the taxonomy columns.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {

		$taxonomies = get_taxonomies(
			[
				'public'      => true,
				'object_type' => [ 'product' ],
			],
			'objects'
		);

		$taxonomy_options = array_map( function ( $taxonomy ) {
			return [
				'value' => $taxonomy->name,
				'label' => $taxonomy->labels->singular_name,
			];
		}, $taxonomies );

		$taxonomy_options = array_merge(
			[
				[
					'value' => '',
					'label' => 'Select taxonomy to show...',
				],
			],
			array_values( $taxonomy_options )
		);

		return [
			'taxonomy' => [
				'label' => __( 'Taxonomy', 'jet-wc-product-table' ),
				'type' => 'select',
				'default' => 'product_cat',
				'description' => 'Choose which taxonomy terms to display.',
				'options' => $taxonomy_options,
			],
			'linked' => [
				'label' => __( 'Linked', 'jet-wc-product-table' ),
				'type' => 'toggle',
				'default' => true,
				'description' => 'Toggle to link the taxonomy term to its archive page.',
			],
			'delimiter' => [
				'label' => __( 'Delimiter', 'jet-wc-product-table' ),
				'type' => 'text',
				'default' => ', ',
				'description' => 'Set the delimiter to separate taxonomy terms.',
			],
		];
	}
}
