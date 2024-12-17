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
				<?php echo esc_html( 'The [notifications-listing] shortcode allows you to display listing of notifications in posts/pages.' ); ?>
			</p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'title', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'The title for the notification listing. Title will display on the top of the listing.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'order', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Sort notifications by "ASC" or "DESC"', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - DESC', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'page', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Page offset of results to return.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - 1', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'per_page', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Number of items to return per page of results.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - 20', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'max', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Does NOT affect query. May change the reported number of total notifications found, but not the actual number of found notifications. Default: false.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false(unlimited)', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'container_class', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'The container class of notification listing loop.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - notification', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
