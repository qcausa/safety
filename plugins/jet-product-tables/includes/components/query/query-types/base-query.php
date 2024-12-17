<?php

namespace Jet_WC_Product_Table\Components\Query\Query_Types;

/**
 * Abstract base class for all query types.
 */
abstract class Base_Query {

	public $current_query = null;
	protected $query_props;

	public function __construct( $query_props ) {
		$this->query_props = $query_props;
	}

	/**
	 * Sets a query property.
	 *
	 * @param string $prop  Property name to set.
	 * @param mixed  $value Value for the property to set.
	 */
	public function set_query_prop( $prop, $value ) {
		$this->query_props[ $prop ] = $value;
	}

	/**
	 * Sets a query property.
	 *
	 * @param  string $prop          Property name to get.
	 * @param  mixed  $default_value Default property value.
	 * @return mixed
	 */
	public function get_query_prop( $prop, $default_value = false ) {
		return isset( $this->query_props[ $prop ] ) ? $this->query_props[ $prop ] : $default_value;
	}

	/**
	 * Get current query
	 *
	 * @return [type] [description]
	 */
	public function get_current_query() {
		return $this->current_query;
	}

	/**
	 * Gets products based on the query properties.
	 */
	abstract public function get_products();

	/**
	 * Gets the total count of products based on the query properties.
	 */
	abstract public function get_total_count();

	/**
	 * Gets the current page number.
	 */
	abstract public function get_page_num();

	/**
	 * Gets the number of products per page.
	 */
	abstract public function get_products_per_page();

	/**
	 * Gets the number of products actually displayed on the current page.
	 */
	abstract public function get_page_count();

	/**
	 * Gets the total number of pages based on the query.
	 */
	abstract public function get_pages_count();
}
