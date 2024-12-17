<?php

namespace Jet_WC_Product_Table\Components\Filters\Filter_Types;

/**
 * An abstract class that defines the essential structure and functionality of a filter type in the product table.
 * All specific filter types must extend this class and implement its abstract methods.
 */
abstract class Base_Filter {

	public $attributes_stack = [];

	/**
	 * Retrieves the unique identifier of the filter type.
	 *
	 * @return string The unique identifier of the filter type.
	 */
	abstract public function get_id();

	/**
	 * Retrieves the display name of the filter type.
	 *
	 * @return string The display name of the filter type.
	 */
	abstract public function get_name();

	/**
	 * Inject filtered query variable into query in a specific way for each filter type
	 *
	 * @param string $query_var Variable name to insert.
	 * @param mixed  $value     Variable value.
	 * @param object $query     modifyed query.
	 */
	abstract public function set_request( $query_var, $value, $query );

	/**
	 * Renders the content of the filter type for a given arguments list.
	 *
	 * Shouldn't be called directly
	 *
	 * @param array $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The rendered HTML of the filter content.
	 */
	abstract protected function render( $attrs = [] );

	/**
	 * Renders instance of the current filter type by given attributes set
	 *
	 * @param  array $attrs Filter attributes.
	 * @return void
	 */
	public function render_instance( $attrs = [] ) {
		$this->reset_attributes_stack();
		$this->render( $attrs );
	}

	/**
	 * Returns human-readable selected filter value.
	 *
	 * @param string $query_var Filter query variable.
	 * @param string $value     Selected filter variable.
	 *
	 * @return string The rendered HTML element of the selected filter value.
	 */
	abstract public function verbose_selection( $query_var, $value );

	/**
	 * Returns the name for filter HTML element
	 *
	 * @param  array $attrs Current Filter attributes.
	 * @return string
	 */
	public function get_filter_el_name( $attrs = [] ) {
		$query_var = $attrs['query_var'] ?? false;
		return $this->get_id() . '::' . $query_var;
	}

	/**
	 * Reset attributes stack. Should b called before each filters render() call
	 *
	 * @return void
	 */
	public function reset_attributes_stack() {
		$this->attributes_stack = [];
	}

	/**
	 * Add filter attribute
	 *
	 * @param string $attr  HTML atribute of filter to add.
	 * @param string $value Value of this attribute.
	 */
	public function add_attribute_to_stack( $attr = '', $value = '' ) {

		if ( empty( $this->attributes_stack[ $attr ] ) ) {
			$this->attributes_stack[ $attr ] = [];
		}

		$this->attributes_stack[ $attr ][] = esc_attr( $value );
	}

	/**
	 * Render attributes string from the existing attributes stack
	 *
	 * @return string
	 */
	public function get_attributes_string() {

		if ( empty( $this->attributes_stack ) ) {
			return;
		}

		$attributes = [];

		foreach ( $this->attributes_stack as $attr => $values ) {
			$attributes[] = sprintf( '%1$s="%2$s"', $attr, implode( ' ', $values ) );
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Provides additional settings or configuration options for the filter type.
	 *
	 * @return array An associative array of additional settings for the filter type.
	 */
	public function additional_settings() {
		return [];
	}

	/**
	 * Get full list of aggitional settings for column type.
	 *
	 * @return array The full list of settings.
	 */
	public function get_merged_additional_settings() {
		$default_settings = [];
		return array_merge( $this->additional_settings(), $default_settings );
	}

	/**
	 * Constructs default settings for a filter type using its unique identifier and name.
	 *
	 * @return array Default settings of the filter type.
	 */
	public function filter_as_default_settings() {
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
	 * Ensure input $data contain only vlues allowed for this filter type
	 *
	 * @param  array $data Input data.
	 * @return array Sanitized data
	 */
	public function sanitize_data( $data = [] ) {

		$default_data  = $this->filter_as_default_settings();
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
}
