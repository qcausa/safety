<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Archive extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'title',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'title-prefix',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'description',
			],
			'force_result_types' => [],
		],
	];
}
