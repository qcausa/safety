<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class ArrayShortcode extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'array',
		];
	}

	public function evaluate() {
		$this->init_keyargs( [], [], [], true );
		$arr  = array_map(
			function ( $arg ) {
				return $this->unit_interpreter->evaluate_value( $arg, $this->interpreter_env );
			},
			$this->args
		);
		$arr += array_map(
			function ( $arg ) {
				return $this->unit_interpreter->evaluate_value( $arg, $this->interpreter_env );
			},
			$this->keyargs
		);
		return $arr;
	}
}
