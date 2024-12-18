<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\Helpers;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\PermissionsError;

class Call extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'call',
		];
	}

	public function evaluate() {
		if ( ! $this->has_all_privileges() ) {
			$msg = self::make_priv_err_message( $this->type );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}
		$this->arity_check( 1 );
		$this->init_keyargs( [] );
		$func  = $this->get_arg( 0 );
		$count = $this->get_args_count();
		$args  = [];
		for ( $i = 1; $i < $count; $i++ ) {
			$args[] = $this->get_arg( $i );
		}
		return Helpers::call_catch_errors(
			'user call inside shortcode call',
			function () use ( $func, $args ) {
				return call_user_func_array( $func, $args );
			}
		);
	}
}
