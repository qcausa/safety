<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Loops extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'for',
			'loop',
		];
	}

	public function evaluate() {
		$this->should_not_sanitize();
		switch ( $this->type ) {
			case 'for':
				return $this->evaluate_for();
			case 'loop':
				return $this->evaluate_loop();
		}
		// Unreachable.
	}


	public function evaluate_loop() {
		$this->arity_check( 2, 2 );
		$this->init_keyargs(
			[
				'separator' => [ 'sep' ],
			]
		);
		// besides ids they can actualy also be posts objects:
		$post_ids        = $this->get_arg( 0, 'iterable' );
		$sep             = $this->get_keyarg_as_string( 'separator', '', 'string' );
		$me              = $this;
		$render_callback = function () use ( $me ) {
			return $me->get_arg_as_string( 1, true );
		};
		$error_callback  = function ( $msg ) use ( $me ) {
			$me->evaluation_error( $msg );
		};
		return self::loop_ids_with_callback(
			$post_ids,
			$sep,
			$render_callback,
			$this,
		);
	}

	public function evaluate_for() {
		$this->arity_check( 3, 4 );
		$this->init_keyargs(
			[
				'separator' => [ 'sep' ], // insert given string between each iteration.
			]
		);
		$local_env = $this->unit_interpreter->local_env;
		$id_key    = false;
		if ( $this->get_args_count() === 4 ) {
			$id_key = $this->get_literal_arg( 0 );
			$this->shift();
		}
		$id_val = $this->get_literal_arg( 0 );

		$array = $this->get_arg( 1 );
		if ( $array === '' || $array === null ) {
			$array = [];
		}
		if ( ! is_iterable( $array ) ) {
			$this->evaluation_error( self::make_type_err_message( gettype( $array ), 'iterable', 1 ) );
		}
		$buf = [];
		$sep = $this->get_keyarg_as_string( 'separator', '', 'string' );
		foreach ( $array as $key => $val ) {
			$local_env->open_scope();
			if ( $id_key !== false ) {
				$local_env->define_var( $id_key, $key );
			}
			$local_env->define_var( $id_val, $val );
			try {
				$buf[] = $this->get_arg_as_string( 2, true );
			} finally {
				$local_env->close_scope();
			}
		}
		return implode( $sep, $buf );
	}
}
