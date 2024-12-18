<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Query extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'posts',
			],
			'force_result_types' => [ 'array-post-id' ],
		],
		[
			'args' => [
				'posts',
			],
			'keyargs' => [
				'posts-per-page' => '5',
			],
			'force_result_types' => [ 'array-post-id' ],
		],
		[
			'args' => [
				'users',
			],
			'keyargs' => [
				'role' => 'administrator',
			],
			'force_result_types' => [ 'array-user-id' ],
		],
		[
			'args' => [
				'users',
			],
			'filters' => [
				[
					'type' => '|',
					'name' => 'count',
				],
			],
			'force_result_types' => [ 'int' ],
		],
		[
			'args' => [
				'terms',
			],
			'force_result_types' => [ 'array-term-id' ],
		],
		[
			'args' => [
				'terms',
			],
			'keyargs' => [
				'hide_empty' => null,
			],
			'force_result_types' => [ 'array-term-id' ],
		],
		[
			'args' => [
				'terms',
			],
			'filters' => [
				[
					'type' => '|',
					'name' => 'count',
				],
			],
			'force_result_types' => [ 'int' ],
		],
	];
}
