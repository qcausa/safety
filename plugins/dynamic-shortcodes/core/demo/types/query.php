<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Query extends BaseDemo {

	protected $type = 'query';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving a list of posts, users, terms or Woo Orders.', 'dynamic-shortcodes' );
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
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with field name, 2: placeholder for field name, 3: syntax example with @ID, 4: syntax example combining field name with @ID=VALUE, where VALUE is an example post ID. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific object between posts, users, terms and woo-orders.', 'dynamic-shortcodes' );

		$field_name = 'objects';
		return sprintf(
			$format,
			self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ),
			'<code>' . $field_name . '</code>',
		);
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
