<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Media extends BaseShortcode {

	public static function get_shortcode_types( $context ) {
		return [ 'media' ];
	}

	public function evaluate() {
		$this->arity_check( 1, 1 );

		$this->init_keyargs(
			[
				'id' => [],
				'size' => [],
			],
			[],
			[ 'id' ]
		);

		$info = $this->get_arg( 0, 'string' );
		$id   = $this->get_keyarg( 'id', 'numeric' );

		$attachment = get_post( $id );

		if ( ! $attachment ) {
			return null;
		}

		switch ( $info ) {
			case 'width':
			case 'height':
				if ( ! wp_attachment_is_image( $attachment ) ) {
					$this->evaluation_error( esc_html__( 'Not an image', 'dynamic-shortcodes' ) );
				}
				$size             = $this->get_keyarg_default( 'size', 'large' );
				$image_attributes = wp_get_attachment_image_src( $attachment->ID, $size );
				return $image_attributes[ [
					'width' => 1,
					'height' => 2,
				][ $info ] ];
			case 'url':
				if ( wp_attachment_is_image( $attachment ) ) {
					$size          = $this->get_keyarg_default( 'size', 'large' );
					$valid_sizes   = get_intermediate_image_sizes();
					$valid_sizes[] = 'full';
					if ( ! in_array( $size, $valid_sizes, true ) ) {
						$this->evaluation_error( "Invalid size: $size" );
					}
					return wp_get_attachment_image_url( $attachment->ID, $size );
				} else {
					return wp_get_attachment_url( $attachment->ID );
				}
			case 'image':
				if ( wp_attachment_is_image( $attachment ) ) {
					$size          = $this->get_keyarg_default( 'size', 'large' );
					$valid_sizes   = get_intermediate_image_sizes();
					$valid_sizes[] = 'full';
					if ( ! in_array( $size, $valid_sizes, true ) ) {
						$this->evaluation_error( "Invalid size: $size" );
					}
					$attr = [];
					return wp_get_attachment_image( $attachment->ID, $size, false, $attr );
				} else {
					$this->evaluation_error( 'image arg is available only for images' );
				}
				break;
			case 'file-path':
				return get_attached_file( $attachment->ID );
			case 'alt-text':
				return get_post_meta( $id, '_wp_attachment_image_alt', true );
			case 'title':
				return $attachment->post_title;
			case 'caption':
				return $attachment->post_excerpt;
			case 'description':
				return $attachment->post_content;
			case 'permalink':
				return get_permalink( $attachment->ID );
			default:
				$this->evaluation_error( "Invalid media info: $info" );
		}
	}
}
