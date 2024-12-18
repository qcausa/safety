<?php
// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Cache extends BaseShortcode {

	public static function get_shortcode_types( $context ) {
		return [
			'cache',
			'get-cache',
			'delete-cache',
		];
	}

	private static function strtoseconds( $str ) {
		$now    = time();
		$future = strtotime( $str, $now );
		return $future - $now;
	}

	private function evaluate_get() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		$key   = 'dsh_' . $this->get_arg( 0, 'string' );
		$value = get_transient( $key );
		if ( is_array( $value ) ) {
			$value = $value[0];
		}
		return $value;
	}

	private function evaluate_delete() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		$key = 'dsh_' . $this->get_arg( 0, 'string' );
		delete_transient( $key );
	}

	public function evaluate() {
		if ( $this->type === 'get-cache' ) {
			return $this->evaluate_get();
		} elseif ( $this->type === 'delete-cache' ) {
			return $this->evaluate_delete();
		}
		$this->arity_check( 2, 2 );
		$this->init_keyargs(
			[
				'expiration' => [ 'exp' ], // accept a string like 3 seconds, 2 days etc.
			]
		);
		$key        = 'dsh_' . $this->get_arg( 0, 'string' );
		$expiration = $this->get_keyarg_default( 'expiration', '1 day' );
		// clearing the cache is not an issue:
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$should_clear_cache = current_user_can( 'manage_options' ) &&
					   isset( $_GET['dsh-action'] ) &&
					   $_GET['dsh-action'] === 'clear-cache';
		//phpcs:enable
		if ( $should_clear_cache ) {
			delete_transient( $key );
		}
		$transient = get_transient( $key );
		if ( is_array( $transient ) ) {
			$value = $transient[0];
		} else {
			$value = $this->get_arg( 1 );
			set_transient( $key, [ $value ], self::strtoseconds( $expiration ) );
		}
		return $value;
	}
}
