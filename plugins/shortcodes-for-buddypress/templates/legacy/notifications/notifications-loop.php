<?php
/**
 * BuddyPress - Members Notifications Loop
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 3.0.0
 */

global $notifications_atts;


$user_id = ( isset( $notifications_atts['user_id'] ) && $notifications_atts['user_id'] != '' ) ? : get_current_user_id();
$args    = array(
	'user_id'    => $user_id,
	'is_new'     => true,
	'page'       => $notifications_atts['page'],
	'per_page'   => $notifications_atts['per_page'],
	'max'        => $notifications_atts['per_page'],
	'sort_order' => $notifications_atts['order'],
);

add_filter(
	'bp_current_action',
	function( $current_action ) {
		return 'unread';
	}
);
?>
<div id="buddypress" class="<?php echo esc_attr( $notifications_atts['container_class'] ); ?>">

	<?php if ( bp_has_notifications( $args ) ) : ?>

		<h2 class="bp-screen-reader-text">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Unread notifications', 'shortcodes-for-buddypress' );
		?>
		</h2>
		<table class="notifications">
			<thead>
				<tr>
					<th class="icon"></th>
					<th class="title"><?php esc_html_e( 'Notification', 'buddypress' ); ?></th>
					<th class="date"><?php esc_html_e( 'Date Received', 'buddypress' ); ?></th>
					<th class="actions"><?php esc_html_e( 'Actions', 'buddypress' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>

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

			<?php bp_get_template_part( 'members/single/notifications/feedback-no-notifications' ); ?>

		<?php endif; ?>
</div>
