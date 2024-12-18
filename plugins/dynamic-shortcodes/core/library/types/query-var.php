<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class QueryVar extends BaseLibrary {

	protected static function get_contextual_fields_list() {
		global $wp_query;

		if ( ! is_array( $wp_query->query_vars ) ) {
			return [];
		}

		return array_keys( $wp_query->query_vars );
	}
}
