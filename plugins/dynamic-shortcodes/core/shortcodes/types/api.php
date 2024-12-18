<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Api extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'api',
			'build-url',
		];
	}

	public function evaluate() {
		switch ( $this->type ) {
			case 'api':
				return $this->evaluate_api();
			case 'build-url':
				return $this->evaluate_build_url();
		}
	}

	public function evaluate_build_url() {
		$this->arity_check( 1 );
		$this->init_keyargs( [], [], [], true );
		$base_url     = $this->get_arg_as_string( 0, false );
		$query_params = [];
		foreach ( $this->keyargs as $k => $v ) {
			if ( ! BaseShortcode::is_keyarg_special( $k ) ) {
				if ( $v === true ) {
					// true is the empty keyarg:
					$v = '';
				} else {
					$v = $this->unit_interpreter->evaluate_value( $v, $this->interpreter_env, true );
				}
				$query_params[ $k ] = $v;
			}
		}
		$query_string = http_build_query( $query_params );

		if ( ! empty( $query_string ) ) {
			$url = $base_url . ( strpos( $base_url, '?' ) === false ? '?' : '&' ) . $query_string;
		} else {
			$url = $base_url;
		}
		return $url;
	}

	public function evaluate_api() {
		$this->should_not_sanitize();
		$this->arity_check( 1 );
		$this->init_keyargs(
			[
				'body' => [],
				'headers' => [],
				'timeout' => [],
				'post' => [],
				'json' => [],
				'no-decode' => [],
			],
			[],
			[ 'body' => [ 'post' ] ],
		);

		$this->ensure_all_privileges();
		$url     = $this->get_arg( 0, 'string' );
		$timeout = $this->get_keyarg_default( 'timeout', 30, 'integer' );
		$headers = $this->get_keyarg_default( 'headers', [], 'array' );
		$decode  = ! $this->get_bool_keyarg( 'no-decode' );
		$args    = [
			'timeout' => $timeout,
			'headers' => $headers,
		];

		if ( $this->get_bool_keyarg( 'post' ) ) {
			$body = $this->get_keyarg_default( 'body', null );
			if ( $body !== null ) {
				if ( is_array( $body ) ) {
					$args['body'] = wp_json_encode( $body );
					if ( $args['body'] === false ) {
						$this->evaluation_error( 'Array could not be converted to json' );
					}
				} else {
					$args['body'] = $body;
				}
			}
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			$this->unit_interpreter->manager->add_error( $error_msg, $this->str );
			return '';
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $decode ) {
			return $body;
		}
		$res = json_decode( $body, true );
		if ( $res === null ) {
			$msg = esc_html__( 'JSON decoding failed. API request result body is automatically converted to JSON, if result is not a JSON use the @no-decode keyarg.' );
			$this->unit_interpreter->manager->add_error( $msg, $this->str . "\n\nResult:\n\n" . $body );
		}
		return $res;
	}
}
