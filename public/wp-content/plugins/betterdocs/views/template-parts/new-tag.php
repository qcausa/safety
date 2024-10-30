<?php
    $check_new = betterdocs()->query->check_new_posts( $term->taxonomy, $term->slug );
    if( ! $new_post_tag || ! $check_new ) {
        return;
    }
?>
<p class="betterdocs-new-post-tag">
    <?php echo esc_html__('New', 'betterdocs') ?>
</p>
