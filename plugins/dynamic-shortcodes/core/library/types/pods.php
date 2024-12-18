<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Pods extends BaseLibrary {

	const PREDEFINED_FIELDS = [];

	protected static function get_contextual_fields_list() {
		global $post;

		$pods = pods( get_post_type(), get_the_ID() );

		if ( ! $pods->valid() ) {
			return [];
		}

		$pods_fields = [];

		$fields = $pods->fields();

		foreach ( $fields as $field_name => $field_options ) {
			$pods_fields[ $field_name ] = '';
		}
		$post_attributes = (array) $post;

		// add comments, because is on pods_fields and we want to remove it
		$post_attributes['comments'] = '';

		return array_keys( array_diff_key( $pods_fields, $post_attributes ) );
	}
}
