<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Oxygen;

use DynamicShortcodes\Plugin;

class Manager {
	public function __construct() {
		add_filter( 'oxygen_custom_dynamic_data', [ $this, 'init_dynamic_data' ] );
	}

	public function init_dynamic_data( $dynamic_data ) {
		$properties     = [
			[
				'name' => esc_html__( 'Text with Dynamic Shortcodes', 'dynamic-shortcodes' ),
				'data' => 'shortcode',
				'type' => 'text',
			],
		];
		$dynamic_data[] = [
			'name'      => esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' ),
			'mode'       => 'content',
			'position'   => 'Advanced',
			'data'       => 'dynamic_shortcodes',
			'handler'    => [ $this, 'handler' ],
			'properties' => $properties,
		];
		return $dynamic_data;
	}

	public function handler( $atts ) {
		$value = Plugin::instance()->shortcodes_manager->expand_shortcodes( $atts['shortcode'] );
		return $value;
	}
}
