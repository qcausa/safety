<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

class Author extends User {

	protected static function get_contextual_fields_list() {
		$author_id = intval( get_the_author_meta( 'ID' ) );
		$user_meta = get_user_meta( $author_id );

		if ( is_array( $user_meta ) ) {
			return array_keys( $user_meta );
		}

		return [];
	}
}
