<?php
namespace Jet_WC_Product_Table\Components\Filters\Filter_Types;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\QueryFilters;


/**
 * Filter by attributes
 */
class Attributes_Query_Filter extends Tax_Query_Filter {

	/**
	 * Retrieves the unique identifier of the filter type.
	 *
	 * @return string The unique identifier of the filter type.
	 */
	public function get_id() {
		return 'attributes_query';
	}

	/**
	 * Retrieves the display name of the filter type.
	 *
	 * @return string The display name of the filter type.
	 */
	public function get_name() {
		return __( 'Attributes Filter', 'jet-wc-product-table' );
	}

	/**
	 * Inject filtered query variable into query in a specific way for each filter type
	 *
	 * @param string $query_var Variable name to insert.
	 * @param mixed  $value     Variable value.
	 * @param object $query     Modifyed query.
	 */
	public function set_request( $query_var, $value, $query ) {

		if ( ! $value ) {
			return;
		}

		/**
		$query_filters = Package::container()->get( QueryFilters::class );

		$query->add_before_query_callback( function() use ( $query_filters, $query, $query_var, $value ) {
			add_filter( 'posts_clauses', array( $query_filters, 'add_query_clauses' ), 10, 2 );
			$query_var = ltrim( $query_var, 'pa_' );
			$query->get_query_instance()->get_current_query()->set( 'filter_' . $query_var, $value );
		} );

		$query->add_after_query_callback( function() use ( $query_filters ) {
			remove_filter( 'posts_clauses', array( $query_filters, 'add_query_clauses' ), 10 );
		} );

		*/

		$tax_query = $query->get_query_prop( 'tax_query', [] );

		$new_row = [
			'taxonomy' => $query_var,
			'field'    => 'term_id',
			'terms'    => $value,
		];

		$tax_query[] = $new_row;

		if ( empty( $tax_query['relation'] ) ) {
			$tax_query['relation'] = 'AND';
		}

		$query->set_query_prop( 'tax_query', $tax_query );
	}

	/**
	 * Return additiona settings of the filter.
	 *
	 * @return array
	 */
	public function additional_settings() {
		return [
			'placeholder' => [
				'label'       => __( 'Placeholder', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => 'Select...',
				'description' => 'Set the placeholder text for the filter.',
			],
			'query_var' => [
				'label'       => __( 'Select Attribute', 'jet-wc-product-table' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => $this->get_attribute_options(),
				'description' => 'Select the attribute to filter by.',
			],
		];
	}

	private function get_attribute_options() {
		$attributes = wc_get_attribute_taxonomies();
		$options    = [
			[
				'value' => '',
				'label' => __( 'Select...', 'jet-wc-product-table' ),
			],
		];
		foreach ( $attributes as $attribute ) {
			$options[] = [
				'value' => 'pa_' . $attribute->attribute_name,
				'label' => $attribute->attribute_label,
			];
		}

		return $options;
	}
}
