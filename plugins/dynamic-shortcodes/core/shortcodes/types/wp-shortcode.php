<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class WpShortcode extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'wp-shortcode',
		];
	}

	public function evaluate() {
		$this->arity_check( 1, 2 );
		$this->init_keyargs( [], [], [], true );
		$this->should_not_sanitize();
		$shortcode  = $this->get_literal_arg( 0 );
		$attributes = [];
		foreach ( $this->keyargs as $k => $v ) {
			if ( ! BaseShortcode::is_keyarg_special( $k ) ) {
				if ( $v === true ) {
					$v = '';
				} else {
					$v = $this->unit_interpreter->evaluate_value( $v, $this->interpreter_env, true, true );
				}
				$attributes[ $k ] = $v;
			}
		}
		$content = null;
		if ( $this->get_args_count() === 2 ) {
			$content = $this->get_arg_as_string( 1, true );
		}
		global $shortcode_tags;
		if ( ! isset( $shortcode_tags[ $shortcode ] ) ) {
			return '';
		}
		$callback = $shortcode_tags[ $shortcode ];
		return $callback( $attributes, $content, $shortcode );
	}
}
