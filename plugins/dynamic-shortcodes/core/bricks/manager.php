<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Bricks;

use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\ParseError;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
class Manager {
	private $original_value = false;
	private $replacements   = [];

	public function __construct() {
		add_action( 'after_setup_theme', [ $this, 'init' ] );
	}

	public function init() {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return;
		}
		$version = preg_replace( '/-(beta|dev|rc)\d*$/', '', BRICKS_VERSION );
		if ( version_compare( $version, '1.9.9', '<' ) ) {
			return;
		}
		if (
			// phpcs:ignore WordPress.Security.NonceVerification
			( $_GET['bricks'] ?? '' ) === 'run' || wp_doing_ajax() ) {
			return;
		}
		add_filter( 'bricks/frontend/render_data', [ $this, 'prepare_replacements_filter' ], 0, 1 );
		add_filter( 'bricks/dynamic_data/render_content', [ $this, 'prepare_replacements_filter' ], 0, 1 );
		add_filter( 'bricks/dynamic_data/render_tag', [ $this, 'save_original_value' ], 0, 1 );

		// render_data seems to be the final html:
		add_filter( 'bricks/frontend/render_data', [ $this, 'add_back_replacements_filter' ], 20, 1 );
		add_filter( 'bricks/dynamic_data/render_content', [ $this, 'add_back_replacements_filter' ], 10, 1 );
		add_filter( 'bricks/dynamic_data/render_tag', [ $this, 'render_tag_filter' ], 20, 3 );
	}

	public function save_original_value( $value ) {
		$this->original_value = $value;
		return $value;
	}

	public function prepare_replacements_filter( $content ) {
		$shortcodes_manager         = Plugin::instance()->shortcodes_manager;
		[ $content, $replacements ] = $shortcodes_manager->get_marked_string_and_expanded_replacements( $content );
		$this->replacements         = $replacements;
		return $content;
	}

	public function add_back_replacements_filter( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}
		return strtr( $content, $this->replacements );
	}

	private function evaluate_media( $tag ) {
		try {
			$input = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $tag );
		} catch ( ParseError $e ) {
			return $tag;
		} catch ( EvaluationError $e ) {
			$this->add_evaluation_error( $tag, $e );
			return $tag;
		}
		if ( ! is_array( $input ) ) {
			$input = [ $input ];
		}
		$res = [];
		foreach ( $input as $media_id ) {
			if ( is_numeric( $media_id ) ) {
				$res[] = [
					'id'  => $media_id,
					'url' => wp_get_attachment_url( $media_id ),
				];
			} else {
				$res[] = [
					'url' => $media_id,
				];
			}
		}
		return $res;
	}

	private function evaluate_image( $tag ) {
		try {
			$input = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $tag );
		} catch ( ParseError $e ) {
			return $tag;
		} catch ( EvaluationError $e ) {
			$this->add_evaluation_error( $tag, $e );
			return $tag;
		}
		if ( ! is_array( $input ) ) {
			$input = [ $input ];
		}
		return $input;
	}

	public function render_tag_filter( $tag, $post, $context = 'text' ) {
		if ( ! is_string( $tag ) ) {
			return $tag;
		}
		if ( substr( $tag, 1, -1 ) !== $this->original_value ) {
			// check substr because bricks filter adds the braces back:
			return $tag;
		}

		$shortcodes_manager = Plugin::instance()->shortcodes_manager;
		switch ( $context ) {
			case 'text':
				return $shortcodes_manager->expand_shortcodes( $tag );
			case 'media':
				return $this->evaluate_media( $tag );
			case 'image':
				return $this->evaluate_image( $tag );
			case 'link':
				try {
					return Plugin::instance()->shortcodes_manager->expand_shortcodes( $tag, [ 'catch_errors' => false ] );
				} catch ( EvaluationError $e ) {
					$this->add_evaluation_error( $tag, $e );
					return $tag;
				}
				break;
		}
		// should never be reached:
		return $tag;
	}

	private function add_evaluation_error( $content, $err ) {
		$msg = esc_html__( 'Error while evaluating shortcode. ', 'dynamic-shortcodes' );
		if ( is_string( $err ) ) {
			$msg .= $err;
		} else {
			$msg .= $err->getMessage();
		}
		Plugin::instance()->shortcodes_manager->add_error( $msg, $content );
	}
}
