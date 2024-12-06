<?php
    if ( ! $last_update ) {
        return;
    }
    $date                   = betterdocs()->query->latest_updated_date( $term->taxonomy, $term->slug );
    $last_updated_time_text = isset( $last_updated_time_text ) ? $last_updated_time_text : __( "Last Updated", "betterdocs" );
?>
<p class="betterdocs-last-update"><?php echo wp_sprintf( __( "$last_updated_time_text: %s", 'betterdocs' ), $date ) ?></p>
