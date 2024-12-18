<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\PermissionsError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Author extends User {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'author',
		];
	}

	/**
	 * @return int|string
	 */
	public function get_id() {
		return get_the_author_meta( 'ID' );
	}

	/**
	 * Evaluate
	 *
	 * @return string
	 */
	public function evaluate() {
		if ( ! $this->has_all_privileges() ) {
			$msg = self::make_priv_err_message( $this->type );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}

		return parent::evaluate();
	}
}
