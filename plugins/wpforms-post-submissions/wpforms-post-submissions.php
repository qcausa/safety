<?php
/**
 * Plugin Name:       WPForms Post Submissions
 * Plugin URI:        https://wpforms.com
 * Description:       Post Submissions with WPForms.
 * Requires at least: 5.5
 * Requires PHP:      7.0
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.7.0
 * Text Domain:       wpforms-post-submissions
 * Domain Path:       languages
 *
 * WPForms is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WPForms is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WPForms. If not, see <https://www.gnu.org/licenses/>.
 */

use WPFormsPostSubmissions\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
const WPFORMS_POST_SUBMISSIONS_VERSION = '1.7.0';

/**
 * Plugin file.
 *
 * @since 1.5.0
 */
const WPFORMS_POST_SUBMISSIONS_FILE = __FILE__;

/**
 * Plugin path.
 *
 * @since 1.5.0
 */
define( 'WPFORMS_POST_SUBMISSIONS_PATH', plugin_dir_path( WPFORMS_POST_SUBMISSIONS_FILE ) );

/**
 * Plugin URL.
 *
 * @since 1.5.0
 */
define( 'WPFORMS_POST_SUBMISSIONS_URL', plugin_dir_url( WPFORMS_POST_SUBMISSIONS_FILE ) );

/**
 * Check addon requirements.
 *
 * @since 1.0.0
 * @since 1.5.0 Uses requirements feature.
 */
function wpforms_post_submissions_load() {

	$requirements = [
		'file'    => WPFORMS_POST_SUBMISSIONS_FILE,
		'wpforms' => '1.9.0',
	];

	if ( ! function_exists( 'wpforms_requirements' ) || ! wpforms_requirements( $requirements ) ) {
		return;
	}

	wpforms_post_submissions();
}

add_action( 'wpforms_loaded', 'wpforms_post_submissions_load' );

/**
 * Get the instance of the addon main class.
 *
 * @since 1.0.0
 *
 * @return Plugin
 */
function wpforms_post_submissions() {

	require_once WPFORMS_POST_SUBMISSIONS_PATH . 'vendor/autoload.php';

	return Plugin::get_instance();
}
