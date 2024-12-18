<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Elementor\DynamicTags;

use DynamicShortcodes\Plugin;
use Elementor\Controls_Manager;
use DynamicShortcodes\Core\Shortcodes\ParseError;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Image extends \Elementor\Core\DynamicTags\Data_Tag {
	public function get_name() {
		return 'dynamic-shortcodes-image';
	}

	public function get_title() {
		return esc_html__( 'Dynamic Shortcodes - Image', 'dynamic-shortcodes' );
	}

	public function get_group() {
		return [ 'dynamic-shortcodes' ];
	}

	public function get_categories() {
		return [
			\Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
		];
	}

	protected function register_controls() {
		$this->add_control(
			'content',
			[
				'label' => esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' ),
				'type' => Controls_Manager::TEXTAREA,
				'ai' => [
					'active' => false,
				],
				'placeholder' => '{acf:myimageurl}',
				'description' => esc_html__( 'The code should expand to either an ID of a Media File or the URL of an image', 'dynamic-shortcodes' ),
			],
		);
	}

	private function add_evaluation_error( $content, $err ) {
		$msg = esc_html__( 'Evaluation error in Elementor Image Dynamic Tag. ', 'dynamic-shortcodes' );
		if ( is_string( $err ) ) {
			$msg .= $err;
		} else {
			$msg .= $err->getMessage();
		}
		Plugin::instance()->shortcodes_manager->add_error( $msg, $content );
	}

	public function get_value( array $options = [] ) {
		$content = $this->get_settings( 'content' );
		try {
			$res = Plugin::instance()->shortcodes_manager->expand_shortcodes( $content, [ 'catch_errors' => false ] );
		} catch ( EvaluationError $e ) {
			$this->add_evaluation_error( $content, $e );
			return [
				'url' => '',
				'id' => null,
			];
		}
		if ( is_numeric( $res ) ) {
			$url = wp_get_attachment_image_url( $res, 'full' );
			if ( ! $url ) {
				$this->add_evaluation_error( $content, esc_html__( 'Could not find the media url', 'dynamic-shortcodes' ) );
				return [
					'url' => '',
					'id' => null,
				];
			}
			return [
				'id' => $res,
				'url' => $url,
			];
		}
		return [
			'url' => $res,
			'id' => null,
		];
	}
}
