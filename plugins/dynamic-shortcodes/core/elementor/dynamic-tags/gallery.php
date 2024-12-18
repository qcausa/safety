<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Elementor\DynamicTags;

use DynamicShortcodes\Plugin;
use Elementor\Controls_Manager;
use DynamicShortcodes\Core\Shortcodes\ParseError;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Gallery extends \Elementor\Core\DynamicTags\Data_Tag {
	public function get_name() {
		return 'dynamic-shortcodes-gallery';
	}

	public function get_title() {
		return esc_html__( 'Dynamic Shortcodes - Gallery', 'dynamic-shortcodes' );
	}

	public function get_group() {
		return [ 'dynamic-shortcodes' ];
	}

	public function get_categories() {
		return [
			\Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY,
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
				'placeholder' => '{acf:mygallery}',
				'description' => esc_html__( 'The last shortcode should return an array of Media IDs. Text outside shortcodes is ignored.', 'dynamic-shortcodes' ),
			],
		);
	}

	private function add_evaluation_error( $content, $err ) {
		$msg = esc_html__( 'Evaluation error in Elementor Gallery Dynamic Tag. ', 'dynamic-shortcodes' );
		if ( is_string( $err ) ) {
			$msg .= $err;
		} else {
			$msg .= $err->getMessage();
		}
		Plugin::instance()->shortcodes_manager->add_error( $msg, $content );
	}

	private function add_parse_error( $content, $err ) {
		$msg = esc_html__( 'Parse error in Elementor Gallery Dynamic Tag. ', 'dynamic-shortcodes' );
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
			$input = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $content );
		} catch ( ParseError $e ) {
			$this->add_parse_error( $content, $e );
			return [];
		} catch ( EvaluationError $e ) {
			$this->add_evaluation_error( $content, $e );
			return [];
		}
		if ( ! is_array( $input ) ) {
			$this->add_evaluation_error( $content, esc_html__( 'The last shortcode should return an array', 'dynamic-shortcodes' ) );
			return [];
		}
		$res = [];
		foreach ( $input as $id ) {
			if ( ! is_numeric( $id ) ) {
				$this->add_evaluation_error( $content, esc_html__( 'The last shortcode should return an array of Media IDs', 'dynamic-shortcodes' ) );
				return [];
			}
			$res[] = [ 'id' => $id ];
		}
		return $res;
	}
}
