<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class User extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'ID',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'login',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'email',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'is-logged-in',
			],
			'force_result_types' => [ 'boolean' ],
		],
		[
			'args' => [
				'registered',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'roles',
			],
			'force_result_types' => [ 'array' ],
		],
		[
			'args' => [
				'avatar-url',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'avatar',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'display_name',
			],
			'force_result_types' => [ 'string' ],
		],
	];

	protected static function get_contextual_fields_list() {
		$user_meta = get_user_meta( get_current_user_ID() );

		if ( ! is_array( $user_meta ) ) {
			return [];
		}

		return array_keys( $user_meta );
	}

	/**
	 * @return array<string>
	 */
	protected static function get_hidden_fields() {
		return [
			'metaboxhidden_dashboard',
			'rich_editing',
			'announcements_user_counter',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'locale',
			'show_welcome_panel',
			'wp_dashboard_quick_press_last_post_id',
			'wp_user-settings',
			'wp_persisted_preferences',
			'elementor_admin_notices',
			'wp_elementor_pro_enable_notes_notifications',
			'edit_site-review_filters',
			'wp_elementor_connect_common_data',
			'use_ssl',
			'show_admin_bar_front',
			'dismissed_wp_pointers',
			'session_tokens',
			'wp_capabilities',
		];
	}

	/**
	 * @return array<string>
	 */
	protected static function get_prefixes_fields_to_hide() {
		return [
			'dce_',
			'dsh_',
			'dynamic_shortcodes_',
			'elementor_',
			'closedpostboxes_',
		];
	}
}
