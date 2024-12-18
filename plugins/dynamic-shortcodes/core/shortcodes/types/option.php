<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\PermissionsError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Option extends BaseShortcode {
	/**
	 * @return string
	 */
	public static function get_option_error_msg() {
		return esc_html__( 'The option is not in whitelist. You should use a Power Shortcode to show this option in safe contexts', 'dynamic-shortcodes' );
	}

	public static function get_shortcode_types( $context ) {
		return [
			'option',
		];
	}

	const OPTION_WHITELIST = [
		'siteurl' => true,
		'home' => true,
		'blogname' => true,
		'blogdescription' => true,
		'start_of_week' => true,
		//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		'posts_per_page' => true,
		'date_format' => true,
		'time_format' => true,
		'timezone_string' => true,
		'sticky_posts' => true,
		'www_user_roles' => true,
		'thumbnail_size_h' => true,
		'thumbnail_size_w' => true,
		'users_can_register' => true,
		'avatar_default' => true,
	];

	public function evaluate() {
		$this->arity_check( 1, 2 );
		$this->init_keyargs( [] );
		$key     = $this->get_arg( 0, 'string' );
		$default = false;
		if ( $this->get_args_count() === 2 ) {
			$default = $this->get_arg( 1 );
		}
		if ( ! isset( self::OPTION_WHITELIST[ $key ] ) && ! $this->has_all_privileges() ) {
			$msg = self::make_priv_err_message( $this->type );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}
		return get_option( $key, $default );
	}
}
