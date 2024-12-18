<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Breakdance;

class Manager {
	public function __construct() {
		add_action( 'init', [ self::class, 'register_fields' ] );
	}

	public static function register_fields() {
		// Check if Breakdance is installed and class/function exists
		if ( ! function_exists( '\Breakdance\DynamicData\registerField' ) || ! class_exists( '\Breakdance\DynamicData\Field' ) ) {
			return;
		}
		\Breakdance\DynamicData\registerField( new DynamicData\DataString() );
	}
}
