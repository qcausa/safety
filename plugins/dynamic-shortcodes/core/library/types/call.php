<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Call extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'wp_is_mobile',
			],
			'force_result_types' => [ 'boolean' ],
		],
		[
			'args' => [
				'get_bloginfo',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'time',
			],
			'force_result_types' => [ 'timespan' ],
		],
		[
			'args' => [
				'uniqid',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'get_the_author_meta',
				'ID',
			],
			'force_result_types' => [ 'user-id' ],
		],
	];
}
