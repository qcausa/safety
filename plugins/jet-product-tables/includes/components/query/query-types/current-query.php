<?php

namespace Jet_WC_Product_Table\Components\Query\Query_Types;

use Jet_WC_Product_Table\Plugin;

/**
 * Handles product queries.
 */
class Current_Query extends Base_Query {

	public $current_query = null;
	public $modified      = false;

	/**
	 * Fetches and returns a list of WooCommerce products based on the specified query parameters.
	 *
	 * @return array An array of WC_Product objects or a more detailed response if paginate is true.
	 */
	public function get_products() {

		$query  = $this->get_current_query();
		$result = [];

		if ( empty( $query->posts ) ) {
			return $result;
		}

		foreach ( $query->posts as $post ) {
			$result[] = wc_get_product( $post );
		}

		return $result;
	}

	/**
	 * Sets a query property.
	 *
	 * @param string $prop  Property name to set.
	 * @param mixed  $value Value for the property to set.
	 */
	public function set_query_prop( $prop, $value ) {
		parent::set_query_prop( $prop, $value );
		$this->modified = true;
	}

	/**
	 * Returns current query object
	 */
	public function get_current_query() {

		if ( $this->modified ) {
			$this->current_query = null;
		}

		if ( null !== $this->current_query ) {
			return $this->current_query;
		}

		global $wp_query;

		if ( $this->modified ) {
			$wp_query = $this->requery_posts( $wp_query ); // phpcs:ignore
			$this->modified = false;
		}

		$this->current_query = $wp_query;

		return $this->current_query;
	}

	/**
	 * Query postts again with changed query parameters
	 *
	 * @param  WP_Query $wp_query Query object.
	 * @return WP_Query
	 */
	public function requery_posts( $wp_query ) {

		global $wp_query;

		foreach ( $this->query_props as $key => $value ) {

			switch ( $key ) {
				case 'meta_query':
				case 'tax_query':
					$current = $wp_query->get( $key );

					if ( ! empty( $current ) ) {
						$value = array_merge( $value, $current );
					}

					$wp_query->set( $key, $value );
					break;

				default:
					$wp_query->set( $key, $value );
					break;
			}
		}

		if ( Plugin::instance()->filters_controller->is_filters_request() ) {
			remove_all_filters( 'pre_get_posts' );
		}

		$wp_query->get_posts();

		return $wp_query;
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

		$cached = $this->get_cached_data( 'count' );
		$query  = $this->get_current_query();

		if ( false !== $cached || ! $query ) {
			return $cached;
		}

		$result = $query->found_posts;

		$this->update_query_cache( $result, 'count' );

		return (int) $result;
	}

	/**
	 * Return current listing grid page
	 *
	 * @return false|float|int
	 */
	public function get_current_items_page() {

		$query = $this->get_current_query();
		$page  = ! empty( $this->final_query['paged'] ) ? $this->final_query['paged'] : false;

		if ( ! $page && ! empty( $this->final_query['page'] ) ) {
			$page = $this->final_query['page'];
		}

		if ( ! $page && ! empty( $this->final_query['page'] ) ) {
			$page = $this->final_query['page'];
		}

		if ( ! $page && ! empty( $query->query_var['paged'] ) ) {
			$page = $query->query_var['paged'];
		}

		if ( ! $page && ! empty( $query->query_vars['page'] ) ) {
			$page = $query->query_vars['page'];
		}

		if ( ! $page && ! empty( $query->query_vars['paged'] ) ) {
			$page = $query->query_vars['paged'];
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
		return $this->get_current_items_page();
	}

	/**
	 * Determines the number of products displayed per page.
	 *
	 * @return int Number of products per page.
	 */
	public function get_products_per_page() {
		$query = $this->get_current_query();
		return $query->query_vars['posts_per_page'];
	}

	/**
	 * Counts and returns the number of products displayed on the current page.
	 *
	 * @return int Count of products on the current page.
	 */
	public function get_page_count() {
		$query = $this->get_current_query();
		return $query->post_count;
	}

	/**
	 * Calculates and returns the total number of pages based on the total count of products
	 * and the number of products per page.
	 *
	 * @return int Total number of pages.
	 */
	public function get_pages_count() {
		$query = $this->get_current_query();
		return $query->max_num_pages;
	}
}
