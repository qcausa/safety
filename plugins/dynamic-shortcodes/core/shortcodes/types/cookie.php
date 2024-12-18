<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Cookie extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'cookie',
		];
	}

	/**
	 * Evaluate
	 *
	 * @return string
	 */
	public function evaluate() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		$var = $this->get_arg( 0, 'string' );

		return $_COOKIE[ $var ];
	}
}
