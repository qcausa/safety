<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Conditions extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'if',
			'lt',
			'gt',
			'le',
			'ge',
			'eq',
			'eqv',
			'and',
			'or',
			'not',
			'switch',
			'lit',
			'regex-match',
		];
	}

	public function evaluate_arg_in_scope( $i ) {
		$local_env = $this->unit_interpreter->local_env;
		$local_env->open_scope();
		try {
			if ( $this->want_string() ) {
				return $this->get_arg_as_string( $i );
			} else {
				return $this->get_arg( $i );
			}
		} finally {
			$local_env->close_scope();
		}
	}

	public function lit() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		$arg = strtolower( $this->get_literal_arg( 0 ) );
		switch ( $arg ) {
			case 'true':
				return true;
			case 'false':
				return false;
			case 'null':
				return null;
			default:
				$this->evaluation_error( esc_html__( 'Invalid argument', 'dynamic-shortcodes' ) );
		}
	}

	public function not() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		return ! $this->get_arg( 0 );
	}

	public function evaluate() {
		switch ( $this->type ) {
			case 'if':
				return $this->evaluate_if();
			case 'lit':
				return $this->lit();
			case 'and':
				return $this->and();
			case 'or':
				return $this->or();
			case 'switch':
				return $this->switch();
			case 'not':
				return $this->not();
			case 'regex-match':
				return $this->match();
		}
		return $this->evaluate_relationals();
	}

	public function match() {
		$this->arity_check( 2, 2 );
		$this->init_keyargs( [] );
		$pattern = $this->get_arg( 0, 'string' );
		$string  = $this->get_arg( 1, 'string' );
		return (bool) preg_match( '/' . $pattern . '/', $string );
	}

	public function switch() {
		$this->should_not_sanitize();
		$this->arity_check( 3 );
		$this->init_keyargs( [] );
		$value = $this->get_arg( 0 );
		$this->shift();
		if ( $this->want_string() ) {
			$get_arg = [ $this, 'get_arg_as_string' ];
		} else {
			$get_arg = [ $this, 'get_arg' ];
		}
		while ( 1 ) {
			if ( $this->get_args_count() === 0 ) {
				$this->evaluation_error( esc_html__( 'No match found, you should add a default', 'dynamic-shortcodes' ) );
			}
			if ( $this->get_args_count() === 1 ) {
				return $get_arg( 0 );
			}
			if ( $this->get_arg( 0 ) === $value ) {
				return $get_arg( 1 );
			}
			$this->shift( 2 );
		}
		throw new \Exception( 'unreachable' );
	}

	public function and() {
		$this->init_keyargs( [] );
		while ( $this->get_args_count() > 0 ) {
			if ( ! $this->get_arg( 0 ) ) {
				return false;
			}
			$this->shift();
		}
		return true;
	}

	public function or() {
		$this->init_keyargs( [] );
		while ( $this->get_args_count() > 0 ) {
			if ( $this->get_arg( 0 ) ) {
				return true;
			}
			$this->shift();
		}
		return false;
	}

	public function evaluate_relationals() {
		$this->init_keyargs( [] );
		$this->arity_check( 2 );
		switch ( $this->type ) {
			case 'lt':
				$cmp = function ( $a, $b ) {
					return $a < $b;
				};
				break;
			case 'gt':
				$cmp = function ( $a, $b ) {
					return $a > $b;
				};
				break;
			case 'le':
				$cmp = function ( $a, $b ) {
					return $a <= $b;
				};
				break;
			case 'ge':
				$cmp = function ( $a, $b ) {
					return $a >= $b;
				};
				break;
			case 'eq':
				$cmp = function ( $a, $b ) {
					return $a === $b;
				};
				break;
			case 'eqv':
				$cmp = function ( $a, $b ) {
					//phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					return $a == $b;
				};
				break;
		}
		$a = $this->get_arg( 0 );
		$this->shift();
		while ( $this->get_args_count() > 0 ) {
			$b = $this->get_arg( 0 );
			$this->shift();
			if ( ! $cmp( $a, $b ) ) {
				return false;
			}
			$a = $b;
		}
		return true;
	}


	public function evaluate_if() {
		$this->should_not_sanitize();
		$this->arity_check( 2 );
		$this->init_keyargs( [] );
		$count = $this->get_args_count();
		for ( $i = 0; $i < $count; $i += 2 ) {
			if ( $i === $count - 1 ) {
				// last, return the else.
				return $this->evaluate_arg_in_scope( $i );
			}
			if ( $this->get_arg( $i ) ) {
				// condition is satisfied, return the evaluated value of the next arg:
				return $this->evaluate_arg_in_scope( $i + 1 );
			}
		}
		// no conditions satisfied and no else, return '':
		return '';
	}
}
