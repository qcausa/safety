<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class Option extends BaseLibrary {

	protected static function get_contextual_fields_list() {
		$all_options = wp_load_alloptions();

		if ( ! is_array( $all_options ) ) {
			return [];
		}

		return array_keys( $all_options );
	}

	/**
	 * @return array<string>
	 */
	protected static function get_prefixes_fields_to_hide() {
		return [
			'dsh_',
			'dce_',
			'dynamic_shortcodes_',
			'dynamic_content_for_elementor_',
		];
	}
}
