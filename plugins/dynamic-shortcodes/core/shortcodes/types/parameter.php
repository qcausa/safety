<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Parameter extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'param-get',
			'param-post',
		];
	}

	/**
	 * Evaluate
	 *
	 * @return mixed
	 */
	public function evaluate() {
		$this->init_keyargs( [] );
		$this->arity_check( 1, 1 );
		$var = $this->get_arg( 0, 'string' );

		switch ( $this->type ) {
			case 'param-get':
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $_GET[ $var ] ?? null;
			case 'param-post':
				//phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $_POST[ $var ] ?? null;
		}
		// phpstan
		return '';
	}
}
