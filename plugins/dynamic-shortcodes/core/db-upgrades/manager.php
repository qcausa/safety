<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\DbUpgrades;

use DynamicShortcodes\Core\Settings\Manager as SettingsManager;

class Manager {
	public function __construct() {
		$db_version = get_option( 'dynamic_shortcodes_version' );
		if ( $db_version === false ) {
			$this->filters_option_upgrade();
		}
		if ( $db_version !== DYNAMIC_SHORTCODES_VERSION ) {
			update_option( 'dynamic_shortcodes_version', DYNAMIC_SHORTCODES_VERSION );
		}
	}

	private function filters_option_upgrade() {
		if ( ! get_option( 'dsh_license_key' ) ) {
			return;
		}
		$prefix  = 'dynamic_shortcodes_';
		$options = [
			'enabled_in_post_content',
			'enabled_in_post_title',
			'enabled_in_post_excerpt',
			'enabled_in_widget_text',
			'enabled_in_blocks_content',
		];
		update_option( $prefix . 'enabled_in_elementor_dynamic_tag', true );
		update_option( $prefix . 'enabled_in_breakdance', true );
		update_option( $prefix . 'enabled_in_oxygen', true );
		update_option( $prefix . 'enabled_as_wp_shortcode', true );
		if ( get_option( $prefix . $options[0] ) === false ) {
			foreach ( $options as $option ) {
				update_option( $prefix . $option, true );
			}
		}
	}
}
