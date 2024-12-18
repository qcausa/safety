<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Arithmetic extends BaseShortcode {
	const TYPE_ERROR_MSG = 'An arithmetic operation arg was not numeric';
	const ZERO_ERROR_MSG = 'Division by zero';

	public static function get_shortcode_types( $context ) {
		return [
			'+',
			'-',
			'/',
			'*',
			'modulo',
			'mod',
		];
	}

	public function evaluate_modulo() {
		$this->arity_check( 2, 2 );
		try {
			return $this->get_arg( 0, 'numeric' ) % $this->get_arg( 1, 'numeric' );
		} catch ( \DivisionByZeroError $e ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( self::ZERO_ERROR_MSG );
		}
	}

	public function evaluate() {
		$op = $this->type;
		$this->init_keyargs( [] );
		if ( $op === 'modulo' || $op === 'mod' ) {
			return $this->evaluate_modulo();
		}
		$count = $this->get_args_count();
		if ( $count === 0 ) {
			$res = 0;
			if ( $op === '*' || $op === '/' ) {
				$res = 1;
			}
			return $res;
		}
		$res = $this->get_arg( 0, 'numeric' );
		for ( $i = 1; $i < $count; $i++ ) {
			$val = $this->get_arg( $i, 'numeric' );
			try {
				switch ( $op ) {
					case '+':
						$res += $val;
						break;
					case '-':
						$res -= $val;
						break;
					case '*':
						$res *= $val;
						break;
					case '/':
						$res /= $val;
						break;
				}
			} catch ( \TypeError $e ) {
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( self::TYPE_ERROR_MSG );
			} catch ( \DivisionByZeroError $e ) {
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( self::ZERO_ERROR_MSG );
			}
		}
		return $res;
	}
}
