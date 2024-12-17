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
				<?php echo esc_html( 'BuddyPress Activity Shortcode allows you to embed BuddyPress activities in posts/pages using shortcodes.' ); ?>
				<br>
				<?php echo esc_html( 'The [activity-listing] is work as same as site-wide activity page work.' ); ?>
			</p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'title', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'The title for the activities. Title will display on the top of the activities.', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'page', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Which page of results to fetch. Using page=1 without per_page will result in no pagination.', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Number of results per page.', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Maximum number of results to return.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false ( unlimited )', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'count_total', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'An additional DB query is run to count the total activity items for the query.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - true', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'sort', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Sort activies by "ASC" or "DESC"', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'exclude', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array of activity IDs to exclude.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'in', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array of IDs to limit query by (IN). "in" is intended to be used in conjunction with other filter parameters.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'include', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array of exact activity IDs to query. Providing an "include" array will override all other filters passed in the argument array. When viewing the permalink page for a single activity item, this value defaults to the ID of that item.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'scope', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use a BuddyPress pre-built filter. - "just-me" - retrieves items belonging only to a user; this is equivalent to passing a "user_id" argument. - "friends" - retrieves items belonging to the friends of a user. - "groups" - retrieves items belonging to groups to which a user belongs to. - "favorites" - retrieves a users favorited activity items. - "mentions" - retrieves items where a user has received an @-mention.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'object', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Filters by the `component` column in the database, which is generally the component ID in the case of BuddyPress components, or the plugin slug in the case of plugins.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'user_id', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'The ID(s) of user(s) whose activity should be fetched.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'action', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Filters by the `type` column in the database.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|array|bool	', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'primary_id', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Filters by the `item_id` column in the database.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|array|bool	', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'secondary_id', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Filters by the `secondary_item_id` column in the database.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'offset', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Return only activity items with an ID greater than or equal to this one.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int|array|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'show_hidden', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Whether to show items marked hide_sitewide.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'spam', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Spam status. "ham_only", "spam_only", or false to show all activity regardless of spam status.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - ham_only', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'populate_extras', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Whether to pre-fetch the activity metadata for the queried items.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - true', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
