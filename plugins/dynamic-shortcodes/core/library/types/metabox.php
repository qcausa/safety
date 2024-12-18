<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Metabox extends Post {

	const PREDEFINED_FIELDS = [];

	protected static function get_contextual_fields_list() {
		$object_fields = rwmb_get_object_fields( get_the_ID() );

		if ( ! is_array( $object_fields ) ) {
			return [];
		}

		return array_keys( $object_fields );
	}
}
