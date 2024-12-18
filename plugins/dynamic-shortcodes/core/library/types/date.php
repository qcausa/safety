<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Date extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'now',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'd/m/Y',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i:s',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'l',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'U',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'F',
			],
		],
		[
			'args' => [
				'now',
			],
			'keyargs' => [
				'format' => 'Y',
			],
		],
		[
			'args' => [
				'+10 seconds',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i:s',
			],
		],
		[
			'args' => [
				'+10 minutes',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i:s',
			],
		],
		[
			'args' => [
				'+1 hour',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i:s',
			],
		],
		[
			'args' => [
				'+1 day',
			],
			'keyargs' => [
				'format' => 'd/m/Y H:i:s',
			],
		],
		[
			'args' => [
				'+2 days',
			],
			'keyargs' => [
				'format' => 'd/m/Y',
			],
		],
		[
			'args' => [
				'+1 week',
			],
			'keyargs' => [
				'format' => 'd/m/Y',
			],
		],
		[
			'args' => [
				'+1 month',
			],
			'keyargs' => [
				'format' => 'd/m/Y',
			],
		],
		[
			'args' => [
				'+1 year',
			],
			'keyargs' => [
				'format' => 'd/m/Y',
			],
		],
	];
}
