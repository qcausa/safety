<aside id="betterdocs-sidebar-right"  class="betterdocs-sidebar betterdocs-full-sidebar-right right-sidebar-toc-wrap">
    <div data-simplebar class="layout3-toc-container right-sidebar-toc-container">
        <?php
            $hierarchy     = betterdocs()->settings->get( 'toc_hierarchy' );
            $list_number   = betterdocs()->settings->get( 'toc_list_number' );
            $supported_tag = betterdocs()->settings->get( 'supported_heading_tag' );
            $htags         = $supported_tag ? implode( ',', $supported_tag ) : '';

            $attributes = betterdocs()->template_helper->get_html_attributes( [
                'htags'       => "{$htags}",
                'hierarchy'   => "{$hierarchy}",
                'list_number' => "{$list_number}"
            ] );

            echo do_shortcode( "[betterdocs_toc " . $attributes . "]" );

            if ( isset( $social_share ) && $social_share ) {
                echo betterdocs()->views->get( 'templates/parts/social-2' );
            }

            if ( isset( $feedback ) && $feedback ) {
                $reaction_text = betterdocs()->customizer->defaults->get( 'betterdocs_post_reactions_text_2' );
                echo do_shortcode( '[betterdocs_article_reactions text="'.$reaction_text.'" layout="layout-3"]' );
            }
        ?>
    </div>
</aside>
