<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\PermissionsError;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Server extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'server',
		];
	}

	/**
	 * Evaluate
	 *
	 * @return string
	 */
	public function evaluate() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );

		if ( ! $this->has_all_privileges() ) {
			$msg = self::make_priv_err_message( $this->type );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}

		$var = $this->get_arg( 0, 'string' );
		return $_SERVER[ $var ];
	}
}
