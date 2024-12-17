<?php

namespace Jet_WC_Product_Table\Components\Columns;

class Controller {

	private $columns_registry = [];

	/**
	 * Constructor function that initializes the class and registers an action to register columns.
	 */
	public function __construct() {
		add_action( 'init', [
			$this,
			'register_columns',
		] );
	}

	/**
	 * Registers all the column types for the product table.
	 */
	public function register_columns() {
		$this->register_column( new Column_Types\Product_Id_Column() );
		$this->register_column( new Column_Types\Product_Name_Column() );
		$this->register_column( new Column_Types\Product_Image_Column() );
		$this->register_column( new Column_Types\Product_Sku_Column() );
		$this->register_column( new Column_Types\Product_Price_Column() );
		$this->register_column( new Column_Types\Product_Description_Column() );
		$this->register_column( new Column_Types\Product_Summary_Column() );
		$this->register_column( new Column_Types\Product_Date_Column() );
		$this->register_column( new Column_Types\Product_Categories_Column() );
		$this->register_column( new Column_Types\Product_Tags_Column() );
		$this->register_column( new Column_Types\Product_Taxonomy_Column() );
		$this->register_column( new Column_Types\Product_Attributes_Column() );
		$this->register_column( new Column_Types\Product_Reviews_Column() );
		$this->register_column( new Column_Types\Product_Stock_Column() );
		$this->register_column( new Column_Types\Product_Weight_Column() );
		$this->register_column( new Column_Types\Product_Dimensions_Column() );
		$this->register_column( new Column_Types\Product_Actions_Column() );
		$this->register_column( new Column_Types\Product_Meta_Column() );

		do_action( 'jet-wc-product-table/components/columns/register', $this );

		foreach ( $this->columns_registry as $column ) {

			$column->global_init();

			if ( $column->support_sorting() && $column->sort_by_prop() ) {
				add_filter(
					'jet-wc-product-table/products-list-sort/' . $column->sort_by_prop(),
					function ( $result, $product ) use ( $column ) {
						$result = $column->get_sort_prop_value( $product );
						return $result;
					},
					10, 2
				);
			}
		}
	}

	/**
	 * Registers a single column instance in the columns registry.
	 *
	 * @param object $column_instance Instance of the column class.
	 */
	public function register_column( $column_instance ) {
		$this->columns_registry[ $column_instance->get_id() ] = $column_instance;
	}

	/**
	 * Retrieves a column instance by ID.
	 *
	 * @param string $column_id The ID of the column.
	 *
	 * @return object|false The column object if found, false otherwise.
	 */
	public function get_column( $column_id ) {
		return isset( $this->columns_registry[ $column_id ] ) ? $this->columns_registry[ $column_id ] : false;
	}

	/**
	 * Make sure given $columns_data array contains correctly formatted information about columns
	 *
	 * @param array $columns_data Input array with information about columns.
	 *
	 * @return array  Sanitized array with information about columns
	 */
	public function sanitize_columns_data( $columns_data = [] ) {

		$prepared_data = [];

		foreach ( $columns_data as $column_data ) {

			$column_instance = isset( $column_data['id'] ) ? $this->get_column( $column_data['id'] ) : false;

			if ( $column_instance ) {
				$prepared_data[] = $column_instance->sanitize_column_data( $column_data );
			}
		}

		return $prepared_data;
	}

	/**
	 * Prepares column data for use in JavaScript, formatting it for easy consumption on the frontend.
	 *
	 * @return array Array of columns data formatted for JavaScript.
	 */
	public function get_columns_for_js() {
		$columns_for_js = [];
		foreach ( $this->columns_registry as $column_id => $column_instance ) {
			$columns_for_js[] = [
				'value' => $column_id,
				'label' => $column_instance->get_name(),
			];
		}

		return $columns_for_js;
	}
}
