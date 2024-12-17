<?php

namespace Jet_WC_Product_Table\Components\Filters\Filter_Types;

class Search_Query_Filter extends Base_Filter {

	public function get_id() {
		return 'search_query';
	}

	public function get_name() {
		return __( 'Search Filter', 'jet-wc-product-table' );
	}

	public function set_request( $query_var, $value, $query ) {

		if ( ! $value ) {
			return;
		}

		$query->set_query_prop( 's', $value );
	}

	protected function render( $attrs = [] ) {

		$show_search_button  = isset( $attrs['show_search_button'] ) ? $attrs['show_search_button'] : true;
		$show_search_button  = filter_var( $show_search_button, FILTER_VALIDATE_BOOLEAN );
		$search_on_typing    = isset( $attrs['search_on_typing'] ) ? $attrs['search_on_typing'] : false;
		$search_on_typing    = filter_var( $search_on_typing, FILTER_VALIDATE_BOOLEAN );
		$search_button_label = isset( $attrs['search_button_label'] ) ? wp_kses_post( $attrs['search_button_label'] ) : esc_html__( 'Search', 'jet-wc-product-table' );
		$placeholder         = $attrs['placeholder'] ?? __( 'Search products...', 'jet-wc-product-table' );
		$min_chars           = isset( $attrs['min_chars_to_start_search'] ) ? absint( $attrs['min_chars_to_start_search'] ) : 0;

		$search_button = '';

		if ( $show_search_button ) {

			$button_classes = apply_filters(  'jet-wc-product-table/components/filters/types/search/button-classes', [
				'jet-wc-product-filter-button',
				'jet-wc-search-button',
				'button',
			] );

			$search_button = sprintf(
				'<button type="button" class="%2$s">%1$s</button>',
				$search_button_label,
				implode( ' ', $button_classes )
			);
		}

		$this->add_attribute_to_stack( 'type', 'search' );
		$this->add_attribute_to_stack( 'name', $this->get_filter_el_name( $attrs ) );
		$this->add_attribute_to_stack( 'class', 'jet-wc-product-filter' );
		$this->add_attribute_to_stack( 'placeholder', $placeholder );
		$this->add_attribute_to_stack( 'data-ui', 'search' );
		$this->add_attribute_to_stack( 'data-search-on-typing', $search_on_typing ? 1 : 0 );
		$this->add_attribute_to_stack( 'data-min-chars-to-start-search', $min_chars );

		// phpcs:ignore
		printf( '<input %1$s/>%2$s', $this->get_attributes_string(), $search_button );
	}

	/**
	 * Get selecte filter value in human-readable format
	 *
	 * @param  string $query_var Query varisble. Not used in case of search.
	 * @param  string $value     User inputted value.
	 * @return string
	 */
	public function verbose_selection( $query_var, $value ) {

		if ( ! $value ) {
			return;
		}

		return sprintf( '%1$s: %2$s', __( 'Search', 'jet-wc-product-table' ), esc_html( $value ) );
	}

	/**
	 * Additional settings for the filter type.
	 *
	 * @return array The additional settings.
	 */
	public function additional_settings() {
		return [
			'search_input'              => [
				'label'       => __( 'Placeholder', 'jet-wc-product-table' ),
				'type'        => 'search',
				'default'     => __( 'Search products...', 'jet-wc-product-table' ),
				'description' => 'A new input field above the "Search on typing" toggle.',
			],
			'search_on_typing'          => [
				'label'   => __( 'Search on typing', 'jet-wc-product-table' ),
				'type'    => 'toggle',
				'default' => false,
			],
			'min_chars_to_start_search' => [
				'label'       => __( 'Min chars to start search', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => '3',
				'description' => 'Minimum characters required to start the search.',
			],
			'show_search_button'        => [
				'label'       => __( 'Show search button', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'default'     => true,
				'description' => 'Toggle to show or hide the search button.',
			],
			'search_button_label'       => [
				'label'       => __( 'Search button label', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => __( 'Search', 'jet-wc-product-table' ),
				'description' => 'Label for the search button.',
			],
		];
	}
}
