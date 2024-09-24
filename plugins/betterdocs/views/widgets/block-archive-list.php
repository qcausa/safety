<?php
    $current_category = get_queried_object();

    if ( $current_category != null && $layout == 'layout-1' ):

        ?>
            <div class='betterdocs-content-area block-archive-list <?php echo esc_attr($blockId); ?>'>
                <div class="betterdocs-content-inner-area">
                            <div class="betterdocs-entry-title">
                                <?php
                                    echo wp_sprintf(
                                        '<%1$s class="betterdocs-entry-heading">%2$s</%1$s>',
                                        $title_tag,
                                        $current_category->name
                                    );
                                    echo wp_sprintf( '<p>%s</p>', wp_kses_post( $current_category->description ) );
                                ?>
                            </div>
                            <div class="betterdocs-entry-body betterdocs-taxonomy-doc-category">
                                <?php $view_object->get( 'widgets/archive-list' );?>
                            </div>
                </div>
            </div>
        <?php
    elseif( $current_category != null && $layout == 'layout-2'):

        do_action( 'archive_handbook_list' );

    endif;
?>
