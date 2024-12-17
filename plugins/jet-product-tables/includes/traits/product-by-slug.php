<?php

namespace Jet_WC_Product_Table\Traits;

/**
 * Gets product object by its slug
 */
trait Product_By_Slug {

	public function ensure_product_by_slug( $product = '' ) {

		if ( $product && ! is_object( $product ) ) {

			$args = array(
				'name'        => $product,
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => 1,
			);

			$posts = get_posts( $args );

			// Check if a product was found
			if ( ! empty( $posts ) ) {
				$product = wc_get_product( $posts[0]->ID );
			}
		}

		return $product;
	}
}
