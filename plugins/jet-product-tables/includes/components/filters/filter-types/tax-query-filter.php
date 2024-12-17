<?php

namespace Jet_WC_Product_Table\Components\Filters\Filter_Types;

/**
 * Filter by taxonomy
 */
class Tax_Query_Filter extends Base_Filter {

	/**
	 * Retrieves the unique identifier of the filter type.
	 *
	 * @return string The unique identifier of the filter type.
	 */
	public function get_id() {
		return 'tax_query';
	}

	/**
	 * Retrieves the display name of the filter type.
	 *
	 * @return string The display name of the filter type.
	 */
	public function get_name() {
		return __( 'Taxonomy Filter', 'jet-wc-product-table' );
	}

	/**
	 * Inject filtered query variable into query in a specific way for each filter type
	 *
	 * @param string $query_var Variable name to insert.
	 * @param mixed  $value     Variable value.
	 * @param object $query     modifyed query.
	 */
	public function set_request( $query_var, $value, $query ) {

		if ( ! $value ) {
			return;
		}

		$tax_query = $query->get_query_prop( $this->get_id(), [] );

		$new_row = [
			'taxonomy' => $query_var,
			'terms'    => $value,
			'field'    => 'term_id',
		];

		if ( empty( $tax_query ) ) {
			$tax_query[] = $new_row;
		} else {

			$merged = false;

			foreach ( $tax_query as $i => $query_row ) {
				if ( is_array( $query_row )
					&& isset( $query_row['taxonomy'] )
					&& $query_var === $query_row['taxonomy']
				) {
					$tax_query[ $i ] = $new_row;

					$merged = true;
					break;
				}
			}

			if ( ! $merged ) {
				$tax_query[] = $new_row;
			}
		}

		if ( empty( $tax_query['relation'] ) ) {
			$tax_query['relation'] = 'AND';
		}

		$query->set_query_prop( $this->get_id(), $tax_query );
	}

	/**
	 * Get array of options for the filter
	 *
	 * @param array $attrs Filter attributes.
	 * @return string
	 */
	public function get_filter_options( $attrs = [] ) {

		$tax          = $attrs['query_var'] ?? false;
		$show_count   = $attrs['show_count'] ?? false;
		$hierarchical = $attrs['hierarchical'] ?? false;

		if ( ! $tax ) {
			return;
		}

		$terms = get_terms( [
			'taxonomy'     => $tax,
			'hide_empty'   => true,
			'hierarchical' => $hierarchical,
			'count'        => $show_count,
			'pad_counts'   => $show_count,
		] );

		if ( $hierarchical ) {
			$depth = 0;
			return $this->walk_terms( $terms, $depth, $show_count );
		} else {
			return $this->process_terms_level( $terms, [], 0, $show_count );
		}
	}

	/**
	 * Walk by terms to build hierarchical tree
	 *
	 * @param  array   $terms      All terms list.
	 * @param  integer $depth      Current level depth number.
	 * @param  bool    $show_count Show/hide posts count.
	 * @return string
	 */
	public function walk_terms( $terms = [], $depth = -1, $show_count = false ) {

		if ( -1 < $depth ) {

			$tmp_terms = [];

			foreach ( $terms as $term ) {
				if ( ! isset( $tmp_terms[ $term->parent ] ) ) {
					$tmp_terms[ $term->parent ] = [];
				}

				$tmp_terms[ $term->parent ][] = $term;

			}

			$terms = $tmp_terms;

		} else {
			$terms = [ 0 => $terms ];
		}

		return $this->process_terms_level( $terms[0], $terms, 0, $show_count );
	}

	/**
	 * Process level of terms
	 *
	 * @param  array   $current_level Current level terms list to display.
	 * @param  array   $all_terms     All terms list.
	 * @param  integer $current_depth Current level depth number.
	 * @param  bool    $show_count    Show/hide posts count.
	 * @return string
	 */
	public function process_terms_level( $current_level = [], $all_terms = [], $current_depth = 0, $show_count = false ) {

		$result = '';

		foreach ( $current_level as $term ) {

			$result .= sprintf(
				'<option value="%1$s">%3$s %2$s%4$s</option>',
				$term->term_id,
				$term->name,
				str_pad( '', $current_depth * 3, '-', STR_PAD_LEFT ),
				$show_count ? ' (' . $term->count . ')' : ''
			);

			if ( isset( $all_terms[ $term->term_id ] ) ) {
				$result .= $this->process_terms_level(
					$all_terms[ $term->term_id ],
					$all_terms,
					$current_depth + 1,
					$show_count
				);
			}
		}

		return $result;
	}

	/**
	 * Renders the content of the filter type for a given arguments list.
	 *
	 * @param  array $attrs Additional attributes or data that might affect rendering.
	 */
	protected function render( $attrs = [] ) {

		$placeholder = $attrs['placeholder'] ?? __( 'Select...', 'jet-wc-product-table' );

		$this->add_attribute_to_stack( 'name', $this->get_filter_el_name( $attrs ) );
		$this->add_attribute_to_stack( 'class', 'jet-wc-product-filter' );
		$this->add_attribute_to_stack( 'data-ui', 'select' );

		printf(
			'<select %1$s>%3$s%2$s</select>',
			$this->get_attributes_string(), // phpcs:ignore
			$this->get_filter_options( $attrs ), // phpcs:ignore
			$this->get_filter_placeholder( $placeholder ) // phpcs:ignore
		);
	}

	/**
	 * Returns a placeholder HTML for given filter type
	 *
	 * @param  string $placeholder Placeholder text to display.
	 * @return string
	 */
	public function get_filter_placeholder( $placeholder ) {
		return sprintf( '<option value="">%1$s</option>', $placeholder );
	}

	/**
	 * Returns human-readable selected filter value.
	 *
	 * @param string $query_var Filter query variable.
	 * @param string $value Selected filter variable.
	 *
	 * @return string The rendered HTML of the filter content.
	 */
	public function verbose_selection( $query_var, $value ) {

		if ( ! $value ) {
			return;
		}

		$term = get_term( $value, $query_var );

		if ( ! $term || is_wp_error( $term ) ) {
			return $value;
		} else {
			return $term->name;
		}
	}

	/**
	 * Get additional settings of the filter
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
			'query_var'     => [
				'label'       => __( 'Select Taxonomy', 'jet-wc-product-table' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => $this->get_taxonomy_options(),
				'description' => 'Select the taxonomy to filter by.',
			],
			'show_count'   => [
				'label'       => __( 'Show Count', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'default'     => false,
				'description' => 'Toggle to show the number of products in the term.',
			],
			'hierarchical' => [
				'label'       => __( 'Hierarchical', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'default'     => false,
				'description' => 'Toggle to display the hierarchy of the selected taxonomy in the select options.',
			],
		];
	}

	/**
	 * Get taxonomies list for the options
	 *
	 * @return array
	 */
	private function get_taxonomy_options() {
		$taxonomies = get_taxonomies( [
			'public' => true,
			'object_type' => [ 'product' ],
		], 'objects' );
		$options    = [
			[
				'value' => '',
				'label' => __( 'Select...', 'jet-wc-product-table' ),
			],
		];
		foreach ( $taxonomies as $taxonomy ) {
			$options[] = [
				'value' => $taxonomy->name,
				'label' => $taxonomy->label,
			];
		}

		return $options;
	}
}
