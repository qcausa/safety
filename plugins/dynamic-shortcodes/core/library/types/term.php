<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Term extends BaseLibrary {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'name',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'id',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'slug',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'permalink',
			],
			'force_result_types' => [],
		],
	];

	protected static function get_contextual_fields_list() {
		$queried_object = get_queried_object();
		if ( ! $queried_object instanceof \WP_Term ) {
			return [];
		}
		$term_id = $queried_object->term_id;

		return array_keys( get_term_meta( $term_id ) );
	}
}
