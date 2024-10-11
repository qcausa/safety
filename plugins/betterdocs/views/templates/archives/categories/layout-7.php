<?php

    /**
     * Template archive docs
     *
     * @link       https://wpdeveloper.com
     * @since      1.0.0
     *
     * @package    BetterDocs
     * @subpackage BetterDocs/public
     */

    get_header();

    $view_object    = betterdocs()->views;
    $layout         = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select', 'layout-7' );
    $title_tag      = betterdocs()->customizer->defaults->get( 'betterdocs_archive_title_tag', 'h2' );
    $title_tag      = betterdocs()->template_helper->is_valid_tag( $title_tag );
    $number_of_faqs = betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_faqs' );
    $number_of_docs = betterdocs()->customizer->defaults->get( 'search_modal_query_initial_number_of_docs' );
    // $faq_terms      = betterdocs()->customizer->defaults->get( 'search_modal_query_select_specific_faq' );
    // $doc_terms      = betterdocs()->customizer->defaults->get( 'search_modal_query_select_specific_doc_category' );
?>

<div class="betterdocs-wrapper betterdocs-taxonomy-wrapper betterdocs-archive-layout-7 betterdocs-category-archive-wrapper betterdocs-wraper">
    <?php
        if( betterdocs()->customizer->defaults->get('archive_search_toogle') ){
            betterdocs()->template_helper->search();
        }

        $content_area_classes = [
            'betterdocs-content-wrapper betterdocs-display-flex',
            "doc-category-$layout"
        ];

        $current_category = get_queried_object();
        $args = betterdocs()->query->docs_query_args( [
            'term_id'        => $current_category->term_id,
            'term_slug'      => $current_category->slug,
            'posts_per_page' => -1,
            'orderby'        => betterdocs()->settings->get( 'alphabetically_order_post', 'betterdocs_order' ),
            'order'          => betterdocs()->settings->get( 'docs_order', 'ASC' )
        ] );
        $post_query = new WP_Query( $args );

        $_nested_categories = betterdocs()->query->get_child_term_ids_by_parent_id( 'doc_category', $current_category->term_id );
        if ( $_nested_categories ) {
            $sub_terms_count = count( explode( ',', $_nested_categories ) );
        } else {
            $sub_terms_count = 0;
        }
    ?>

    <div class="<?php echo esc_attr( implode( ' ', $content_area_classes ) ); ?>">
        <?php betterdocs()->template_helper->sidebar( $layout, 'template', [
                'number_of_docs' => $number_of_docs,
                'number_of_faqs' => $number_of_faqs,
                'shortcode_attr' => [
                    'nested_subcategory' => betterdocs()->settings->get('archive_nested_subcategory')
                ]
                // 'faq_terms'      => $faq_terms,
                // 'doc_terms'      => $doc_terms
        ] );?>

        <div id="main" class="betterdocs-content-area">
            <div class="betterdocs-content-inner-area">
                <?php
                    /**
                     * Breadcrumbs
                     */
                    $view_object->get( 'templates/parts/breadcrumbs', [
                        'breadcrumbs_layout' => 'layout-2'
                    ] );

                    $view_object->get( 'template-parts/archive-header', [
                       'taxonomy'         => 'doc_category',
                       'current_category' => $current_category,
                       'sub_terms_count'  => $sub_terms_count,
                       'show_icon'        => true,
                       'title_tag'        => $title_tag,
                       'show_count'       => true,
                       'found_posts'      => $post_query->found_posts
                    ] );

                    if ( $_nested_categories ) {
                        echo '<div class="betterdocs-content-wrapper betterdocs-archive-wrap betterdocs-archive-main betterdocs-categories-folder">';
                        $attributes = betterdocs()->template_helper->shortcode_atts( [
                            'title_tag'     => "$title_tag",
                            'terms'         => $_nested_categories,
                            'terms_order'   => betterdocs()->settings->get( 'terms_order', 'ASC' ),
                            'terms_orderby' => betterdocs()->settings->get( 'terms_orderby', 'betterdocs_order' ),
                            'last_update'   => true,
                            'column'        => 3,
                            'show_icon'     => betterdocs()->customizer->defaults->get( 'betterdocs_doc_page_show_category_icon' )
                        ], 'betterdocs_category_box_3', 'layout-4' );

                        if( betterdocs()->settings->get( 'archive_nested_subcategory' ) ) {
                            echo do_shortcode( '[betterdocs_category_box_3 ' . $attributes . ']' );
                        }
                        echo '</div>';
                    }

                    $view_object->get( 'template-parts/archive-doc-list', [
                        'post_query' => $post_query
                    ] );
                ?>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
