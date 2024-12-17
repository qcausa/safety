<?php
/**
 * Bp add group type general setting file.
 *
 * @since    1.0.0
 * @author   Wbcom Designs
 * @package  Bp_Add_Group_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $bp_grp_types;
?>
<div class="wbcom-tab-content">
	<div class="wbcom-wrapper-admin">
		<div class="wbcom-admin-title-section">
			<p class="wbcom-welcome-description">
				<?php echo esc_html( 'Shortcodes & Elementor Widgets For BuddyPress plugin allows you to embed BuddyPress Activities, BuddyPress Members and Groups in posts/pages using shortcodes.' ); ?>
			</p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'Activities', 'shortcodes-for-buddypress' ); ?>
						</label>
						<span class="bp-shortcode-read-more">
							<p class="description"><?php esc_html_e( 'BuddyPress Activity Shortcode allows you to embed BuddyPress activities in posts/pages using shortcodes. ', 'shortcodes-for-buddypress' ); ?></p>
							<a href="<?php echo esc_attr( 'https://docs.wbcomdesigns.com/docs/shortcode-for-buddypress-pro/activity-parameters/what-are-accepted-parameters-for-activity-shortcodes/' );?>" class="bpsh_read_more" target="_blank"><?php echo esc_html( ' read more' ); ?></a>
						</span>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( '[activity-listing]', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'Members', 'shortcodes-for-buddypress' ); ?>
						</label>
						<span class="bp-shortcode-read-more">
							<p class="description"><?php esc_html_e( 'BuddyPress Members Shortcode allows you to embed BuddyPress members in posts/pages using shortcodes.', 'shortcodes-for-buddypress' ); ?></p>
							<a href="<?php echo esc_attr( 'https://docs.wbcomdesigns.com/docs/shortcode-for-buddypress-pro/member-parameters/what-are-accepted-parameters-for-members-listing-shortcodes/' );?>" class="bpsh_read_more" target="_blank"><?php echo esc_html( 'read more' ); ?></a>
						</span>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( '[members-listing]', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'Groups', 'shortcodes-for-buddypress' ); ?>
						</label>
						<span class="bp-shortcode-read-more">
							<p class="description"><?php esc_html_e( 'BuddyPress Groups Shortcode allows you to embed BuddyPress groups in posts/pages using shortcodes.', 'shortcodes-for-buddypress' ); ?></p>
							<a href="<?php echo esc_attr( 'https://docs.wbcomdesigns.com/docs/shortcode-for-buddypress-pro/group-parameters/what-are-accepted-parameters-for-group-shortcodes/' );?>" class="bpsh_read_more" target="_blank"><?php echo esc_html( 'read more' ); ?></a>
						</span>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( '[groups-listing]', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'User Notification', 'shortcodes-for-buddypress' ); ?>
						</label>
						<span class="bp-shortcode-read-more">
							<p class="description"><?php esc_html_e( 'BuddyPress Notification Shortcode allows you to embed BuddyPress notificaition list in posts/pages using shortcodes.', 'shortcodes-for-buddypress' ); ?></p>
							<a href="<?php echo esc_attr( 'https://docs.wbcomdesigns.com/docs/shortcode-for-buddypress-pro/notification-parameters/what-are-accepted-parameters-for-users-notification-listing-shortcodes/' );?>" class="bpsh_read_more" target="_blank"><?php echo esc_html( 'read more' ); ?></a>
						</span>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( '[notifications-listing]', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
