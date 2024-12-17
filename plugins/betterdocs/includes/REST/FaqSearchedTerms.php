<?php

namespace WPDeveloper\BetterDocs\REST;

use WP_Error;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class FaqSearchedTerms extends BaseAPI {
	public function permission_check() {
		return true;
	}

	public function register() {
		$this->get( 'faq-terms-by-keyword-search', [ $this, 'search_logic' ] );
	}

	public function search_logic( $request ) {
		$keyword = $request->get_param( 'search' );

		if ( empty( $keyword ) ) {
			$error = new WP_Error( 400, __( 'FAQ Search Parameter Cannot Be Empty', 'betterdocs' ) );
			return rest_ensure_response( $error );
		}

		$term_ids = [];

		$args = [
			'post_type'      => 'betterdocs_faq',
			'post_status'    => 'publish',
			's'              => $keyword,
			'posts_per_page' => -1
		];

		$query = new \WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$categories = get_the_terms( get_the_ID(), 'betterdocs_faq_category' );

				if ( $categories ) {
					foreach ( $categories as $category ) {
						$term_ids[] = $category->term_id;
					}
				}
			}
			wp_reset_postdata();
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'betterdocs_faq_category',
				'hide_empty' => false,
				'search'     => $keyword
			]
		);

		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id;
		}

		$term_ids = array_unique( $term_ids );

		if ( empty( $term_ids ) ) {
			return rest_ensure_response( [] );
		}

		$terms_with_meta = [];

		$terms_payload = get_terms(
			[
				'taxonomy'   => 'betterdocs_faq_category',
				'hide_empty' => false,
				'include'    => $term_ids
			]
		);

		foreach ( $terms_payload as $term ) {
			$meta                          = get_term_meta( $term->term_id );
			$meta['_betterdocs_faq_order'] = empty( get_term_meta( $term->term_id, '_betterdocs_faq_order', true ) ) ? [] : [ get_term_meta( $term->term_id, '_betterdocs_faq_order', true ) ];
			$term->meta                    = $meta;
			array_push( $terms_with_meta, $term );
		}

		return rest_ensure_response( $terms_payload );
	}
}
