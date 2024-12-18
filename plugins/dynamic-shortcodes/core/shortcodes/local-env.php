<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

class LocalEnv {
	/**
	 * current values of variables
	 * @var array<string, mixed>
	 */
	private $var_cells = [];
	/**
	 * stacks of values of the shadowed variables
	 * @var array<string, array<mixed>>
	 */
	private $var_stacks = [];
	/**
	 * stack of scopes. A scope is a set that keeps tracks of the variables
	 * defined in that scope
	 * @var array<array<string, true>>
	 */
	private $scopes_stack = [];

	private function &current_scope() {
		if ( empty( $this->scopes_stack ) ) {
			throw new \RuntimeException( 'Scopes stack is empty' );
		}

		$last_index = count( $this->scopes_stack ) - 1;
		return $this->scopes_stack[ $last_index ];
	}

	private function push_var( $name ) {
		if ( isset( $this->var_cells[ $name ] ) ) {
			if ( isset( $this->var_stacks[ $name ] ) ) {
				$this->var_stacks[ $name ][] = $this->var_cells[ $name ];
			} else {
				$this->var_stacks[ $name ] = [ $this->var_cells[ $name ] ];
			}
		}
	}

	public function define_var( $name, $initial_value = null ) {
		if ( isset( $this->current_scope()[ $name ] ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( sprintf( 'var `%s` is already defined', $name ) );
		}
		$this->current_scope()[ $name ] = true;
		$this->push_var( $name );
		$this->var_cells[ $name ] = $initial_value;
	}

	public function has_var( $name ) {
		return array_key_exists( $name, $this->var_cells );
	}

	public function get_var( $name ) {
		if ( ! array_key_exists( $name, $this->var_cells ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( sprintf( 'undefined var `%s`', $name ) );
		}
		return $this->var_cells[ $name ];
	}

	public function set_var( $name, $value ) {
		$this->var_cells[ $name ] = $value;
	}

	public function open_scope() {
		$this->scopes_stack[] = [];
	}

	public function close_scope() {
		foreach ( $this->current_scope() as $name => $_ ) {
			if ( isset( $this->var_stacks[ $name ] ) ) {
				$this->var_cells[ $name ] = array_pop( $this->var_stacks[ $name ] );
				if ( empty( $this->var_stacks[ $name ] ) ) {
					unset( $this->var_stacks[ $name ] );
				}
			} else {
				unset( $this->var_cells[ $name ] );
			}
		}
		array_pop( $this->scopes_stack );
	}
}
