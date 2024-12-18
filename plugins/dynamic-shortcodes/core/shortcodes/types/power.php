<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Settings\Manager as SettingsManager;

class Power extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'pw',
			'power',
		];
	}

	public function evaluate() {
		$this->should_not_sanitize();
		$this->arity_check( 1 );
		$this->init_keyargs(
			[
				'value!' => [],
			],
			[],
			[],
			true
		);
		$id         = $this->get_arg( 0, 'string' );
		$shortcodes = get_option( SettingsManager::OPTION_POWER_SHORTCODES, [] );
		if ( ! is_array( $shortcodes ) ) {
			throw new EvaluationError( 'Power Shortcodes error. Please try to save the Power Shortcodes again or contact support.' );
		}
		if ( ! isset( $shortcodes[ $id ] ) ) {
			$msg = esc_html( sprintf( 'The Power Shortcode %s was not found', $id ) );
			$this->evaluation_error( $msg );
		}
		$ast                           = $shortcodes[ $id ];
		$interpreter_env               = $this->interpreter_env;
		$interpreter_env['privileges'] = 'all';
		$args                          = array_slice( $this->args, 1 );
		$interpreter_env['power_args'] = array_map(
			function ( $arg ) {
				return $this->unit_interpreter->evaluate_value( $arg, $this->interpreter_env );
			},
			$args
		);
		$pkeyargs                      = [];
		foreach ( $this->keyargs as $k => $v ) {
			$pkeyargs[ $k ] = $this->unit_interpreter->evaluate_value( $v, $this->interpreter_env );
		}
		$interpreter_env['power_keyargs'] = $pkeyargs;
		if ( ! $this->get_bool_keyarg( 'value!' ) ) {
			return $this->unit_interpreter->evaluate_shortcodes( $ast, $interpreter_env );
		} else {
			$ast = array_filter(
				$ast,
				function ( $value ) {
					return ! is_string( $value );
				}
			);
			if ( count( $ast ) === 0 ) {
				$this->evaluation_error( esc_html__( 'The power shortcode referenced does not contain shortcodes.', 'dynamic-shortcodes' ) );
			}
			return $this->unit_interpreter->evaluate_shortcode( end( $ast ), false, true, $interpreter_env );
		}
	}
}
