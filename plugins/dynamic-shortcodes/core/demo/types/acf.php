<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Acf extends Post {

	protected $type = 'acf';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving ACF fields.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with ACF field name, 2: placeholder for ACF field name, 3: syntax example with @ID, 4: syntax example combining ACF field name with @ID=VALUE, where VALUE is an example post ID, 5: example post ID, 6: syntax example with format, 7: syntax example combining ACF field name with @ID=VALUE and format. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific ACF Field you wish to select from the current post. If you want to select a specific post, you can use %3$s followed by the post\'s identification number. For example, combining field name with post ID, writing %4$s will retrieve the fields related to the post with ID %5$d. If you want to use the chosen return format set in the ACF field settings, you can add %6$s. For example, you can use %7$s or %8$s.', 'dynamic-shortcodes' );

		$example_post_id = 200;
		$field_name      = 'field-name';
		return sprintf(
			$format,
			self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ),
			'<code>' . $field_name . '</code>',
			'<code>@ID</code>',
			self::escape_code( '<code>{' . $this->type . ':' . $field_name . ' @ID=' . $example_post_id . '}</code>' ),
			$example_post_id,
			'<code>@format</code>',
			'<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @format}' ) . '</code>',
			'<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @ID=' . $example_post_id . ' format}' ) . '</code>',
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
