<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ArchiveList extends Block {

    public $tax_query_block = [];

    public function get_name() {
        return 'doc-archive-list';
    }

    protected $editor_styles = [
        'betterdocs-fontawesome',
        'betterdocs-blocks-editor',
        'betterdocs-doc-archive-list',
        'betterdocs-doc_category'
    ];

    protected $frontend_styles = [
        'betterdocs-fontawesome',
        'betterdocs-doc-archive-list',
        'betterdocs-doc_category'
    ];

    public function get_default_attributes() {
        return [
            'blockId'               => '',
            'nested_subcategory'    => false,
            'order'                 => 'asc',
            'orderby'               => 'title',
            'layout'                => 'layout-1',
            'list_icon'             => 'far fa-file-alt',
            'postsPerPageLayoutTwo' => -1,
            'listIconImageUrl'      => ''
        ];
    }

    public function render( $attributes, $content ) {
        $this->views( 'widgets/block-archive-list' );
    }

    public function view_params() {
        global $wp_query;
        $_term_slug = '';
        if ( isset( $wp_query->query ) && array_key_exists( 'doc_category', $wp_query->query ) ) {
            $_term_slug = $wp_query->query['doc_category'];
        }

        if ( isset( $wp_query->query ) && array_key_exists( 'doc_tag', $wp_query->query ) ) {
            $_term_slug = $wp_query->query['doc_tag'];
            add_filter( 'betterdocs_docs_tax_query_args', function ( $tax_query, $_multiple_kb, $_term_slug, $_kb_slug, $_origin_args ) {
                $tax_query[0]['taxonomy'] = 'doc_tag';
                unset( $tax_query[0]['operator'] );
                unset( $tax_query[0]['include_children'] );
                $this->tax_query_block = $tax_query;
                return $tax_query;
            }, 10, 5 );
            add_filter( 'betterdocs_articles_args', function ( $args, $_term_id, $_origin_args ) {
                if ( empty( $args['tax_query'] ) ) {
                    $args['tax_query'] = $this->tax_query_block;
                }
                return $args;
            }, 10, 3 );
        }

        $term = ! empty( get_term_by( 'slug', $_term_slug, 'doc_category' ) ) ? get_term_by( 'slug', $_term_slug, 'doc_category' ) : get_term_by( 'slug', $_term_slug, 'doc_tag' );

        $_docs_query = [
            'term_id'        => isset( $term->term_id ) ? $term->term_id : 0,
            'orderby'        => $this->attributes['orderby'],
            'order'          => $this->attributes['order'],
            'postsOrderBy'   => $this->attributes['orderby'],
            'postsOrder'     => $this->attributes['order'],
            'kb_slug'        => '',
            'posts_per_page' => $term == false ? 5 : -1,
            'term_slug'      => isset( $term->slug ) ? $term->slug : ''
        ];

        return [
            'term'               => $term,
            'nested_subcategory' => (bool) $this->attributes['nested_subcategory'],
            'list_icon_name'     =>! empty( $this->attributes['listIconImageUrl'] ) ? ['value' => ['url' => str_replace( 'blob:', '', $this->attributes['listIconImageUrl'] )]] : ( ! empty( $this->attributes['list_icon'] ) ? ['value' => ['url' => $this->attributes['list_icon']]] : ( ! empty( betterdocs()->settings->get( 'docs_list_icon' ) ) ? ['value' => ['url' => betterdocs()->settings->get( 'docs_list_icon' )['url']]] : [] ) ),
            'query_args'         => betterdocs()->query->docs_query_args( $_docs_query ),
            'title_tag'          => 'h2',
            'layout'             => betterdocs()->is_pro_active() ? $this->attributes['layout'] : 'layout-1',
            'posts_per_page'     => $this->attributes['postsPerPageLayoutTwo'],
            'list_icon_url'      => '',
            'layout_type'        => 'block'
        ];
    }
}
