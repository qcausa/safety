<?php
namespace Jet_WC_Product_Table\Components\Query\Query_Types;

/**
 * Handles queries for product variations, providing a structured way to access variation data.
 */
class Related_Products_Query extends Variations_Query {

	/**
	 * Fetches and returns product variations based on defined query parameters.
	 *
	 * @return void
	 */
	public function fetch_query_result() {

		$product_id = isset( $this->query_props['product_id'] ) ? absint( $this->query_props['product_id'] ) : false;

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
		} else {
			global $product;
			$product = $this->ensure_product_by_slug( $product );
		}

		if ( $product && is_object( $product ) && ! is_wp_error( $product ) ) {
			$this->query_result = array_filter(
				array_map(
					'wc_get_product',
					// 999 - is temporary solution to get all related products. In the future should be replaced
					wc_get_related_products( $product->get_id(), 999, $product->get_upsell_ids() )
				),
				'wc_products_array_filter_visible'
			);
		} else {
			$this->query_result = [];
		}
	}
}
