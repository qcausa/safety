<?php
/**
 * Bootstrapper. Initializes the plugin.
 *
 * @package    BPGTC
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace PressThemes\BPGTC\Bootstrap;

use PressThemes\BPGTC\Admin\Admin_Loader;
use PressThemes\BPGTC\BuddyPress\Groups\BP_Nouveau_Compat;
use PressThemes\BPGTC\Handlers\Ajax_handler;
use PressThemes\BPGTC\BuddyPress\Groups\Group_Loader;
use PressThemes\BPGTC\BuddyPress\Groups\Group_Tabs_Actions;

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Bootstrapper.
 */
class Bootstrapper {

	/**
	 * Setup the bootstrapper.
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Bind hooks
	 */
	private function setup() {

		add_action( 'bp_loaded', array( $this, 'load' ) );
		add_action( 'bp_init', array( $this, 'load_translations' ) );

		Assets_Loader::boot();
	}

	/**
	 * Load core functions/template tags.
	 * These are non auto loadable constructs.
	 */
	public function load() {

		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		$path = bpgtc_group_tabs()->get_path();

		$files = array(
			'src/core/bpgtc-functions.php',
		);

		foreach ( $files as $file ) {
			require_once $path . $file;
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			require_once $path . 'vendors/cmb2/init.php';
			Admin_Loader::boot();
		}

		Group_Tabs_Actions::boot();
		BP_Nouveau_Compat::boot();
		Ajax_handler::boot();
	}

	/**
	 * Load translations.
	 */
	public function load_translations() {
		load_plugin_textdomain(
			'buddypress-group-tabs-creator-pro',
			false,
			basename( bpgtc_group_tabs()->get_path() ) . '/languages'
		);
	}
}
