<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Args extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'args',
			'keyargs',
		];
	}
	public function evaluate() {
		$interpreter_env = &$this->interpreter_env;
		$this->arity_check( 0, 0 );
		$this->init_keyargs( [] );
		if ( ! isset( $interpreter_env['power_args'] ) ) {
			throw new EvaluationError( esc_html__( 'The args shortcode can only be used within a Power Shortcode', 'dynamic-shortcodes' ) );
		}
		if ( $this->type === 'args' ) {
			return $interpreter_env['power_args'];
		} else {
			return $interpreter_env['power_keyargs'];
		}
	}
}
