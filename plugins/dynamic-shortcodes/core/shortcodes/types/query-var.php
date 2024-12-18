<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\PermissionsError;

class QueryVar extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'query-var',
			'query_var',
		];
	}

	public static function get_query_var_privileges_error_msg() {
		return esc_html__( 'fetching a query var that requires higher privileges', 'dynamic-shortcodes' );
	}

	const WHITELIST_QUERY_VARS = [
		'author' => true,
		'author_name' => true,
		'cat' => true,
		'category_name' => true,
		'category__and' => true,
		'category__in' => true,
		'category__not_in' => true,
		'tag' => true,
		'tag_id' => true,
		'tag__and' => true,
		'tag__in' => true,
		'tag__not_in' => true,
		'tag_slug__and' => true,
		'tag_slug__in' => true,
		'p' => true,
		'name' => true,
		'page' => true,
		'page_id' => true,
		'pagename' => true,
		'post_parent' => true,
		'post__in' => true,
		'post__not_in' => true,
		'post_name__in' => true,
		'post_status' => true,
		//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
		'numberposts' => true,
		//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		'posts_per_page' => true,
		'nopaging' => true,
		'paged' => true,
		'offset' => true,
		's' => true,
		'order' => true,
		'orderby' => true,
		'meta_key' => true,
		'meta_value' => true,
		'meta_value_num' => true,
		'meta_compare' => true,
		'meta_query' => true,
		'date' => true,
		'year' => true,
		'monthnum' => true,
		'day' => true,
		'hour' => true,
		'minute' => true,
		'second' => true,
		'm' => true,
		'date_query' => true,
		'tax_query' => true,
		'ignore_sticky_posts' => true,
		'post_type' => true,
		'suppress_filters' => true,
		'error' => true,
		'subpost' => true,
		'subpost_id' => true,
		'attachment' => true,
		'attachment_id' => true,
		'static' => true,
		'w' => true,
		'feed' => true,
		'tb' => true,
		'preview' => true,
		'sentence' => true,
		'title' => true,
		'fields' => true,
		'menu_order' => true,
		'embed' => true,
		'post_parent__in' => true,
		'post_parent__not_in' => true,
		'author__in' => true,
		'author__not_in' => true,
		'cache_results' => true,
		'update_post_term_cache' => true,
		'lazy_load_term_meta' => true,
		'update_post_meta_cache' => true,
		'comments_per_page' => true,
		'no_found_rows' => true,
	];

	/**
	 * Evaluate
	 *
	 * @return string
	 */
	public function evaluate() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		$var = $this->get_arg( 0, 'string' );
		if ( ! isset( self::WHITELIST_QUERY_VARS[ $var ] ) ) {
			$msg = self::get_query_var_privileges_error_msg();
			$this->ensure_all_privileges( $msg );
		}
		return get_query_var( $var ) ?? '';
	}
}
