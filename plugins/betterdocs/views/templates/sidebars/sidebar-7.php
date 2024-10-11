<?php
    if ( ( isset( $force ) && $force == null ) || ! isset( $force ) ) {
        if ( ! betterdocs()->settings->get( 'enable_sidebar_cat_list' ) ) {
            return;
        }
    }

    $wrapper_attributes = [
        'class' => ['betterdocs-sidebar betterdocs-full-sidebar-left betterdocs-sidebar-layout-7 betterdocs-background'],
        'id'    => 'betterdocs-full-sidebar-left'
    ];

    /**
     * @var array $wrapper_attr_array
     */
    if ( isset( $wrapper_attr_array ) && ! empty( $wrapper_attr_array ) && is_array( $wrapper_attr_array ) ) {
        $wrapper_attributes = betterdocs()->views->merge( $wrapper_attr_array, $wrapper_attributes );
    }

    $wrapper_attributes = betterdocs()->template_helper->get_html_attributes( $wrapper_attributes );
    $enable_sidebar_search = isset( $sidebar_search ) ?  $sidebar_search : true;
?>
<aside
    <?php echo $wrapper_attributes; ?>>
    <div class="betterdocs-sidebar-content betterdocs-category-sidebar betterdocs-height">
        <?php
            if( $enable_sidebar_search ) {
                $search_placeholder = betterdocs()->settings->get( 'search_placeholder' );
                $number_of_docs     = isset( $number_of_docs ) ? $number_of_docs : '';
                $number_of_faqs     = isset( $number_of_faqs ) ? $number_of_faqs : '';
                // $faq_terms          = isset( $faq_terms ) ? $faq_terms : '';
                // $doc_terms          = isset( $doc_terms ) ? $doc_terms : '';
                echo do_shortcode( '[betterdocs_search_modal number_of_docs="' . $number_of_docs . '" number_of_faqs="' . $number_of_faqs . '" layout="sidebar" placeholder="' . esc_html( $search_placeholder ) . '"]' );
            }

            $terms_orderby = betterdocs()->settings->get( 'terms_orderby' );
            $terms_order   = betterdocs()->settings->get( 'terms_order' );

            if ( betterdocs()->settings->get( 'alphabetically_order_term' ) ) {
                $terms_orderby = 'name';
            }

            $title_tag = betterdocs()->customizer->defaults->get( 'betterdocs_sidebar_title_tag' );

            $_shortcode_attr = [
                'terms_order'    => $terms_order,
                'terms_orderby'  => $terms_orderby,
                'sidebar_list'   => true,
                'show_icon'      => true,
                'category_icon'  => 'folder',
                'show_count'     => true,
                'list_icon_url'  => false,
                'posts_per_page' => -1,
                'title_tag'      => betterdocs()->template_helper->is_valid_tag( $title_tag ),
                'layout_type'    => isset( $layout_type ) ? $layout_type : ''
            ];

            if ( isset( $layout_type ) && $layout_type == 'block' ) {
                $_shortcode_attr['list_icon_url'] = '';
            }

            if ( isset( $shortcode_attr ) ) {
                $_shortcode_attr = array_merge( $_shortcode_attr, $shortcode_attr );
            }

            $attributes = betterdocs()->template_helper->shortcode_atts(
                $_shortcode_attr,
                'betterdocs_category_grid',
                'sidebar-7',
                $terms_orderby,
                $terms_order
            );

            echo do_shortcode( '[betterdocs_category_grid ' . $attributes . ']' );
        ?>
    </div>
</aside>
