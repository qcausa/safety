<?php
    $_defined_vars = get_defined_vars();
    $_params       = isset( $_defined_vars['params'] ) ? $_defined_vars['params'] : [];

    if ( isset( $archive_layout ) && $archive_layout == 'layout-1' ) {
        $view_object->get( 'template-parts/category-list', $_params );
    } else if ( isset( $_params['query_args'] ) ) {
        $post_query = new WP_Query( $_params['query_args'] );
        $view_object->get( 'template-parts/archive-doc-list', [
            'post_query' => $post_query
        ] );
    }
?>
