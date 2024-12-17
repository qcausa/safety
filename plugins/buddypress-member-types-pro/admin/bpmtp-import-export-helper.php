<?php

// If file access directly over web. It will exit
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BPMTP_Import_Export_Helper
 */
class BPMTP_Import_Export_Helper {

	/**
	 * Class instance
	 *
	 * @var BPMTP_Import_Export_Helper
	 */
	private static $instance = null;

	/**
	 * The constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Get instance
	 *
	 * @return BPMTP_Import_Export_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup menu and and link to member type screen
	 */
	private function setup() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 100 );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_links' ) );
	}

	/**
	 * Adding submenu pages
	 */
	public function register_menu() {
		add_submenu_page( null, __( 'Import', 'buddypress-member-types-pro' ), __( 'Import', 'buddypress-member-types-pro' ), 'delete_users', 'import-member-types', array( $this, 'render_import_screen' ) );
		add_submenu_page( null, __( 'Export', 'buddypress-member-types-pro' ), __( 'Export', 'buddypress-member-types-pro' ), 'delete_users', 'export-member-types', array( $this, 'render_export_screen' ) );
	}

	/**
	 * Adding links to member type screen to import and export member types.
	 *
	 * @param string $which top, bottom.
	 */
	public function add_links( $which ) {
		if ( 'top' == $which ) {
			echo sprintf( '<div class="alignleft actions"><a href="%s" class="button">%s</a></div>', admin_url( 'users.php?page=import-member-types' ), __( 'Import', 'buddypress-member-types-pro' ) );
			echo sprintf( '<div class="alignleft actions"><a href="%s" class="button">%s</a></div>', admin_url( 'users.php?page=export-member-types' ), __( 'Export', 'buddypress-member-types-pro' ) );
		}
	}

	/**
	 * Render import screen
	 */
	public function render_import_screen() {
		?>
		<div class="wrap">
			<h2><?php _ex( 'Import Member Types', 'Page heading', 'buddypress-member-types-pro' ) ?></h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<?php _e( "import" ) ?>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	/**
	 * Render export screen
	 */
	public function render_export_screen() {
		?>
		<div class="wrap">
			<h2><?php _ex( 'Export Member Types', 'Page heading', 'buddypress-member-types-pro' ) ?></h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<?php _e( "export" ) ?>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}
}

BPMTP_Import_Export_Helper::get_instance();

