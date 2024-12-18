<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;

class Term extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'term',
		];
	}

	const TERM_FIELDS_ALIAS = [
		'ID'      => 'term_id',
		'id'      => 'term_id',
	];

	const TERM_FIELDS_FUNCTIONS = [
		'permalink'   => 'get_term_link',
		'link'   => 'get_term_link',
	];

	public static function get_wrong_object_error_msg() {
		return esc_html__( 'The object is not an instance of WP_Term', 'dynamic-shortcodes' );
	}

	public function evaluate() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs(
			[
				'id' => [],
				'object' => [],
			],
			[ [ 'id', 'object' ] ],
		);
		$term = $this->get_term_of_interest();
		if ( $term === null ) {
			return null;
		}

		$field_name = $this->get_arg( 0, 'string' );

		if ( isset( self::TERM_FIELDS_ALIAS[ $field_name ] ) ) {
			$field_name = self::TERM_FIELDS_ALIAS[ $field_name ];
		}

		$standard_fields = [ 'name', 'slug', 'term_id', 'description', 'count', 'taxonomy' ];

		if ( in_array( $field_name, $standard_fields, true ) ) {
			return $term->$field_name ?? null;
		}

		if ( isset( self::TERM_FIELDS_FUNCTIONS[ $field_name ] ) ) {
			$function = self::TERM_FIELDS_FUNCTIONS[ $field_name ];
			return call_user_func( $function, $term );
		}

		return get_term_meta( $term->term_id, $field_name, true );
	}

	private function get_term_of_interest() {
		global $wp_query;

		if ( $this->has_keyarg( 'object' ) ) {
			$term_object = $this->get_keyarg( 'object' );
			if ( ! ( $term_object instanceof \WP_Term ) ) {
				return null;
			}
			return $term_object;
		}

		if ( $this->has_keyarg( 'id' ) ) {
			$term_id = $this->get_keyarg( 'id' );
			if ( ! term_exists( intval( $term_id ) ) ) {
				return null;
			}
			$term     = get_term( intval( $term_id ) );
			$taxonomy = get_taxonomy( $term->taxonomy );
			if ( $taxonomy && ! $taxonomy->public ) {
				$this->ensure_all_privileges( esc_html__( 'fetching a term of a non-public taxonomy', 'dynamic-shortcodes' ) );
			}
			return $term;
		}

		if ( class_exists( '\Bricks\Query' ) ) {
			$looping_query_id = \Bricks\Query::is_any_looping();
			$term             = null;
			if ( ! empty( $looping_query_id ) ) {
				$term = \Bricks\Query::get_loop_object( $looping_query_id );
			}
			if ( $term && is_a( $term, 'WP_Term' ) ) {
				return $term;
			}
		}

		if (
			// if we are in an elementor taxonomy loop:
			( $wp_query->is_loop_taxonomy ?? false ) &&
			isset( $wp_query->loop_term ) &&
			is_object( $wp_query->loop_term ) &&
			isset( $wp_query->loop_term->term_id )
		) {
			$term_id = $wp_query->loop_term->term_id;
		} else {
			$term_id = get_queried_object_id();
		}
		if ( ! term_exists( $term_id ) ) {
			return null;
		}
		$term = get_term( intval( $term_id ) );
		return $term;
	}
}
