<?php

if ( betterdocs()->settings->get( 'show_last_update_time' ) ) {
    $doc_date_text = isset( $doc_date_text ) ? $doc_date_text :  __( 'Updated on', 'betterdocs' );
    echo wp_sprintf(
        '<div %1$s>%2$s %3$s</div>',
        $wrapper_attr,
        $doc_date_text,
        get_the_modified_date()
    );
}
