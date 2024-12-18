<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Identity extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'identity',
		];
	}

	public function evaluate() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		return $this->get_arg( 0 );
	}
}
