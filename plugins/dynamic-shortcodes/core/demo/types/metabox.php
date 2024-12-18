<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Metabox extends Post {

	protected $type = 'metabox';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving Meta Box fields.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with field name, 2: placeholder for field name, 3: syntax example with @ID, 4: syntax example combining field name with @ID=VALUE, where VALUE is an example post ID. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific Meta Box field you wish to select from the current post. If you want to select a specific post, you can use %3$s followed by the post\'s identification number. For example, combining field name with post ID, writing %4$s will retrieve the fields related to the post with ID %5$d.', 'dynamic-shortcodes' );

		$example_post_id = 200;
		$field_name      = 'field-name';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ), '<code>' . $field_name . '</code>', '<code>@ID</code>', '<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @ID=' . $example_post_id . '}' ) . '</code>', $example_post_id );
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
