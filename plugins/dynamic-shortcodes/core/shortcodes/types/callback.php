<?php
// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Callback extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [ $context['type'] ];
	}

	public function evaluate() {
		$this->should_not_sanitize();
		$this->init_keyargs( [], [], [], true );
		$obj     = $this;
		$args    = array_map(
			function ( $e ) use ( $obj ) {
				return $obj->unit_interpreter->evaluate_value( $e, $obj->interpreter_env );
			},
			$this->args
		);
		$keyargs = array_map(
			function ( $e ) use ( $obj ) {
				if ( $e === true ) {
					  // empty keyarg:
					  return $e;
				}
				return $obj->unit_interpreter->evaluate_value( $e, $obj->interpreter_env );
			},
			$this->keyargs
		);
		return $this->context['callback']( $args, $keyargs, $this->context['type'] );
	}
}
