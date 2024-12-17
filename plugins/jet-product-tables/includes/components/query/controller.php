<?php

namespace Jet_WC_Product_Table\Components\Query;

/**
 * Main controller for handling product queries, similar to WP_Query
 */
class Controller {

	protected $query_instance = null;
	protected $query_props = [];
	protected $before_query_callbacks = [];
	protected $after_query_callbacks = [];

	public function __construct( $query_props ) {
		$this->query_props = $query_props;
	}

	/**
	 * Determines the type of query based on provided arguments and returns the corresponding query object
	 */
	public function get_query_instance() {
		if ( is_null( $this->query_instance ) ) {
			switch ( $this->query_props['query_type'] ) {
				case 'variations':
					$this->query_instance = new Query_Types\Variations_Query( $this->query_props );
					break;

				case 'current_query':
					$this->query_instance = new Query_Types\Current_Query( $this->query_props );
					break;

				case 'related_products':
					$this->query_instance = new Query_Types\Related_Products_Query( $this->query_props );
					break;

				case 'products':
				default:
					$this->query_instance = new Query_Types\Products_Query( $this->query_props );
					break;
			}
		}

		return $this->query_instance;
	}

	/**
	 * Allows setting query properties from outside; used in future for filters
	 *
	 * @param string $prop  Property name to set.
	 * @param mixed  $value Value for the property to set.
	 */
	public function set_query_prop( $prop, $value ) {
		$this->query_props[ $prop ] = $value;
		$this->get_query_instance()->set_query_prop( $prop, $value );
	}

	/**
	 * Get request properties of current query
	 *
	 * @return [type] [description]
	 */
	public function query_props() {
		return $this->query_props;
	}

	/**
	 * Get give property from query or default
	 *
	 * @param  string $prop          Property name to get.
	 * @param  mixed  $default_value Default value of the given property.
	 * @return mixed
	 */
	public function get_query_prop( $prop, $default_value = false ) {
		return $this->get_query_instance()->get_query_prop( $prop, $default_value );
	}

	/**
	 * Register callback function to run before current query
	 *
	 * @param callable $callback Callback funcation to run.
	 */
	public function add_before_query_callback( $callback ) {
		if ( is_callable( $callback ) ) {
			$this->before_query_callbacks[] = $callback;
		}
	}

	/**
	 * Register callback function to run after current query
	 *
	 * @param callable $callback Callback funcation to run.
	 */
	public function add_after_query_callback( $callback ) {
		if ( is_callable( $callback ) ) {
			$this->after_query_callbacks[] = $callback;
		}
	}

	/**
	 * Run callbacks registered to execute before query
	 *
	 * @return [type] [description]
	 */
	public function before_query() {
		if ( empty( $this->before_query_callbacks ) ) {
			return;
		}

		foreach ( $this->before_query_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this );
			}
		}
	}

	/**
	 * Run callbacks registered to execute after query
	 *
	 * @return [type] [description]
	 */
	public function after_query() {

		if ( empty( $this->after_query_callbacks ) ) {
			return;
		}

		foreach ( $this->after_query_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this );
			}
		}
	}

	/**
	 * Returns an array of products matching the query parameters
	 */
	public function get_products() {

		$this->before_query();
		$products = $this->get_query_instance()->get_products();
		$this->after_query();

		return $products;
	}

	/**
	 * Returns the total number of products matching the query parameters
	 */
	public function get_total_count() {
		return $this->get_query_instance()->get_total_count();
	}

	/**
	 * Returns the current page number
	 */
	public function get_page_num() {
		return $this->get_query_instance()->get_page_num();
	}

	/**
	 * Returns the number of products per page set in the query
	 */
	public function get_products_per_page() {
		return $this->get_query_instance()->get_products_per_page();
	}

	/**
	 * Returns the number of products that are physically displayed on the current page
	 */
	public function get_page_count() {
		return $this->get_query_instance()->get_page_count();
	}

	/**
	 * Returns the total number of pages according to the query parameters
	 */
	public function get_pages_count() {
		return $this->get_query_instance()->get_pages_count();
	}
}
