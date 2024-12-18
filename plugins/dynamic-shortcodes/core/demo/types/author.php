<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class Author extends User {

	protected $type = 'author';

	/**
	 * Flag to indicate if the type requires all privileges
	 *
	 * @return boolean
	 */
	public function requires_all_privileges() {
		return true;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		// translators: 1 is a title
		$message = esc_html__( 'This shortcode is useful for retrieving the author. In this example, we show you all the custom fields available in the user, author of the current page %1$s. You may not recognise all fields as some are created by third-party plugins.', 'dynamic-shortcodes' );
		return sprintf( $message, '"' . get_the_title() . '"' );
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with field name, 2: placeholder for field name, 3: syntax example with @ID, 4: syntax example combining field name with @ID=VALUE, where VALUE is an example author ID. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific field you wish to select from the current author. If you want to select a specific author, you can use %3$s followed by the author\'s identification number. For example, combining field name with author ID, writing %4$s will retrieve the fields related to the author with ID %5$d.', 'dynamic-shortcodes' );

		$example_id = 2;
		$field_name = 'field-name';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ), '<code>' . $field_name . '</code>', '<code>@ID</code>', '<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @ID=' . $example_id . '}' ) . '</code>', $example_id );
	}
}
