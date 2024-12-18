<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Metabox extends Post {
	public function evaluate() {
		$this->ensure_plugin_dependencies( [ 'metabox' ] );
		return parent::evaluate();
	}

	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'metabox',
		];
	}
}
