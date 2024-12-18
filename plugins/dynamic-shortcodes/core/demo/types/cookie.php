<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Cookie extends BaseDemo {

	protected $type = 'cookie';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving cookies.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with cookie name, 2: placeholder for cookie name */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific cookie you wish to select', 'dynamic-shortcodes' );

		$example_post_id = 200;
		$cookie_name     = 'cookie-name';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $cookie_name . '}</code>' ), '<code>' . $cookie_name . '</code>' );
	}

	/**
	 * Generate sample ID
	 *
	 * @return array<string,string>
	 */
	public function generate_sample_id() {
		return [];
	}
}
