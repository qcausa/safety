<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Elementor\DynamicTags;

use DynamicShortcodes\Plugin;
use Elementor\Controls_Manager;
use DynamicShortcodes\Core\Shortcodes\UnitInterpreter;

class Text extends \Elementor\Core\DynamicTags\Tag {
	public function get_name() {
		return 'text-with-dynamic-shortcodes';
	}

	public function get_title() {
		return esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' );
	}

	public function get_group() {
		return [ 'dynamic-shortcodes' ];
	}

	public function get_categories() {
		return [
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::DATETIME_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::COLOR_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY,
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
			]
		);
	}

	public function render() {
		$content = $this->get_settings( 'content' );
		if ( apply_filters( 'dynamic-shortcodes/elementor/dynamic-tag-text/early-return', false ) ) {
			// used by dce.
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
			return;
		}
		[ $content, $replacements ] = Plugin::instance()->shortcodes_manager->get_marked_string_and_expanded_replacements( $content );
		$content                    = UnitInterpreter::sanitize( $content );
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo strtr( $content, $replacements );
	}
}
