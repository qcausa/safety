<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Call extends BaseDemo {

	protected $type = 'call';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for to call a PHP function.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with function name, 2: placeholder for function name, 3: syntax example with parameter, 4: syntax example combining function name with parameter. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific PHP function. The following arguments will be used as parameters passed to the function. For example, %3$s will pass the parameter %4$s to the function.', 'dynamic-shortcodes' );

		$example_post_id = 200;
		$function_name   = 'function-name';
		$parameter       = 'Hello World';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $function_name . '}</code>' ), '<code>' . $function_name . '</code>', self::escape_code( '<code>{' . $this->type . ':' . $function_name . ' \'' . $parameter . '\'}</code>' ), '<code>' . $parameter . '</code>' );
	}

	/**
	 * Flag to indicate if the type requires all privileges
	 *
	 * @return boolean
	 */
	public function requires_all_privileges() {
		return true;
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
