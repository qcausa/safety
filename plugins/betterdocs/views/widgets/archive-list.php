<?php
use WPDeveloper\BetterDocs\Utils\Helper;

$_defined_vars = get_defined_vars();
$_params       = isset( $_defined_vars['params'] ) ? $_defined_vars['params'] : [];
$pagination    = isset( $_params['pagination'] ) ? $_params['pagination'] : false;

if ( isset( $archive_layout ) && $archive_layout == 'layout-1' ) {
	$view_object->get( 'template-parts/category-list', $_params );
} elseif ( isset( $_params['query_args'] ) ) {
	$post_query = new WP_Query( $_params['query_args'] );
	$view_object->get(
		'template-parts/archive-doc-list',
		[
			'post_query' => $post_query
		]
	);
}

if ( isset( $pagination ) && $pagination ) {
	$current_term = isset( $edit_mode ) && $edit_mode && isset( $widget_type ) && $widget_type == 'elementor' ? Helper::get_highest_docs_term() : ( isset( $_params['term'] ) ? $_params['term'] : '' );
	$query_args   = isset( $_params['query_args'] ) ? $_params['query_args'] : [];
	$post_query   = new WP_Query( $query_args );
	$total_pages  = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / 10 );

	$link = ! is_wp_error( get_term_link( $current_term, 'doc_category' ) ) ? get_term_link( $current_term, 'doc_category' ) : ( get_the_ID() != false ? get_permalink( get_the_ID() ) : '' );

	$view_object->get(
		'template-parts/pagination',
		[
			'total_pages'  => $total_pages,
			'link'         => $link,
			'current_page' => isset( $page ) ? $page : 1,
			'template'     => 'doc_category'
		]
	);
}
