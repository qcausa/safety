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
				<?php echo esc_html( 'The [members-listing] shortcode allows you to display listing of members listing in posts/pages.' ); ?>
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
							<p class="description"><?php esc_html_e( 'Sort order. Accepts "active", "random", "newest", "popular", "online", "alphabetical".', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - active', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Page of results to display.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - int|bool', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Type - int|bool', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'page_arg', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'The string used as a query parameter in pagination links.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - upage', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'The class of conainer of member loop.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - members', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Exclude users from results by ID. Accepts an array, a single integer, a comma-separated list of IDs, or false (to disable this limiting).', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - array|string|bool', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Whether to fetch optional data, such as friend counts.', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'Limit results by a list of user IDs. Accepts an array, a single integer, a comma-separated list of IDs, or false (to disable this limiting). Accepts "active", "alphabetical","newest", or "random".', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - array|int|string|bool', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - false', 'shortcodes-for-buddypress' ); ?></p>
							</label>
						</div>
					</div>
				</div>
				<div class="form-table">
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="group_types_search">
								<?php esc_html_e( 'meta_value', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'When used with meta_key, limits results by the a matching usermeta value.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - string', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - mixed', 'shortcodes-for-buddypress' ); ?></p>
							</label>
						</div>
					</div>
				</div>
				<div class="form-table">
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="group_types_search">
								<?php esc_html_e( 'meta_key', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Limit results by the presence of a usermeta key.', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'search_terms', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Limit results by a search term', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'user_ids', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'An array or comma-separated list of IDs, or false (to disable this limiting).', 'shortcodes-for-buddypress' ); ?></p>
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
							<p class="description"><?php esc_html_e( 'If provided, results are limited to the friends of the specified user.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - int', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - 0', 'shortcodes-for-buddypress' ); ?></p>
							</label>
						</div>
					</div>
				</div>
				<div class="form-table">
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="group_types_search">
								<?php esc_html_e( 'member_type', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Array or comma-separated list of member types to limit results to.', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'member_type__in', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Array or comma-separated list of member types to limit results to.', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'member_type__not_in', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Array or comma-separated list of member types to exclude from results.', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'include_member_role', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Array or comma-separated list of member role to include in results.', 'shortcodes-for-buddypress' ); ?></p>
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
								<?php esc_html_e( 'exclude_member_role', 'shortcodes-for-buddypress' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Array or comma-separated list of member role to exclude in results.', 'shortcodes-for-buddypress' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="bupr-switch">
							<p class="description"><?php esc_html_e( 'Type - string|array', 'shortcodes-for-buddypress' ); ?></p>
							<p class="description"><?php esc_html_e( 'Default - ham_only', 'shortcodes-for-buddypress' ); ?></p>
							</label>
						</div>
					</div>
				</div>
		</div>
	</div>
</div>
