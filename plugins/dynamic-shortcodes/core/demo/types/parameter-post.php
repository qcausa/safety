<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class ParameterPost extends BaseDemo {

	protected $type = 'param-post';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving POST parameters.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with parameter, 2: placeholder for parameter name */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a parameter sent via POST', 'dynamic-shortcodes' );

		$example_post_id = 200;
		$parameter_name  = 'parameter';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $parameter_name . '}</code>' ), '<code>' . $parameter_name . '</code>' );
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
