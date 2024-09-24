<?php $reading_text = betterdocs()->settings->get( 'estimated_reading_time_text' ); $reading_title = betterdocs()->settings->get('estimated_reading_time_title'); ?>
<?php echo betterdocs()->settings->get( 'enable_estimated_reading_time' ) ? do_shortcode( '[betterdocs_reading_time reading_text="'.$reading_text.'" reading_title="'.$reading_title.'"]' ) : ''; ?>
<div class="betterdocs-entry-content">
    <?php
        /**
         * Print Icon
         */
        $view_object->get( 'templates/parts/print-icon', [
            'enable' => betterdocs()->settings->get( 'enable_print_icon', false )
        ] );

        /**
         * TOC
         */
        $view_object->get( 'templates/parts/toc' );

        /**
         * Content
         */
        $view_object->get( 'templates/parts/content' );
    ?>
</div>
