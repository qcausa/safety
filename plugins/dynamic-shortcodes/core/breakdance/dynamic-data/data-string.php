<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Breakdance\DynamicData;

use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;
use DynamicShortcodes\Plugin;

class DataString extends StringField {


	/**
	 * @inheritDoc
	 */
	public function label() {
		return 'Dynamic Shortcodes - String';
	}

	/**
	 * @inheritDoc
	 */
	public function category() {
		return 'Dynamic Shortcodes';
	}

	/**
	 * @inheritDoc
	 */
	public function slug() {
		return 'dynamic_shortcodes_string';
	}

	/**
	 * @inheritDoc
	 */
	public function controls() {
		return [
			\Breakdance\Elements\control(
				'shortcode',
				esc_html__( 'Text with Dynamic Shortcodes', 'dynamic-shortcodes' ),
				[
					'type' => 'text',
					'layout' => 'vertical',
				]
			),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function returnTypes() {
		 return [ 'string', 'query', 'url', 'google_map' ];
	}

	/**
	 * @inheritDoc
	 */
	public function handler( $attributes ): StringData {
		if ( empty( $attributes['shortcode'] ) ) {
			return StringData::emptyString();
		}
		$value = Plugin::instance()->shortcodes_manager->expand_shortcodes( $attributes['shortcode'] );
		if ( $value === '' ) {
			return StringData::emptyString();
		}
		return StringData::fromString( $value );
	}

	/**
	 * @inheritDoc
	 */
	public function proOnly() {
		return false;
	}
}
