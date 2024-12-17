<?php
/**
 * BuddyPress - Members Notifications Loop
 *
 * @since 3.0.0
 * @version 3.1.0
 */
global $notifications_atts;
$user_id = ( isset( $notifications_atts['user_id'] ) && $notifications_atts['user_id'] != '' ) ? : get_current_user_id();
$args    = array(
	'user_id'    => $user_id,
	'is_new'     => true,
	'page'       => $notifications_atts['page'],
	'per_page'   => $notifications_atts['per_page'],	
	'sort_order' => $notifications_atts['order'],
);
add_filter(
	'bp_current_action',
	function( $current_action ) {
		return 'unread';
	}
);
?>
<div id="buddypress" class="bps-notifications-shortcode bp-dir-hori-nav <?php
if( function_exists( 'bp_nouveau_get_container_classes' ) ){
	echo esc_attr( bp_nouveau_get_container_classes() ). ' '. esc_attr( $notifications_atts['container_class'] );
}else{
	echo esc_attr( $notifications_atts['container_class'] );
}
?>">

<input type="hidden" data-bp-filter="notifications" value="<?php echo esc_attr( $notifications_atts['bpsh_query'] ); ?>" />		
	<?php	
	if ( bp_has_notifications( $args ) ) :	?>

		<table class="notifications bp-tables-user">
			<thead>
				<tr>
					<th class="icon"></th>
					<th><?php esc_html_e( 'Notification', 'shortcodes-for-buddypress' ); ?></th>
					<th><?php esc_html_e( 'Date Received', 'shortcodes-for-buddypress' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'shortcodes-for-buddypress' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php
				while ( bp_the_notifications() ) :
					bp_the_notification();
					?>

					<tr>
						<td></td>
						<td class="notification-description"><?php bp_the_notification_description(); ?></td>
						<td class="notification-since"><?php bp_the_notification_time_since(); ?></td>
						<td class="notification-actions"><?php bp_the_notification_action_links(); ?></td>
					</tr>

				<?php endwhile; ?>

			</tbody>
		</table>

	<?php else : ?>

		<?php 
			if ( function_exists( 'bp_nouveau_user_feedback' ) ) {
				bp_nouveau_user_feedback( 'member-notifications-none' );
			} else {
				
				/** This user notice will render when youzify is active along with BuddyPress and no new notifications are there. */
				?>
				<div class="bpsp-flex-container">
					<div><i class="fa fa-exclamation-circle bpsp-fa-icon"></i></div>
					<p><?php echo esc_html__( 'This member has no unread notifications.', 'shortcodes-for-buddypress' ); ?></p>
				</div>
				<?php
			}
		?>

	<?php endif; ?>
</div>
