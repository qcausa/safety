<?php
global $activity_atts;

// echo '<pre>';print_r($activity_atts);echo '</pre>';
/**
 * Fires before the activity directory listing.
 *
 * @since 1.5.0
 */
do_action( 'bp_before_directory_activity' );

if ( $activity_atts['use_compat'] ) {
	echo '<div id="buddypress" class="bp-dir-hori-nav ' . esc_attr( bp_nouveau_get_container_classes() ) . ' ' . esc_attr( $activity_atts['container_class'] ) . '">';
}
if( empty( $atts ) ){
	$atts = array();
}

bp_nouveau_before_loop(); ?>
<?php
if ( $activity_atts['allow_posting'] == 'true' && is_user_logged_in() ) :
	bp_get_template_part( 'activity/post-form' );
endif;
?>
<?php do_action( 'before_activity_listing_output', $atts, $activity_atts ); ?>

<?php if ( bp_has_activities( $activity_atts['bpsh_query'] ) ) : ?>
	<div id="activity-stream" class="activity" data-bp-list="activity" data-ajax="false">
		<ul class="activity-list item-list bp-list bp-shortcode-activity">
			<?php
			while ( bp_activities() ) :
				bp_the_activity();
				?>
				<?php bp_get_template_part( 'activity/entry' ); ?>
			<?php endwhile; ?>
			</ul>

			<?php if ( isset( $activity_atts['load_more'] ) && ! empty( $activity_atts['load_more'] ) && bp_activity_has_more_items() ) : ?>
				<ul>
					<li class="load-more">
						<a class="button" id="load-more-activity" href="<?php bp_activity_load_more_link(); ?>" data-limit='<?php echo esc_attr( $activity_atts['per_page'] ); ?>' data-page="2"><?php echo esc_html__( 'Load More', 'shortcodes-for-buddypress' ); ?></a>
					</li>
				</ul>
			<?php endif; ?>
	</div>
<?php else : ?>

	<?php bp_nouveau_user_feedback( 'activity-loop-none' ); ?>

<?php endif; ?>

<?php bp_nouveau_after_loop(); ?>

<?php do_action( 'after_activity_listing_output', $atts, $activity_atts ); ?>

<?php

if ( $activity_atts['use_compat'] ) {
	echo '</div>';
}
