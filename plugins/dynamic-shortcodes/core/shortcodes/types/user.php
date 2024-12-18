<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\PermissionsError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class User extends BaseShortcode {
	/**
	 * Get Shortcode Types
	 *
	 * @return array<string>
	 */
	public static function get_shortcode_types( $context ) {
		return [
			'user',
		];
	}

	/**
	 * @return string
	 */
	public static function get_keyarg_id_error_msg() {
		return esc_html__( 'The keyarg ID can only be used within a Power Shortcode', 'dynamic-shortcodes' );
	}

	public static function get_blacklist_error_msg() {
		return esc_html__( 'You can\'t use this meta because it\'s blacklisted', 'dynamic-shortcodes' );
	}

	public static function get_meta_privileges_error_msg() {
		return esc_html__( 'This meta can only be used within a Power Shortcode', 'dynamic-shortcodes' );
	}

	public static function get_keyarg_id_specified_arg_error_msg() {
		return esc_html__( 'The keyarg ID can\'t be used with this argument', 'dynamic-shortcodes' );
	}

	public static function get_user_id_not_exists_error_msg( $id ) {
		// translators: %s: User ID
		return sprintf( esc_html__( 'The User ID %s doesn\'t exist', 'dynamic-shortcodes' ), $id );
	}

	public static function get_meta_with_all_privileges() {
		return [
			'email',
			'login',
		];
	}

	const BLACKLIST = [
		'user_pass' => true,
		'pass' => true,
		'user_activation_key' => true,
		'activation_key' => true,
		'user_status' => true,
		'status' => true,
	];

	const USER_METAS_ALIAS = [
		'ID'      => 'ID',
		'id'      => 'ID',
		'email'   => 'user_email',
		'login'   => 'user_login',
		'registered' => 'user_registered',
		'roles' => 'roles',
		'display_name' => 'display_name',
		'display-name' => 'display_name',
		'nicename' => 'user_nicename',
	];

	const USER_METAS_FUNCTIONS = [
		'avatar-url'   => 'get_avatar_url',
		'avatar_url'   => 'get_avatar_url',
		'avatar'   => 'get_avatar',
		'is-logged-in' => 'is_user_logged_in',
	];

	public static function get_wrong_object_error_msg() {
		return esc_html__( 'The object is not an instance of WP_User', 'dynamic-shortcodes' );
	}

	/**
	 * Evaluate
	 *
	 * @return mixed
	 */
	public function evaluate() {
		$this->arity_check( 1, 1 );
		$meta = $this->get_arg( 0, 'string' );
		$id   = $this->get_id();
		$this->init_keyargs(
			[
				'id' => [],
				'object' => [],
			]
		);

		if ( $this->has_keyarg( 'id' ) && ! $this->has_all_privileges() ) {
			$msg = self::get_keyarg_id_error_msg();
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}

		if ( in_array( $meta, $this->get_meta_with_all_privileges(), true )
			&& ! $this->has_all_privileges() ) {
			$msg = self::get_meta_privileges_error_msg();
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}

		if ( isset( self::BLACKLIST[ $meta ] ) ) {
			$msg = self::get_blacklist_error_msg();
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( $msg );
		}
		if ( $this->has_keyarg( 'id' ) ) {
			if ( 'is-logged-in' === $meta ) {
				$msg = self::get_keyarg_id_specified_arg_error_msg();
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( $msg );
			}
			$id = $this->get_keyarg( 'id' );
			if ( ! get_userdata( $id ) ) {
				$msg = self::get_user_id_not_exists_error_msg( $id );
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( $msg );
			}
		}

		if ( $this->has_keyarg( 'object' ) ) {
			$object = $this->get_keyarg( 'object' );
			if ( $object instanceof \WP_User ) {
				$id = $object->term_id ?? '';
			} else {
				$msg = self::get_wrong_object_error_msg();
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( $msg );
			}
		}

		if ( $this->is_arg_an_identifier( 0 ) && isset( self::USER_METAS_ALIAS[ $meta ] ) ) {
			$meta = self::USER_METAS_ALIAS[ $meta ];

			$userdata = get_userdata( $id );
			if ( $userdata === false ) {
				return null;
			}
			return $userdata->$meta ?? null;
		}

		if ( $this->is_arg_an_identifier( 0 ) && isset( self::USER_METAS_FUNCTIONS[ $meta ] ) ) {
			$function = self::USER_METAS_FUNCTIONS[ $meta ];
			return call_user_func( $function, $object ?? $id );
		}

		$single = ! $this->get_bool_keyarg( 'all', false );

		return get_user_meta( $id, $meta, $single );
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return get_current_user_id();
	}
}
