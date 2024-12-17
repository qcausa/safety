<?php

namespace Jet_WC_Product_Table\Components\Filters;

class Controller {

	protected $filters_registry = [];
	protected $is_filters_request = false;
	public $api = null;

	/**
	 * Constructor function that initializes the class and registers an action to register columns.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'register_filters' ] );
		add_action( 'init', [ $this, 'register_api_controller' ] );
	}

	/**
	 * Define $is_filters_request state
	 *
	 * @param bool $value Is filters request processing or not.
	 */
	public function set_is_filters_request( $value ) {
		$this->is_filters_request = $value;
	}

	/**
	 * Get $is_filters_request state
	 */
	public function is_filters_request() {
		return $this->is_filters_request;
	}

	/**
	 * Regsiter REST API controller
	 */
	public function register_api_controller() {
		$this->api = new API_Controller();
	}

	/**
	 * Registers all the filter types for the product table.
	 */
	public function register_filters() {

		$this->register_filter( new Filter_Types\Tax_Query_Filter() );
		$this->register_filter( new Filter_Types\Attributes_Query_Filter() );
		$this->register_filter( new Filter_Types\Search_Query_Filter() );

		do_action( 'jet-wc-product-table/components/filters/register', $this );
	}

	/**
	 * Registers a single filter instance in the filters registry.
	 *
	 * @param object $filter_instance Instance of the column class.
	 */
	public function register_filter( $filter_instance ) {
		$this->filters_registry[ $filter_instance->get_id() ] = $filter_instance;
	}

	/**
	 * Retrieves a filter type instance by ID.
	 *
	 * @param string $filter_id The ID of the filter type.
	 *
	 * @return object|false The filter type object if found, false otherwise.
	 */
	public function get_filter_type( $filter_id ) {
		return isset( $this->filters_registry[ $filter_id ] ) ? $this->filters_registry[ $filter_id ] : false;
	}

	/**
	 * Make sure given $filters_data array contains correctly formatted information about filters
	 *
	 * @param array $filters_data Input array with information about filters.
	 *
	 * @return array  Sanitized array with information about filters
	 */
	public function sanitize_filters_data( $filters_data = [] ) {
		$prepared_data = [];

		foreach ( $filters_data as $filter_data ) {
			$filter_instance = isset( $filter_data['id'] ) ? $this->get_filter_type( $filter_data['id'] ) : false;

			if ( $filter_instance ) {
				$prepared_data[] = $filter_instance->sanitize_data( $filter_data );
			}
		}

		return $prepared_data;
	}

	/**
	 * Prepares column data for use in JavaScript, formatting it for easy consumption on the frontend.
	 *
	 * @return array Array of columns data formatted for JavaScript.
	 */
	public function get_filter_types_for_js() {

		$filters_for_js = [];

		foreach ( $this->filters_registry as $filter_id => $filter_instance ) {
			$filters_for_js[] = [
				'value' => $filter_id,
				'label' => $filter_instance->get_name(),
			];
		}

		return $filters_for_js;
	}
}
