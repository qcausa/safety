<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Archive extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'archive',
		];
	}
	public function evaluate() {
		$interpreter_env = &$this->interpreter_env;
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );

		$arg = $this->get_arg( 0, 'string' );

		switch ( $arg ) {
			case 'title':
				add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				$title = get_the_archive_title();
				remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				return $title;
			case 'title-prefix':
				$prefix = '';
				add_filter(
					'get_the_archive_title_prefix',
					function ( $current_prefix ) use ( &$prefix ) {
						$prefix = preg_replace( '/[^\w\s]/', '', $current_prefix ); // Removes punctuation
						return $current_prefix;
					}
				);

				// Call the function only to trigger the filter
				get_the_archive_title();

				remove_filter(
					'get_the_archive_title_prefix',
					function ( $current_prefix ) use ( &$prefix ) {
						return $current_prefix;
					}
				);

				return $prefix;
			case 'description':
				return get_the_archive_description();
			default:
				$this->evaluation_error( "Invalid archive argument: $arg" );
		}
	}
}
