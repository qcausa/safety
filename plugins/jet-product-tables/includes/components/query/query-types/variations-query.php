<?php
namespace Jet_WC_Product_Table\Components\Query\Query_Types;

use Jet_WC_Product_Table\Traits\Product_By_Slug;

/**
 * Handles queries for product variations, providing a structured way to access variation data.
 */
class Variations_Query extends Base_Query {

	use Product_By_Slug;

	protected $query_result = null;

	/**
	 * Fetches and returns product variations based on defined query parameters.
	 *
	 * @return array An array of product variations, empty if no variations are found.
	 * точно для сингл подходит
	 */
	public function get_products() {

		if ( null === $this->query_result ) {

			$this->fetch_query_result();

			if ( ! empty( $this->query_props['orderby'] ) ) {
				$this->reorder_results();
			}

			$this->filter_results();

		}

		return $this->query_result;
	}

	/**
	 * Fill $this->query_result property with actual products
	 *
	 * @return void
	 */
	public function fetch_query_result() {

		switch ( $this->query_props['variation_type'] ) {

			case 'specific_product_ids':
				$product_ids = explode( ',', $this->query_props['product_ids'] );
				$this->query_result = $this->get_variations_by_product_ids( $product_ids );
				break;

			case 'specific_product_skus':
				$product_skus = explode( ',', $this->query_props['product_skus'] );
				$this->query_result = $this->get_variations_by_product_skus( $product_skus );
				break;

			case 'current_product':
			default:
				if ( ! empty( $this->query_props['parent_product_id'] ) ) {
					$product = wc_get_product( $this->query_props['parent_product_id'] );
				} else {
					global $product;
					$product = $this->ensure_product_by_slug( $product );
				}

				if ( $product && is_object( $product ) && $product->is_type( 'variable' ) ) {

					$variations = $product->get_available_variations();

					$this->query_result = array_map( function ( $variation ) {
						return wc_get_product( $variation['variation_id'] );
					}, $variations );
				} else {
					$this->query_result = [];
				}

				break;
		}
	}

	/**
	 * Filter query results
	 *
	 * @return [type] [description]
	 */
	public function filter_results() {

		if ( empty( $this->query_props['tax_query'] ) && empty( $this->query_props['s'] ) ) {
			return;
		}

		$this->query_result = array_filter( $this->query_result, [ $this, 'is_item_match' ] );
	}

	/**
	 * Check if item is match query props
	 *
	 * @param  WC_Product $product WooCommerce product object.
	 * @return boolean
	 */
	public function is_item_match( $product ) {

		if ( ! empty( $this->query_props['s'] ) ) {

			$search_terms = trim( $this->query_props['s'] );
			$search_terms = explode( ' ', $search_terms );

			foreach ( $search_terms as $term ) {

				$term = trim( $term );

				if ( false !== strpos( $product->get_name(), $term )
					|| false !== strpos( $product->get_sku(), $term )
					|| false !== strpos( $product->get_short_description(), $term )
					|| false !== strpos( $product->get_description(), $term )
				) {
					return true;
				}
			}
		}

		if ( ! empty( $this->query_props['tax_query'] ) ) {

			$query = $this->query_props['tax_query'];
			$relation = ! empty( $tax_query['relation'] ) ? $tax_query['relation'] : 'and';
			$relation = strtolower( $relation );

			if ( isset( $query['relation'] ) ) {
				unset( $query['relation'] );
			}

			$all_match = true;

			foreach ( $query as $row ) {

				$tax   = $row['taxonomy'] ?? false;
				$term  = $row['terms'] ?? false;
				$field = $row['field'] ?? 'term_id';

				if ( ! $tax || ! $term ) {
					continue;
				}

				$has_term = has_term( $term, $tax, $product->get_id() );

				if ( $has_term && 'or' === $relation ) {
					return true;
				} elseif ( ! $has_term && 'and' === $relation ) {
					$all_match = false;
				}
			}

			if ( 'and' === $relation && $all_match ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reorder query results
	 *
	 * @return void
	 */
	public function reorder_results() {

		usort( $this->query_result, function ( $product_a, $product_b ) {

			$orderby = $this->query_props['orderby'];
			$order   = $this->query_props['order'] ?? 'asc';
			$order   = strtolower( $order );

			// phpcs:ignore
			if ( in_array( $orderby, [ 'meta_value', 'meta_value_num' ] )
				&& isset( $this->query_props['meta_key'] )
			) {
				$orderby = $this->query_props['meta_key'];
			}

			$prop_a = apply_filters( 'jet-wc-product-table/products-list-sort/' . $orderby, false, $product_a );
			$prop_b = apply_filters( 'jet-wc-product-table/products-list-sort/' . $orderby, false, $product_b );

			if ( false === $prop_a || false === $prop_b ) {
				return 0;
			}

			if ( $prop_a === $prop_b ) {
				return 0;
			} elseif ( 'asc' === $order ) {
				return ( $prop_a < $prop_b ) ? -1 : 1;
			} else {
				return ( $prop_a > $prop_b ) ? -1 : 1;
			}
		} );
	}

	/**
	 * Fetches product variations by product IDs.
	 *
	 * @param  array $product_ids Array of product IDs.
	 * @return array Array of WC_Product_Variation objects.
	 */
	private function get_variations_by_product_ids( $product_ids ) {
		$variations = [];
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( 'variable' ) ) {
				$variations = array_merge( $variations, $product->get_available_variations() );
			}
		}
		return array_map(function ( $variation ) {
			return wc_get_product( $variation['variation_id'] );
		}, $variations);
	}

	/**
	 * Fetches product variations by product SKUs.
	 *
	 * @param array $product_skus Array of product SKUs.
	 * @return array Array of WC_Product_Variation objects.
	 */
	private function get_variations_by_product_skus( $product_skus ) {
		$variations = [];
		foreach ( $product_skus as $product_sku ) {
			$product_id = wc_get_product_id_by_sku( $product_sku );
			if ( $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product && $product->is_type( 'variable' ) ) {
					$variations = array_merge( $variations, $product->get_available_variations() );
				}
			}
		}
		return array_map(function ( $variation ) {
			return wc_get_product( $variation['variation_id'] );
		}, $variations);
	}

	/**
	 * Fetches product variations filtered by attributes.
	 *
	 * @param int    $product_id The parent product ID.
	 * @param string $attribute  The attribute to filter by (e.g., 'color').
	 * @param mixed  $value      Attribute value to compare.
	 * @return array Filtered array of WC_Product_Variation objects.
	 */
	public function get_filtered_variations( $product_id, $attribute, $value ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return [];
		}

		$variations = $product->get_available_variations();
		$filtered_variations = array_filter( $variations, function ( $variation ) use ( $attribute, $value ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );
			$attributes = $variation_obj->get_attributes();
			return isset( $attributes[ $attribute ] ) && $attributes[ $attribute ] === $value;
		} );

		return array_map( function ( $variation ) {
			return wc_get_product( $variation['variation_id'] );
		}, $filtered_variations );
	}

	/**
	 * Calculates and returns the total number of product variations that match the query criteria.
	 *
	 * @return int Total count of product variations.
	 */
	public function get_total_count() {
		return count( $this->get_products() );
	}

	/**
	 * Retrieves the current page number for pagination purposes.
	 *
	 * @return int The current page number.
	 */
	public function get_page_num() {
		return 1;
	}

	/**
	 * Determines the number of product variations displayed per page.
	 *
	 * @return int Number of product variations per page.
	 */
	public function get_products_per_page() {
		return $this->get_total_count();
	}

	/**
	 * Counts and returns the number of variations displayed on the current page.
	 *
	 * @return int Count of variations on the current page.
	 */
	public function get_page_count() {
		return $this->get_total_count();
	}

	/**
	 * Calculates and returns the total number of pages based on the total count of variations and the number of variations per page.
	 *
	 * @return int Total number of pages.
	 */
	public function get_pages_count() {
		return 1;
	}

	public function get_items_per_page() {
		return $this->get_total_count();
	}
}
