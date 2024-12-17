<?php

namespace Jet_WC_Product_Table\Components\Query\Query_Types;

use WC_Product_Query;

/**
 * Handles product queries.
 */
class Products_Query extends Base_Query {

	protected $final_query = [];
	protected $query_result = null;

	/**
	 * Fetches and returns a list of WooCommerce products based on the specified query parameters.
	 *
	 * @return array An array of WC_Product objects or a more detailed response if paginate is true.
	 */
	public function get_products() {

		$query      = $this->get_current_query();
		$query_vars = $query->get_query_vars();

		$result = [];

		if ( ! $query ) {
			return $result;
		}

		$query_result = $this->get_current_query_result( $query );

		if ( ! empty( $query_vars['paginate'] ) ) {
			$result = $query_result->products;
		} else {
			$result = $query_result;
		}

		return $result;
	}

	/**
	 * Get results of the given query or results from the cache
	 *
	 * @param WC_Product_Query $query Query instance.
	 *
	 * @return array
	 */
	public function get_current_query_result( $query = null ) {

		if ( null === $this->query_result ) {

			if ( ! $query ) {
				$query = $this->get_current_query();
			}

			$this->query_result = $query->get_products();
		}

		return $this->query_result;
	}

	/**
	 * Process boolean arguments for query parameters.
	 *
	 * @param array  $args The query arguments array.
	 * @param string $key  The key to process.
	 *
	 * @return array The updated arguments array.
	 */
	protected function process_boolean_argument( $args, $key ) {
		if ( isset( $args[ $key ] ) && '' !== $args[ $key ] ) {
			$args[ $key ] = filter_var( $args[ $key ], FILTER_VALIDATE_BOOLEAN );
		} else {
			unset( $args[ $key ] );
		}

		return $args;
	}

	/**
	 * Generates and returns the query arguments for retrieving products
	 *
	 * @param  array $args Raw args.
	 * @return array
	 */
	protected function prepare_query_args( $args ) {

		$boolean_keys = [ 'reviews_allowed', 'virtual', 'featured', 'downloadable', 'manage_stock', 'sold_individually' ];
		foreach ( $boolean_keys as $key ) {
			$args = $this->process_boolean_argument( $args, $key );
		}

		if ( empty( $args['limit'] ) ) {
			$args['limit'] = $this->get_default_wc_product_per_page();
		}

		if ( ! empty( $args['average_rating'] ) ) {
			$args['average_rating'] = number_format( $args['average_rating'], 2 );
		}

		$args = $this->extract_array_key( $args, 'include', 'include', 'intval' );
		$args = $this->extract_array_key( $args, 'exclude', 'exclude', 'intval' );
		$args = $this->extract_array_key( $args, 'parentExclude', 'parent', 'intval' );
		$args = $this->extract_array_key( $args, 'tag' );
		$args = $this->extract_array_key( $args, 'tagID', 'product_tag_id', 'intval' );
		$args = $this->extract_array_key( $args, 'category' );
		$args = $this->extract_array_key( $args, 'categoryID', 'product_category_id', 'intval' );

		if ( ! empty( $args['specific_query'] ) ) {
			$row = $args['specific_query'];

			foreach ( $row as $row_query ) {
				$args[ $row_query['feature'] ] = $row_query['status'];
			}
		} elseif ( isset( $args['specific_query'] ) ) {
			unset( $args['specific_query'] );
		}

		if ( ! empty( $args['date_query'] ) ) {
			$row    = $args['date_query'];
			$format = 'Y-m-d';

			foreach ( $row as $row_query ) {
				$compare_date = wp_date( $format, strtotime( $row_query['year'] . '-' . $row_query['month'] . '-' . $row_query['day'] ) );
				$date_before  = wp_date( $format, strtotime( $row_query['before'] ) );
				$date_after   = wp_date( $format, strtotime( $row_query['after'] ) );
				$compare_sign = $row_query['compare'];

				switch ( $compare_sign ) {
					case '=':
						$args[ $row_query['column'] ] = $compare_date;
						break;

					case '>':
					case '>=':
					case '<':
					case '<=':
						$args[ $row_query['column'] ] = $compare_sign . $compare_date;
						break;

					case 'BETWEEN':
						$args[ $row_query['column'] ] = $date_after . '...' . $date_before;
						break;

					default:
						break;
				}
			}
		} elseif ( isset( $args['date_query'] ) ) {
			unset( $args['date_query'] );
		}

		if ( ! empty( $args['tax_query'] ) ) {
			$raw               = $args['tax_query'];
			$args['tax_query'] = []; // phpcs:ignore

			if ( isset( $args['tax_query_relation'] ) ) {
				unset( $args['tax_query_relation'] );
			}

			foreach ( $raw as $query_row ) {
				if ( isset( $query_row['exclude_children'] ) ) {
					$query_row['include_children'] = ! $query_row['exclude_children'];
					unset( $query_row['exclude_children'] );
				}

				// phpcs:ignore
				if ( empty( $query_row['operator'] ) || in_array( $query_row['operator'], [
					'IN',
					'NOT IN',
				] ) ) {
					if ( ! empty( $query_row['terms'] ) && ! is_array( $query_row['terms'] ) ) {
						$query_row['terms'] = $this->explode_string( $query_row['terms'] );
					}
				}

				if ( empty( $query_row['terms'] ) ) {
					continue;
				}

				$args['tax_query'][] = $query_row;
			}
		}

		return apply_filters( 'jet-wc-product-table/query/products-query/args', $args, $this );
	}

	/**
	 * Safely extract "maybe-array" argumnets
	 *
	 * @param  array   $args            Arguments list to extract key from.
	 * @param  string  $key             Key to extract value from.
	 * @param  string  $into_key        Additional key to extract value into it (could be the same as $key).
	 * @param  boolean $filter_callback Optional callback to filter extracted values.
	 * @return array
	 */
	public function extract_array_key( $args = [], $key = '', $into_key = false, $filter_callback = false ) {

		if ( ! $into_key ) {
			$into_key = $key;
		}

		if ( isset( $args[ $key ] ) ) {

			if ( ! is_array( $args[ $key ] ) ) {
				$args[ $key ] = $this->explode_string( $args[ $key ] );
			}

			if ( $filter_callback && is_callable( $filter_callback ) ) {
				$args[ $key ] = array_map( $filter_callback, $args[ $key ] );
			}

			$args[ $into_key ] = $args[ $key ];

			if ( $into_key !== $key ) {
				unset( $args[ $key ] );
			}
		}

		return $args;
	}

	/**
	 * Extract array from comma-separated string
	 *
	 * @param  array $str Raw args.
	 * @return array
	 */
	public function explode_string( $str = '' ) {

		if ( empty( $str ) ) {
			return [];
		}

		if ( is_array( $str ) ) {
			return $str;
		}

		$result = explode( ',', $str );
		$result = array_map( 'trim', $result );

		return $result;
	}

	/**
	 * Returns `WC_Product_Query`
	 *
	 * @return \WC_Product_Query|null
	 */
	public function get_current_query() {

		if ( null !== $this->current_query ) {
			return $this->current_query;
		}

		$this->current_query = new \WC_Product_Query( $this->prepare_query_args( array_filter( $this->query_props ) ) );

		return $this->current_query;
	}

	/**
	 * Returns default WC_Query product per page.
	 *
	 * @return float|int
	 */
	public function get_default_wc_product_per_page() {
		return wc_get_default_products_per_row() * wc_get_default_product_rows_per_page();
	}

	/**
	 * Returns total found items count
	 *
	 * @return int The total number of products.
	 */
	public function get_total_count() {
		$cached     = $this->get_cached_data( 'count' );
		$query      = $this->get_current_query();
		$query_vars = $query->get_query_vars();

		if ( false !== $cached || ! $query ) {
			return $cached;
		}

		if ( $query_vars['paginate'] ) {
			$result = $query->get_products()->total;
		} else {
			$result = count( $query->get_products() );
		}

		$this->update_query_cache( $result, 'count' );

		return (int) $result;
	}

	/**
	 * Return current listing grid page
	 *
	 * @return false|float|int
	 */
	public function get_current_items_page() {

		$query      = $this->get_current_query();
		$query_vars = $query->get_query_vars();
		$page       = ! empty( $this->final_query['paged'] ) ? $this->final_query['paged'] : false;

		if ( ! $page && ! empty( $this->final_query['page'] ) ) {
			$page = $this->final_query['page'];
		}

		if ( ! $page && ! empty( $query_vars['paged'] ) ) {
			$page = $query_vars['paged'];
		}

		if ( ! $page ) {
			$page = 1;
		}

		return $page;
	}


	/**
	 * Retrieves the current page number for pagination purposes.
	 *
	 * @return int The current page number.
	 */
	public function get_page_num() {

		if ( ! empty( $this->query_props['page'] ) ) {
			return (int) $this->query_props['page'];
		}

		// phpcs:ignore
		if ( ! empty( $_GET['paged'] ) ) {
			return absint( $_GET['paged'] ); // phpcs:ignore
		}

		return 1;
	}

	/**
	 * Determines the number of products displayed per page.
	 *
	 * @return int Number of products per page.
	 */
	public function get_products_per_page() {

		$this->setup_query();

		$query      = $this->get_current_query();
		$query_vars = $query->get_query_vars();

		if ( ! empty( $query_vars['limit'] ) ) {
			$limit = $query_vars['limit'];
		} else {
			$limit = $this->get_default_wc_product_per_page();
		}

		return $limit;
	}

	/**
	 * Counts and returns the number of products displayed on the current page.
	 *
	 * @return int Count of products on the current page.
	 */
	public function get_page_count() {
		$result   = $this->get_items_total_count();
		$per_page = $this->get_items_per_page();

		if ( $per_page < $result ) {
			$page  = $this->get_current_items_page();
			$pages = $this->get_items_pages_count();

			if ( $page < $pages ) {
				$result = $per_page;
			} elseif ( absint( $page ) === absint( $pages ) ) {
				$offset = ! empty( $this->final_query['offset'] ) ? absint( $this->final_query['offset'] ) : 0;

				if ( $result % $per_page > 0 ) {
					$result = ( $result % $per_page ) - $offset;
				} else {
					$result = $per_page - $offset;
				}
			}
		}

		return $result;
	}

	/**
	 * Calculates and returns the total number of pages based on the total count of products and the number of products per page.
	 *
	 * @return int Total number of pages.
	 */
	public function get_pages_count() {

		$query_result = $this->get_current_query_result();

		return isset( $query_result->max_num_pages ) ? $query_result->max_num_pages : 1;
	}
}
