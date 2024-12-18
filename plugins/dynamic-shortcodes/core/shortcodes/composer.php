<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

class Composer {
	public static function compose_value( $val ) {
		if ( is_string( $val ) ) {
			if (
				preg_match( '/^' . ShortcodeParser::REGEX_IDENTIFIER . '$/', $val ) ||
				preg_match( '/^\d+$/', $val )
			) {
				return $val;
			}
			return '"' . addslashes( $val ) . '"';
		}
		if ( is_numeric( $val ) ) {
			return $val;
		}
		$res = '';
		switch ( $val['type'] ) {
			case 'shortcode':
				$res = self::compose_shortcode( $val['data'] );
				break;
			case 'template':
				$res = '[' . self::compose( $val['data'] ) . ']';
				break;
		}
		return $res;
	}

	public static function compose_keyargs( $keyargs ) {
		$keyargs = array_map(
			function ( $val, $key ) {
				if ( $val === null ) {
					return $key;
				}
				$val = self::compose_value( $val );
				return "$key=$val";
			},
			$keyargs,
			array_keys( $keyargs )
		);
		return implode( ' ', $keyargs );
	}

	public static function compose_filters( $filters ) {
		$filters = array_map(
			function ( $filter ) {
				$args = '';
				if ( isset( $filter['args'] ) ) {
					$args   = array_map(
						function ( $arg ) {
							return self::compose_value( $arg );
						},
						$filter['args']
					);
					  $args = '(' . implode( ', ', $args ) . ')';
				}
				return ' ' . $filter['type'] . ' ' . $filter['name'] . $args;
			},
			$filters
		);
		return implode( '', $filters );
	}

	public static function compose_shortcode( $tok ) {
		$args    = array_map(
			function ( $arg ) {
				return self::compose_value( $arg );
			},
			$tok['args']
		);
		$args    = implode( ' ', $args );
		$keyargs = '';
		if ( ! empty( $tok['keyargs'] ) ) {
			$keyargs = ' @ ' . self::compose_keyargs( $tok['keyargs'] );
		}
		$filters = '';
		if ( ! empty( $tok['filters'] ) ) {
			$filters = self::compose_filters( $tok['filters'] );
		}
		$fallback = '';
		if ( ! empty( $tok['fallback'] ) ) {
			$fallback = ' ? ' . self::compose_value( $tok['fallback'] );
		}
		$type = $tok['type'];
		return "{{$type}:$args$keyargs$filters$fallback}";
	}

	/**
	 * ? at the end of a key here indicates optionality.
	 *
	 * accepts an array of string and shortcodes, shortcodes are arrays with keys:
	 * type, args, keyargs?, filters? and fallback?
	 *
	 * keyargs is an associative array, if an element value is null it just
	 * inserts the key without the =.
	 *
	 * filters is an array of filters. Each filter has this keys: type (||,
	 * |-, or |), name and args?
	 *
	 * If instead of a simple string/number you want to insert a nested shortcode
	 * or template you pass an array that has keys type and data. type is
	 * either shortcode or template. data is the shortcode itself or an array of
	 * string with shortcodes.
	 */
	public static function compose( $elements ) {
		$composed = array_map(
			function ( $e ) {
				if ( is_string( $e ) ) {
					  return $e;
				}
				return self::compose_shortcode( $e );
			},
			$elements
		);
		return implode( '', $composed );
	}
}
