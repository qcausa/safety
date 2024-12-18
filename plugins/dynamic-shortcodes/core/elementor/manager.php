<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Elementor;

use DynamicShortcodes\Core\Elementor\DynamicTags\Text;
use DynamicShortcodes\Core\Elementor\DynamicTags\Image;
use DynamicShortcodes\Core\Elementor\DynamicTags\Gallery;

class Manager {
	public function __construct() {
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ] );
	}

	/**
	 * @return void
	 */
	public function register_dynamic_tags( $dynamic_tags_manager ) {
		$dynamic_tags_manager->register_group(
			'dynamic-shortcodes',
			[
				'title' => esc_html__( 'Dynamic.ooo - Dynamic Shortcodes', 'dynamic-shortcodes' ),
			]
		);
		$dynamic_tags_manager->register( new Text() );
		$dynamic_tags_manager->register( new Image() );
		$dynamic_tags_manager->register( new Gallery() );
	}
}
