<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\PermissionsError;

class Post extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'post',
		];
	}

	const POST_METAS_ALIAS = [
		'ID'      => 'ID',
		'id'      => 'ID',
		'date'    => 'post_date',
		'author'  => 'post_author',
		'post_content' => 'post_content',
		'content' => 'post_content',
		'modified-date' => 'post_modified',
		'comment-count' => 'comment_count',
		'slug'          => 'post_name',
		'status'        => 'post_status',
	];

	const POST_METAS_FUNCTIONS = [
		'title'   => 'get_the_title',
		'excerpt' => 'get_the_excerpt',
		'featured-image-id' => 'get_post_thumbnail_id',
		'permalink' => 'get_the_permalink',
		'type' => 'get_post_type',
		'parent-id' => 'wp_get_post_parent_id',
		'content-rendered' => 'get_the_content',
		'type-label' => [ self::class, 'get_post_type_label' ],
		'type-label-plural' => [ self::class, 'get_post_type_label_plural' ],
	];

	/**
	 * Get the post type label.
	 *
	 * @param int $post_id The post ID.
	 * @return string The post type label.
	 */
	public static function get_post_type_label( $post_id ) {
		$post_type        = get_post_type( $post_id );
		$post_type_object = get_post_type_object( $post_type );
		return $post_type_object ? $post_type_object->labels->singular_name : '';
	}

	/**
	 * Get the post type label Plural.
	 *
	 * @param int $post_id The post ID.
	 * @return string The post type label.
	 */
	public static function get_post_type_label_plural( $post_id ) {
		$post_type        = get_post_type( $post_id );
		$post_type_object = get_post_type_object( $post_type );
		return $post_type_object ? $post_type_object->labels->name : '';
	}

	/**
	 * @return array<string>
	 */
	public function get_whitelist_meta_with_underscores() {
		return [
			'_thumbnail_id',
		];
	}

	/**
	 * @return string
	 */
	public static function get_not_valid_id_error_msg() {
		return esc_html__( 'This post ID is not valid', 'dynamic-shortcodes' );
	}

	protected function is_appropriate_type( $id ) {
		return true; // all posts id are valid
	}

	public static function get_wrong_object_error_msg() {
		return esc_html__( 'The object is not an instance of WP_Post', 'dynamic-shortcodes' );
	}

	/**
	 * @return string
	 */
	public static function get_term_not_exists_error_msg() {
		return esc_html__( 'This term doesn\'t exist', 'dynamic-shortcodes' );
	}

	protected function get_id() {
		$id = get_the_ID();
		if ( $this->has_keyarg( 'id' ) ) {
			$id = $this->get_keyarg( 'id' );
			if ( ! get_post( $id ) ) {
				return null;
			}
		}

		return $id;
	}

	public function evaluate() {
		$this->arity_check( 1, 1 );

		$meta = $this->get_arg( 0, 'string' );

		$keyargs_accepted = [
			'id' => [],
		];

		if ( 'terms' === $meta ) {
			$keyargs_accepted['taxonomy'] = [];
		} else {
			$keyargs_accepted['all'] = []; // wether to return all meta or only one.
		}

		$this->init_keyargs( $keyargs_accepted );

		if ( ! in_array( $meta, $this->get_whitelist_meta_with_underscores(), true ) && substr( $meta, 0, 1 ) === '_' ) {
			$this->ensure_all_privileges( esc_html__( 'Reading Post Meta starting with underscore', 'dynamic-shortcodes' ) );
		}

		$id = $this->get_id();

		if ( ! $id ) {
			return null;
		}

		if ( ! $this->is_appropriate_type( $id ) ) {
			return null;
		}

		if ( post_password_required( $id ) ) {
			return '';
		}

		if ( $this->has_keyarg( 'id' ) && ! is_post_publicly_viewable( $id ) ) {
			$this->ensure_all_privileges( esc_html__( 'Reading Post Meta on not public post', 'dynamic-shortcodes' ) );
		}

		if ( 'terms' === $meta ) {
			return $this->get_terms( $id );
		}

		$res = $this->handle_special_meta( $meta, $id );

		if ( $res !== false ) {
			return $res[0];
		}

		if ( $this->is_arg_an_identifier( 0 ) && isset( self::POST_METAS_ALIAS[ $meta ] ) ) {
			$meta = self::POST_METAS_ALIAS[ $meta ];
			$post = get_post( $id, ARRAY_A );
			if ( $post === null ) {
				return null;
			}
			return $post[ $meta ] ?? null;
		}

		if ( $this->is_arg_an_identifier( 0 ) && isset( self::POST_METAS_FUNCTIONS[ $meta ] ) ) {
			$function = self::POST_METAS_FUNCTIONS[ $meta ];
			return call_user_func( $function, $object ?? $id );
		}

		$single = ! $this->get_bool_keyarg( 'all', false );
		return get_post_meta( $id, $meta, $single );
	}

	protected function handle_special_meta( $meta, $id ) {
		if ( 'terms' === $meta ) {
			return [ $this->evaluate_terms() ];
		}

		return false;
	}

	protected function get_terms( $id ) {
		$taxonomy = 'category';

		if ( $this->has_keyarg( 'taxonomy' ) ) {
			$taxonomy = $this->get_keyarg( 'taxonomy' );
		}

		return wp_get_post_terms( $id, $taxonomy, [ 'fields' => 'ids' ] );
	}
}
