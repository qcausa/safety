<?php
/**
 * Plugin Name: BuddyPress User Profile Tabs Creator Pro
 * Version: 1.2.6
 * Plugin URI: https://buddydev.com/plugins/buddypress-user-profile-tabs-creator-pro/
 * Author: BuddyDev
 * Author URI: https://BuddyDev.com
 * Description: Take BuddyPress to the next level. Add tabs to user profile with inline content. Create unlimited components/tabs.
 *
 * License: GPL2 or above
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * The name BuddyPress User profile Tabs Creator is for search engine,
 * the real name is BuddyPress profile tabs creator :)
 */
/**
 * Profile Tabs Pro helper
 */
class BPPTC_Profile_Tabs_Pro {

	/**
	 * Singleton instance
	 *
	 * @var BPPTC_Profile_Tabs_Pro
	 */
	private static $instance = null;

	/**
	 * Absolute path to this plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Absolute url to this plugin directory.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Constructor
	 */
	private function __construct() {

		$this->path     = plugin_dir_path( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );

		$this->setup();
	}

	/**
	 * Get singleton instance
	 *
	 * @return BPPTC_Profile_Tabs_Pro
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Setup hooks.
	 */
	public function setup() {

		add_action( 'bp_loaded', array( $this, 'load' ) );
		add_action( 'bp_init', array( $this, 'load_translations' ) );
	}

	/**
	 * Load required files
	 */
	public function load() {

		$files = array(
			'core/bpptc-profile-tabs-functions.php',
			'core/bpptc-shortcodes.php',
			'core/class-bpptc-profile-tabs-actions.php',
			'core/class-bpptc-profile-tabs-tab-entry.php',
			'core/class-bpptc-profile-tabs-subnav-entry.php',
		);

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			$files[] = 'admin/bpptc-profile-tabs-admin-misc.php'; // misc settings etc.

			$files[] = 'vendors/cmb2/init.php';
			$files[] = 'admin/bpptc-profile-tabs-edit-helper.php'; // edit screen helper.
			$files[] = 'admin/bpptc-profile-tabs-list-helper.php';// Edit list helper.
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Load translations.
	 */
	public function load_translations() {
		load_plugin_textdomain( 'buddypress-user-profile-tabs-creator-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Get the main plugin file.
	 *
	 * @return string
	 */
	public function get_file() {
		return __FILE__;
	}

	/**
	 * Get absolute url to this plugin dir.
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Get absolute path to this plugin dir.
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Is it the add/edit tab screen.
	 *
	 * @return bool
	 */
	public function is_admin_add_edit() {
		if ( is_admin() && function_exists( 'get_current_screen' ) && bpptc_get_post_type() === get_current_screen()->post_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if Profile Tabs Creator Pro is network active.
	 *
	 * @return bool
	 */
	public function is_network_active() {

		if ( ! is_multisite() ) {
			return false;
		}

		// Check the sitewide plugins array.
		$base    = $this->basename;
		$plugins = get_site_option( 'active_sitewide_plugins' );

		if ( ! is_array( $plugins ) || ! isset( $plugins[ $base ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Is the PHp version good enough for us?
	 * Checks if php version >= 5.4
	 *
	 * @return boolean
	 */
	public function has_php_version() {
		return version_compare( PHP_VERSION, '5.4', '>=' );
	}
}

// Instantiate.
BPPTC_Profile_Tabs_Pro::get_instance();

/**
 * Helper method to access  BPPTC_Profile_Tabs_Pro instance
 *
 * @return BPPTC_Profile_Tabs_Pro
 */
function bpptc_profile_tabs_pro() {
	return BPPTC_Profile_Tabs_Pro::get_instance();
}
