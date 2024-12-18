<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Toolset extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'toolset',
		];
	}

	public function evaluate() {
		$this->ensure_plugin_dependencies( [ 'toolset' ] );
		$this->arity_check( 1, 1 );
		$this->init_keyargs(
			[
				'id' => [],
				'format' => [ 'fmt' ], // @format without argument is normal formatting
									   // @format=html for html formatting
			]
		);
		$meta = $this->get_arg( 0, 'string' );

		$id = get_the_ID();
		if ( $this->has_keyarg( 'id' ) ) {
			$id = $this->get_keyarg( 'id' );
		}

		$output = 'raw';
		$format = $this->get_keyarg_default( 'format', false );
		if ( $format === true ) {
			$output = 'normal';
		} elseif ( $format === 'html' ) {
			$output = 'html';
		}

		if ( $this->has_keyarg( 'id' ) && get_post( $id ) && ! is_post_publicly_viewable( $post ) ) {
			$this->ensure_all_privileges();
		}

		if ( 'publish' !== get_post_status( $id ) && ! $this->has_all_privileges() ) {
			$msg = esc_html__( 'Trying to access a post that is not published without privileges' );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( $msg );
		}
		/** @phpstan-ignore-next-line */
		return types_render_field(
			$meta,
			[
				'output' => $output,
				'post_id' => $id,
			]
		);
	}
}
