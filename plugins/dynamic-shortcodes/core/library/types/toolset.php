<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Toolset extends Post {

	const PREDEFINED_FIELDS = [];

	protected static function get_contextual_fields_list() {
		$toolset_fields = [];

		$all_fields = wpcf_admin_fields_get_fields();

		if ( ! is_array( $all_fields ) ) {
			return [];
		}

		foreach ( $all_fields as $field_slug => $field ) {
			$toolset_fields[ $field_slug ] = '';
		}

		return array_keys( $toolset_fields );
	}
}
