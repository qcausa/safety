<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Settings\Manager as SettingsManager;

class Vars extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'get',
			'set-default',
			'set',
			'let',
			'def',
			'define',
			'wp-shortcode',
		];
	}

	private function get() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		return $this->unit_interpreter->local_env->get_var( $this->get_literal_arg( 0 ) );
	}

	private function set_default() {
		$this->arity_check( 2, 2 );
		$this->init_keyargs( [] );
		$name = $this->get_literal_arg( 0 );
		if ( ! $this->unit_interpreter->local_env->has_var( $name ) ) {
			$this->unit_interpreter->local_env->set_var( $name, $this->get_arg( 1 ) );
		}
		return '';
	}

	private function set() {
		$this->arity_check( 2, 2 );
		$this->init_keyargs( [] );
		$this->unit_interpreter->local_env->set_var( $this->get_literal_arg( 0 ), $this->get_arg( 1 ) );
		return '';
	}

	private function define() {
		$this->arity_check( 1, 2 );
		$this->init_keyargs( [] );
		$val = null;
		if ( $this->get_args_count() === 2 ) {
			$val = $this->get_arg( 1 );
		}
		$this->unit_interpreter->local_env->define_var( $this->get_literal_arg( 0 ), $val );
		return '';
	}

	private function let() {
		$this->should_not_sanitize();
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [], [], [], true );
		$this->unit_interpreter->local_env->open_scope();
		try {
			foreach ( $this->keyargs as $k => $v ) {
				if ( ! BaseShortcode::is_keyarg_special( $k ) ) {
					if ( $v === true ) {
						// true is the empty keyarg:
						$v = null;
					} else {
						$v = $this->unit_interpreter->evaluate_value( $v, $this->interpreter_env );
					}
					$this->unit_interpreter->local_env->define_var( $k, $v );
				}
			}
			return $this->get_arg( 0 );
		} finally {
			$this->unit_interpreter->local_env->close_scope();
		}
	}

	public function evaluate() {
		switch ( $this->type ) {
			case 'get':
				return $this->get();
			case 'set':
				return $this->set();
			case 'set-default':
				return $this->set_default();
			case 'let':
				return $this->let();
			case 'def':
			case 'define':
				return $this->define();
			default:
				throw new \LogicException();
		}
	}
}
