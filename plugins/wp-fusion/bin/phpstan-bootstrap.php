<?php
/**
 * PHP Stan bootstrap file.
 *
 * @package WP Fusion
 */

if ( ! defined( 'ABSPATH' ) ) {
	/**
	 * Absolute path to WordPress.
	 *
	 * @phpstan-type string $abspath
	 * @var string $abspath
	 */
	define( 'ABSPATH', '/path/to/wordpress/' );
}

if ( ! defined( 'WPF_MIN_WP_VERSION' ) ) {
	/**
	 * Minimum WordPress version required.
	 *
	 * @phpstan-type string $min_wp_version
	 * @var string $min_wp_version
	 */
	define( 'WPF_MIN_WP_VERSION', '4.0' );
}

if ( ! defined( 'WPF_MIN_PHP_VERSION' ) ) {
	/**
	 * Minimum PHP version required.
	 *
	 * @phpstan-type string $min_php_version
	 * @var string $min_php_version
	 */
	define( 'WPF_MIN_PHP_VERSION', '5.6' );
}

if ( ! defined( 'WPF_DIR_PATH' ) ) {
	/**
	 * Directory path where WP Fusion is located.
	 *
	 * @phpstan-type string $dir_path
	 * @var string $dir_path
	 */
	define( 'WPF_DIR_PATH', 'path/to/wp-fusion' );
}

if ( ! defined( 'WPF_PLUGIN_PATH' ) ) {
	/**
	 * Full plugin path for WP Fusion.
	 *
	 * @phpstan-type string $plugin_path
	 * @var string $plugin_path
	 */
	define( 'WPF_PLUGIN_PATH', 'path/to/wp-fusion' );
}

if ( ! defined( 'WPF_DIR_URL' ) ) {
	/**
	 * URL for the WP Fusion directory.
	 *
	 * @phpstan-type string $dir_url
	 * @var string $dir_url
	 */
	define( 'WPF_DIR_URL', 'https://site.com/path/to/wp-fusion' );
}

if ( ! defined( 'WPF_STORE_URL' ) ) {
	/**
	 * URL for the WP Fusion store.
	 *
	 * @phpstan-type string $store_url
	 * @var string $store_url
	 */
	define( 'WPF_STORE_URL', 'https://wpfusion.com' );
}

if ( ! defined( 'WPF_EDD_ITEM_ID' ) ) {
	/**
	 * Easy Digital Downloads item ID for WP Fusion.
	 *
	 * @phpstan-type string $edd_item_id
	 * @var string $edd_item_id
	 */
	define( 'WPF_EDD_ITEM_ID', '4721' );
}