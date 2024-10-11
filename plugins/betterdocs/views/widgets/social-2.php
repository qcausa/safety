<div
    <?php echo $wrapper_attr; ?>>
    <div class="betterdocs-social-share-heading layout-2">
        <?php
            if ( $title ) {
                echo wp_sprintf( '<h5>%s</h5>', esc_html( $title ) );
            }
        ?>
    </div>

    <ul class="betterdocs-social-share-links layout-2">
        <?php
            echo wp_sprintf(
                '<li><img src="%s" alt=""></li>',
                esc_html( betterdocs()->assets->icon( 'social/share-icon.svg' ) )
            );
            if ( ! empty( $links ) ) {
                foreach ( $links as $key => $social ) {
                    echo wp_sprintf(
                        '<li><a title="%s" href="%s" target="_blank"><img src="%s" alt="%s"></a></li>',
                        esc_attr( $social['alt'] ),
                        esc_url( $social['link'] ),
                        esc_html( $social['icon-2'] ),
                        esc_attr( $social['alt'] )
                    );
                }
            }
        ?>
    </ul>
</div>
