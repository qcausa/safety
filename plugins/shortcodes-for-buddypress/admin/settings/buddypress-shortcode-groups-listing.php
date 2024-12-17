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
				<?php echo esc_html( 'The [groups-listing] shortcode allows you to display listing of groups in posts/pages.' ); ?>
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
							<?php esc_html_e( 'type', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Shorthand for certain orderby/order combinations. "newest", "active", "popular", "alphabetical", "random". When present, will override orderby and order params.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - alphabetical', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Does NOT affect query. May change the reported number of total groups found, but not the actual number of found groups. Default: false.', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'page_arg', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Query argument used for pagination.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - grpage', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'The container class of group listing loop.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - group', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group IDs. Results will exclude the listed groups.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'update_meta_cache', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Whether to fetch groupmeta for queried groups.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - bool', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - true', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group IDs. Results will be limited to groups within the list.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'meta_query', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'An array of meta_query conditions.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'orderby', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Property to sort by. "date_created", "last_activity", "total_member_count", "name", "random".', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - last_activity', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'search_terms', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'If provided, only groups whose names or descriptions match the search terms will be returned.', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'slug', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'If provided, only the group with the matching slug will be returned.', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'user_id', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'If provided, results will be limited to groups of which the specified user is a member.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - int', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'group_type', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group types to limit results to.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|array', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'group_type__in', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group types to limit results to.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|array', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label for="group_types_search">
							<?php esc_html_e( 'group_type__not_in', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group types that will be excluded from results.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - string|array', 'shortcodes-for-buddypress' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Whether to include hidden groups in results.', 'shortcodes-for-buddypress' ); ?></p>
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
							<?php esc_html_e( 'parent_id', 'shortcodes-for-buddypress' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Array or comma-separated list of group IDs. Results will include only child groups of the listed groups.', 'shortcodes-for-buddypress' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<label class="bupr-switch">
						<p class="description"><?php esc_html_e( 'Type - array|string', 'shortcodes-for-buddypress' ); ?></p>
						<p class="description"><?php esc_html_e( 'Default - null', 'shortcodes-for-buddypress' ); ?></p>
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
