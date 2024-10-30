<?php
    if( ! $last_update ) {
        return;
    }
    $date = betterdocs()->query->latest_updated_date( $term->taxonomy, $term->slug );
?>
<p class="betterdocs-last-update"><?php echo wp_sprintf( __( 'Last Updated: %s', 'betterdocs' ), $date ) ?></p>
