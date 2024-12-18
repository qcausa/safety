<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Post extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'title',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'ID',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'featured-image-id',
			],
			'force_result_types' => [ 'media-id' ],
		],
		[
			'args' => [
				'date',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'modified-date',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'author',
			],
			'force_result_types' => [ 'user-id' ],
		],
		[
			'args' => [
				'permalink',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'slug',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'status',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'type',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'type-label',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'type-label-plural',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'comment-count',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'terms',
			],
		],
		[
			'args' => [
				'parent-id',
			],
			'force_result_types' => [ 'post-id' ],
		],
		[
			'args' => [
				'content',
			],
			'show_in_demo' => false,
		],
		[
			'args' => [
				'get-the-content',
			],
			'show_in_demo' => false,
		],
	];

	protected static function get_contextual_fields_list() {
		$post_meta = get_post_meta( get_the_ID() );

		if ( ! is_array( $post_meta ) ) {
			return [];
		}

		return array_keys( $post_meta );
	}

	/**
	 * @return array<string>
	 */
	protected static function get_hidden_fields() {
		return [
			'_thumbnail_id',
			'dce_widgets',
			'dyncontel_elementor_templates',
			'_elementor_data',
			'_elementor_pro_version',
			'_elementor_page_assets',
			'_elementor_version',
			'_elementor_template_type',
			'_elementor_page_settings',
			'to_ping',
			'pinged',
			'filter',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'show_admin_bar_front',
			'wp_capabilities',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'wp_dashboard_quick_press_last_post_id',
			'wp_elementor_connect_common_data',
			'session_tokens',
			'submitted_on_id',
			'submitted_by_id',
		];
	}

	/**
	 * @return array<string>
	 */
	protected static function get_prefixes_fields_to_hide() {
		return [
			'post_',
		];
	}
}
