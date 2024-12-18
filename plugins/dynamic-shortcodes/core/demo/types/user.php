<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class User extends BaseDemo {

	protected $type = 'user';

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving User Fields. In this example, we show you all the custom fields available in the current user. You may not recognise all fields as some are created by third-party plugins.', 'dynamic-shortcodes' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with field name, 2: placeholder for field name, 3: syntax example with @ID, 4: syntax example combining field name with @ID=VALUE, where VALUE is an example user ID. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific field you wish to select from the current user. If you want to select a specific user, you can use %3$s followed by the user\'s identification number. For example, combining field name with user ID, writing %4$s will retrieve the fields related to the user with ID %5$d.', 'dynamic-shortcodes' );

		$example_id = 2;
		$field_name = 'field-name';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ), '<code>' . $field_name . '</code>', '<code>@ID</code>', '<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @ID=' . $example_id . '}' ) . '</code>', $example_id );
	}

	/**
	 * Generate sample ID
	 *
	 * @return array<string,string>
	 */
	public function generate_sample_id() {
		return [
			[
				'name' => [ BaseDemo::class, 'generate_user_id' ],
				'arg' => 'nickname',
				'keyarg' => 'ID',
				'force_result_types' => [ 'string' ],
			],
		];
	}
}
