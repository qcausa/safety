<?php
// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

/**
 *
 * @wordpress-plugin
 * Plugin Name: Dynamic.ooo - Dynamic Shortcodes
 * Plugin URI: https://www.dynamic.ooo/dynamic-shortcodes
 * Description: Enhance your WordPress content with Dynamic Shortcodes, powerful placeholders for dynamically generated values. Easily create and use Dynamic Shortcodes to display real-time data, dynamic content, and more. It's compatible with Elementor, Bricks, Gutenberg, Full Site Editing, Oxygen, Breakdance and the Classic Editor.
 * Version: 1.5.8
 * Requires at least: 5.7
 * Requires PHP: 7.3
 * Author: Dynamic.ooo
 * Author URI: https://www.dynamic.ooo/
 * Text Domain: dynamic-shortcodes
 * Domain Path: /languages
 * License: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
 * License URI: https://license.dynamic.ooo/dynamic-shortcodes/GPL-3.0-with-dynamicooo-additional-terms.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * Dynamic Shortcodes includes code from:
 * - Select2, Copyright (c) 2012-2017 Kevin Brown, Igor Vaynberg, and Select2 contributors, License: MIT, https://github.com/select2/select2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DYNAMIC_SHORTCODES_VERSION', '1.5.8' );
define( 'DYNAMIC_SHORTCODES__FILE__', __FILE__ );
define( 'DYNAMIC_SHORTCODES_PATH', plugin_dir_path( DYNAMIC_SHORTCODES__FILE__ ) );
define( 'DYNAMIC_SHORTCODES_URL', plugin_dir_url( __FILE__ ) );
define( 'DYNAMIC_SHORTCODES_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'DYNAMIC_SHORTCODES_SLUG', 'dynamic-shortcodes' );
define( 'DYNAMIC_SHORTCODES_PREFIX', 'dsh' );
define( 'DYNAMIC_SHORTCODES_LICENSE_URL', 'https://license.dynamic.ooo' );
define( 'DYNAMIC_SHORTCODES_PRICING_PAGE', 'https://www.dynamic.ooo/dynamic-shortcodes/pricing/' );
define( 'DYNAMIC_SHORTCODES_PRODUCT_NAME', 'Dynamic Shortcodes' );
define( 'DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG', 'Dynamic.ooo - Dynamic Shortcodes' );
define( 'DYNAMIC_SHORTCODES_PRODUCT_UNIQUE_ID', 'WP-DSH-1' );

/**
 * @return void
 */
function dynamic_shortcodes_load_plugin() {
	load_plugin_textdomain( 'dynamic-shortcodes' );
	require DYNAMIC_SHORTCODES_PATH . 'plugin.php';
}

register_activation_hook(
	DYNAMIC_SHORTCODES__FILE__,
	function ( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			return;
		}
		set_transient( 'dsh_do_activation_redirect', true, 30 );
	}
);

register_deactivation_hook( DYNAMIC_SHORTCODES_PLUGIN_BASE, '\DynamicShortcodes\Plugin::deactivate' );
function dsh_uninstall() {
	delete_option( DYNAMIC_SHORTCODES_PREFIX . '_license_key' );
}
register_uninstall_hook( DYNAMIC_SHORTCODES_PLUGIN_BASE, 'dsh_uninstall' );


add_action( 'plugins_loaded', 'dynamic_shortcodes_load_plugin' );
