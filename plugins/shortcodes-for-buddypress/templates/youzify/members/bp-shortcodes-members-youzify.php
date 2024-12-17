<?php
/**
 * BuddyPress Members Directory
 *
 * @version 3.0.0
 */

global $members_atts;
?>
<div id="bp-shortcode-members" class="bp-dir-hori-nav <?php if ( !empty( $groups_atts['container_class'] ) ) { echo esc_attr( $groups_atts['container_class'] ); } ?> youzify">
	<?php do_action( 'bp_before_members_loop' ); ?>	
	<?php 
	if ( !empty( $members_atts ) ) {
		$bp_members = bp_has_members( $members_atts['bpsh_query'] );
	} else {
		$bp_members = bp_has_members( bp_ajax_querystring( 'members' ) );
	}
	if ( $bp_members ) : ?>
		<div id="members-dir-list" class="members dir-list" data-bp-list="members" data-ajax="false">
	
		<?php do_action( 'bp_before_directory_members_list' ); ?>

		<ul id="youzify-members-list" class="item-list youzify-card-avatar-border-circle youzify-card-action-buttons-block youzify-page-btns-border-oval" aria-live="assertive" aria-relevant="all">

			<?php while ( bp_members() ) : bp_the_member(); ?>

				<li <?php bp_member_class(); ?>>

					<div class="youzify-user-data">

						<?php 
							if( function_exists( 'youzify_get_user_tools' ) ) {
								youzify_get_user_tools( bp_get_member_user_id() ); 
							}
							if ( function_exists( 'youzify_members_directory_user_cover' ) ) {
								youzify_members_directory_user_cover( bp_get_member_user_id() );
							}
						?>

						<div class="youzify-item-avatar">
							<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar( 'type=full&width=100&height=100' ); ?></a>
						</div>

						<div class="item">
							<div class="item-title">
								<a class="youzify-fullname" href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
							</div>

							<div class="item-meta">
								<?php if ( bp_current_action( 'my-friends' ) ) { ?>
									<span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span>

								<?php } else { 
									
									if ( function_exists( 'youzify_get_md_user_meta' ) ) {
										echo wp_kses_post( youzify_get_md_user_meta( bp_get_member_user_id() ) );
									}
									
								} ?>

								<?php
									/**
									 * Fires inside the display of a directory member item.
									 *
									 * @since 1.1.0
									 */
									do_action( 'bp_directory_members_item_meta' ); 
								?>
							</div>

							<?php

							/**
							 * Fires inside the display of a directory member item.
							 *
							 * @since 1.1.0
							 */
							do_action( 'bp_directory_members_item' ); ?>
						</div>

						<?php

							if ( function_exists( 'youzify_get_member_statistics_data' ) ) {
								youzify_get_member_statistics_data( bp_get_member_user_id() );
							}
							
							
						?>
						<?php if ( 'on' == youzify_option( 'youzify_enable_md_cards_actions_buttons', 'on' ) && is_user_logged_in() ) : ?>

							<div class="youzify-user-actions">

								<?php

								/**
								 * Fires inside the members action HTML markup to display actions.
								 *
								 * @since 1.1.0
								 */
								do_action( 'bp_directory_members_actions' ); ?>

							</div>

						<?php endif; ?>

						<?php do_action( 'youzify_after_directory_members_actions' ); ?>
					</div>
				</li>
			<?php endwhile; ?>

			<div class="clear"></div>
		</ul>

		<?php
		/**
		 * Fires after the display of the members list.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_directory_members_list' ); ?>
		
	<?php endif; ?>
	
</div>
		
<?php

/**
 * Fires after the display of the members loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_members_loop' );


