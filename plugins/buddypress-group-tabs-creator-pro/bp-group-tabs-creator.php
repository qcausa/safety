<?php
/**
 * Plugin Name: BuddyPress Group Tabs Creator Pro
 * Version: 1.3.2
 * Plugin URI: https://buddydev.com/plugins/buddypress-group-tabs-creator-pro/
 * Author: BuddyDev
 * Author URI: https://BuddyDev.com
 * Description: Take BuddyPress to the next level. Add tabs to group with inline content. Create unlimited tabs.
 *
 * License: GPL2 or above
 */

use PressThemes\BPGTC\Bootstrap\Autoloader;
use PressThemes\BPGTC\Bootstrap\Bootstrapper;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Group Tabs Pro helper
 */
class BPGTC_Group_Tabs_Pro {

	/**
	 * Singleton instance
	 *
	 * @var BPGTC_Group_Tabs_Pro
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

		$this->init();
	}

	/**
	 * Get singleton instance
	 *
	 * @return BPGTC_Group_Tabs_Pro
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
	public function init() {
		$this->bootstrap();
		$this->setup();
	}

	/**
	 * Boot
	 */
	private function bootstrap() {

		// Load autoloader.
		require_once  $this->path . 'src/bootstrap/class-autoloader.php';

		// Register autoloader.
		spl_autoload_register( new Autoloader( 'PressThemes\\BPGTC\\', __DIR__ . '/src/' ) );
	}

	/**
	 * Callbacks to necessaries hooks
	 */
	public function setup() {
		Bootstrapper::boot();
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

		if ( is_admin() && function_exists( 'get_current_screen' ) && function_exists('bpgtc_get_post_type') && bpgtc_get_post_type() === get_current_screen()->post_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if Group Tabs Creator Pro is network active.
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

/**
 * Helper method to access BPGTC_Group_Tabs_Pro instance
 *
 * @return BPGTC_Group_Tabs_Pro
 */
function bpgtc_group_tabs() {
	return BPGTC_Group_Tabs_Pro::get_instance();
}

bpgtc_group_tabs();
