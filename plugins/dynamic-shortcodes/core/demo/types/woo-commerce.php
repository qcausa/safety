<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo\Types;

use DynamicShortcodes\Core\Demo\BaseDemo;

class WooCommerce extends Post {

	protected $type = 'woo';

	public function prerequisites_satisfied_for_current_element() {
		return get_post_type( get_the_ID() ) === 'product';
	}

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {
		/* translators: 1: syntax example with field name, 2: placeholder for field name, 3: syntax example with @ID, 4: syntax example combining field name with @ID=VALUE, where VALUE is an example post ID. */
		$format = esc_html__( 'To use the syntax, you should write %1$s where %2$s represents the name of a specific WooCommerce field you wish to select from the current product. If you want to select a specific product, you can use %3$s followed by the product\'s identification number. For example, combining field name with product ID, writing %4$s will retrieve the fields related to the product with ID %5$d.', 'dynamic-shortcodes' );

		$example_product_id = 200;
		$field_name         = 'field-name';
		return sprintf( $format, self::escape_code( '<code>{' . $this->type . ':' . $field_name . '}</code>' ), '<code>' . $field_name . '</code>', '<code>@ID</code>', '<code>' . self::escape_code( '{' . $this->type . ':' . $field_name . ' @ID=' . $example_product_id . '}' ) . '</code>', $example_product_id );
	}

	public function get_missing_prerequisites_message() {
		return esc_html__( "Demo not available for the current Post ID because it's not a WooCommerce Product.", 'dynamic-shortcodes' );
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'This shortcode is useful for retrieving WooCommerce Product Fields', 'dynamic-shortcodes' );
	}

	/**
	 * Generate sample ID
	 *
	 * @return array<string,string>
	 */
	public function generate_sample_id() {
		return [
			[
				'name' => [ BaseDemo::class, 'generate_product_id' ],
				'arg' => 'title',
				'keyarg' => 'ID',
				'force_result_types' => [ 'string' ],
			],
		];
	}
}
