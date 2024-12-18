<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library;

use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\ShortcodeParser;
use DynamicShortcodes\Core\Shortcodes\Composer;

abstract class BaseLibrary {

	const PREDEFINED_FIELDS = [];

	final public static function get_fields_list() {
		$fields = array_merge( static::get_predefined_fields_list(), static::get_contextual_fields_list() );

		return array_filter(
			$fields,
			function ( $field ) {
				return isset( $field ) && static::field_is_not_hidden( $field );
			}
		);
	}

	final public static function get_fields_composer() {
		$fields = array_merge( static::get_predefined_fields_composer(), static::get_contextual_fields_composer() );

		return array_filter(
			$fields,
			function ( $field ) {
				return isset( $field['args'][0] ) && static::field_is_not_hidden( $field['args'][0] );
			}
		);
	}

	/**
	 * @param string $field
	 * @return boolean
	 */
	final public static function field_is_not_hidden( string $field ) {
		$underscore_field_accepted = [ '_thumbnail_id' ];
		if ( '_' === $field[0] && ! in_array( $field, $underscore_field_accepted, true ) ) {
			return false;
		}
		if ( ! empty( static::get_hidden_fields() ) && in_array( $field, static::get_hidden_fields(), true ) ) {
			return false;
		}

		if ( ! empty( static::get_prefixes_fields_to_hide() ) ) {
			foreach ( static::get_prefixes_fields_to_hide() as $prefix ) {
				if ( substr( $field, 0, strlen( $prefix ) ) === $prefix ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @return array<string>
	 */
	protected static function get_hidden_fields() {}

	/**
	 * @return array<string>
	 */
	protected static function get_prefixes_fields_to_hide() {}

	final protected static function get_predefined_fields_list() {
		$keys = [];
		foreach ( static::PREDEFINED_FIELDS as $field ) {
			$keys[] = $field['args'][0];
		}
		return $keys;
	}

	final protected static function get_predefined_fields_composer() {
		// predefined fields are yet formatted for composer
		return static::PREDEFINED_FIELDS;
	}

	/**
	 * It retrieves all fields according to their type.
	 * For instance in the 'post' type, it must retrieve all the post fields of the current ID
	 *
	 * @return array<string>
	 */
	protected static function get_contextual_fields_list(){}

	final protected static function get_contextual_fields_composer() {
		$list = static::get_contextual_fields_list();

		if ( empty( $list ) ) {
			return [];
		}
		$fields = [];
		foreach ( $list as $field ) {
			$fields[]['args'] = [ $field ];
		}
		return $fields;
	}
}
