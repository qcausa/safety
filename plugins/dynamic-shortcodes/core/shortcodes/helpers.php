<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Shortcodes;

class Helpers {
	public static function call_catch_errors( $error_source, $callback ) {
		$msg = $error_source . ' error';
		try {
			return $callback();
		} catch ( \Throwable $e ) {
			if ( ! defined( 'DSH_TESTING' ) ) {
				//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $msg );
				//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $e->getMessage() );
			}
			if ( current_user_can( 'manage_options' ) ) {
				$msg .= ' (' . $e->getMessage() . ')';
			}
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( $msg );
		}
	}
	public static function current_user_is_at_least_contributor() {
		$user = wp_get_current_user();
		if ( in_array( 'contributor', (array) $user->roles, true ) ||
			 in_array( 'author', (array) $user->roles, true ) ||
			 in_array( 'editor', (array) $user->roles, true ) ||
			 in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		} else {
			return false;
		}
	}
}
