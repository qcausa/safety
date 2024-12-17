<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
abstract class Base_Column {
	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	abstract public function get_id();

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	abstract public function get_name();

	/**
	 * Renders the content of the column for a given product.
	 * Must be implemented by the subclass to generate the HTML output specific to this column.
	 * Shouldn't be clled directly. For the public column content returned by method get_column_content()
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The rendered HTML of the column content.
	 */
	abstract protected function render( $product, $attrs = [] );

	/**
	 * Could be rewritten in column classes to set any actions for the specific column which should run globally
	 *
	 * @return void
	 */
	public function global_init() {
	}

	/**
	 * Public method to get column caontent with an ability to filter it and with implemented value_format processing
	 *
	 * @param  WC_Product $wc_product Product object to get content for.
	 * @param  array      $attrs      Column attributes.
	 * @return string
	 */
	public function get_column_content( $wc_product, $attrs = [] ) {

		global $product;

		$product = $wc_product;
		$content = $this->render( $product, $attrs );
		$format  = $attrs['value_format'] ?? '%s';
		$content = do_shortcode( sprintf( $format, $content ) );

		return apply_filters(
			'jet-wc-product-table/components/columns/get-column-content',
			$content,
			$product,
			$attrs,
			$this
		);
	}

	/**
	 * Enqueue column assets.
	 *
	 * @param  array $attrs Column attributes where assets shoud be enqueued.
	 * @return bool
	 */
	public function enqueue_column_assets( $attrs = [] ) { // phpcs:ignore
		// If column requires any assets - enqueue them here
		return true;
	}

	/**
	 * Determines if the column supports sorting. Default is false.
	 *
	 * @return bool Whether sorting is supported.
	 */
	public function support_sorting() {
		return false;
	}

	/**
	 * Provides additional settings or configuration options for the column.
	 * Can be overridden by subclasses to return custom settings specific to the column type.
	 *
	 * @return array An associative array of additional settings for the column.
	 */
	public function additional_settings() {
		return [];
	}

	/**
	 * Merges default sorting settings with column-specific settings if sorting is supported.
	 *
	 * @return array The full list of settings including sorting if applicable.
	 */
	public function get_merged_additional_settings() {

		$default_settings = [];

		if ( $this->support_sorting() ) {
			$default_settings['is_sortable'] = [
				'label' => __( 'Is Sortable', 'jet-wc-product-table' ),
				'type' => 'toggle',
				'default' => false,
				'description' => __( 'Enable sorting for this column.', 'jet-wc-product-table' ),
			];
		}

		// Add the 'Value Format' setting
		$default_settings['value_format'] = [
			'label'       => __( 'Value Format', 'jet-wc-product-table' ),
			'type'        => 'text',
			'description' => __( 'Specify a format for the column value. Use %s as a placeholder for the value.', 'jet-wc-product-table' ),
			'default'     => '%s',
		];

		return array_merge( $this->additional_settings(), $default_settings );
	}

	/**
	 * Constructs default settings for a column using its unique identifier and name.
	 *
	 * @return array Default settings of the column.
	 */
	public function column_as_default_settings() {
		$result = [
			'id'    => $this->get_id(),
			'label' => $this->get_name(),
		];

		$additional_settings = $this->get_merged_additional_settings();

		foreach ( $additional_settings as $setting => $data ) {
			$result[ $setting ] = $data['default'] ?? null;
		}

		return $result;
	}

	/**
	 * Ensure input $data contain only vlues allowed for this coulmn
	 *
	 * @param  array $data Input data.
	 * @return array  Sanitized data
	 */
	public function sanitize_column_data( $data = [] ) {

		// We need to keep _uid for some cases when we have 2 columns of the same type
		$default_data  = array_merge( [ '_uid' => 0 ], $this->column_as_default_settings() );
		$prepared_data = [];

		foreach ( $default_data as $key => $default_value ) {

			$value = isset( $data[ $key ] ) ? $data[ $key ] : $default_value;

			if ( is_bool( $default_value ) ) {
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			}

			$prepared_data[ $key ] = $value;

		}

		return $prepared_data;
	}

	/**
	 * Get sortable controls HTML markup for the given column arguments.
	 *
	 * @param  array $args Column arguments.
	 * @return string
	 */
	public function get_sortable_controls( $args = [] ) {

		$result = '';

		if ( ! $this->support_sorting() ) {
			return $result;
		}

		$is_sortable = $args['is_sortable'] ?? false;
		$is_sortable = filter_var( $is_sortable, FILTER_VALIDATE_BOOLEAN );

		if ( ! $is_sortable ) {
			return $result;
		}

		$arrows = apply_filters( 'jet-wc-product-table/column/sort-arrows-icons', [
			'up' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3l12 18h-24z"/></svg>',
			'down' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 21l-12-18h24z"/></svg>',
		] );

		// unset arguments we don't need
		unset( $args['is_sortable'] );
		unset( $args['label'] );

		$result .= sprintf(
			'<span class="jet-wc-product-table-sort" data-column="%1$s">',
			htmlentities( wp_json_encode( $args ) )
		);

		$result .= sprintf(
			'<a href="#" data-order="asc" class="jet-wc-product-table-sort__button button-up">%1$s</a>',
			$arrows['up']
		);

		$result .= sprintf(
			'<a href="#" data-order="desc" class="jet-wc-product-table-sort__button button-down">%1$s</a>',
			$arrows['down']
		);

		$result .= '</span>';

		return $result;
	}

	/**
	 * Get value of the sortable propert from the product object.
	 * This method is used for query types which are returns plain array as result - Variations, Related query
	 *
	 * @param  WC_Product $product Product to get value of the sortable property from.
	 * @return mixed
	 */
	public function get_sort_prop_value( $product ) { // phpcs:ignore
		return false;
	}

	/**
	 * Get value of the sortable propert from the product object.
	 * This method is used for query types which are returns plain array as result - Variations, Related query
	 *
	 * @return mixed
	 */
	public function sort_by_prop() {
		return false;
	}

	/**
	 * Sort table query by current column.
	 *
	 * @param array  $args  Column arguments.
	 * @param object $query Query object to set sorting for.
	 */
	public function set_order_by_column( $args = [], $query = false ) {
		$query->set_query_prop( 'order', $args['order'] );
		$query->set_query_prop( 'orderby', $this->sort_by_prop() );
	}
}
