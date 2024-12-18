<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Option extends BaseDemo {

	protected $type = 'option';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving option values. In this example we show you all the option values available on your WordPress site. You may not recognise all values as some are created by third-party plugins.', 'dynamic-shortcodes' );
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
