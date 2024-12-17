<?php
// No Direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

use Press_Themes\PT_Settings\Page;

/**
 * Member Types Pro Network admin settings.
 */
class BPMTP_Network_Admin_Settings {

	/**
	 * Setting page
	 *
	 * @var Page
	 */
	private $page;


	/**
	 * Setting page
	 *
	 * @var Page
	 */
	private $subsite_page;


	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'bpmtp-admin';

	/**
	 * Network option name.
	 *
	 * @var string
	 */
	private $option_name = 'bpmtp-network-settings';


	/**
	 * Subsite option name.
	 *
	 * @var string
	 */
	private $subsite_option_name = 'bpmtp-settings';

	/**
	 * Setup hooks.
	 */
	public function setup() {
		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'network_admin_menu', array( $this, 'add_menu' ) );

		add_action( 'admin_menu', array( $this, 'add_subsite_menu' ) );

		// Wp setting api does not save in site meta, we will sync.
		add_action( 'pre_update_option_' . $this->option_name, array( $this, 'sync_options' ), 10, 2 );
	}

	/**
	 * Init settings.
	 */
	public function init() {
		$is_options_page = $this->is_options_page();
		if (  $is_options_page || $this->is_network_admin() ) {
			$this->register_settings();
		}

		if ( $is_options_page || $this->is_bpmtp_settings_page() ) {
			$this->register_subsite_settings();
		}

	}

	/**
	 * Add Menu
	 */
	public function add_menu() {

		add_submenu_page('settings.php',
			_x( 'Member Type Settings', 'admin page title', 'buddypress-member-types-pro' ),
			_x( 'Member Types', 'admin menu title', 'buddypress-member-types-pro' ),
			'manage_options', // Change to make sure admin only.
			$this->page_slug,
			array( $this, 'render' )
		);

	}

	/**
	 * ADD OPTION ON SUB SITE TO ALLOW MAPPING OF MEMBER TYPE TO ROLE.
	 */
	public function add_subsite_menu() {

		// Add menu on sub site.
		if ( ! is_super_admin() || is_main_site() || ! bpmtp_use_multisite_local_role_association() ) {
			return;
		}

		add_options_page(
			_x( 'Member Type Settings', 'admin page title', 'buddypress-member-types-pro' ),
			_x( 'Member Types', 'admin menu title', 'buddypress-member-types-pro' ),
			'manage_options', // Change to make sure admin only.
			$this->page_slug,
			array( $this, 'render_subsite' )
		);

	}

	/**
	 * Render the dashboard.
	 */
	public function render() {
		$this->page->render();
	}

	/**
	 * Render the dashboard fo sub site.
	 */
	public function render_subsite() {
		$this->subsite_page->render();
	}

	/**
	 * Init settings for network.
	 */
	public function register_settings() {
		$page = new Page( $this->option_name );
		$page->set_network_mode();

		$this->page = $page;
		// Add a panel to to the admin
		// A panel is a Tab and what comes under that tab.
		$panel = $page->add_panel( 'multisite', _x( 'Multisite Options', 'setting panel title', 'buddypress-member-types-pro' ) );

		$section = $panel->add_section( 'multisite-settings', _x( 'Global Settings', 'setting section title', 'buddypress-member-types-pro' ) );

		$member_types = bp_get_member_types( array(), 'objects' );

		$site_role_doc_link = 'https://buddydev.com/docs/buddypress-member-types-pro/buddypress-member-types-multisite-settings/';


		$section->add_field( array(
			'name'    => 'add_user_to_site_on_registration',
			'label'   => _x( 'Add user to the site on registration?', 'setting option label', 'buddypress-member-types-pro' ),
			'type'    => 'radio',
			'options' => array(
				1  => _x( 'Yes', 'setting option', 'buddypress-member-types-pro' ),
				0  => _x( 'No', 'setting option', 'buddypress-member-types-pro' ),
				),
			'default'   => 1,
			'desc' => __( 'BuddyPress does not add user to the site on registration/activation. If you enable this option, User will be added to the site/blog on which they activate their account.', 'buddypress-member-types-pro' ),
		) );

		if ( ! empty( $member_types ) ) {


			$section->add_field( array(
				'name'    => 'allow_site_role',
				'label'   => _x( 'How to associate Role & Member Type on sub site(Other than your main site)?', 'setting option label', 'buddypress-member-types-pro' ),
				'type'    => 'radio',
				'options' => array(
					'none'    => _x( "Don't do anything on sub site", 'setting option', 'buddypress-member-types-pro' ),
					'site'    => _x( 'Let me choose it on sub site', 'setting option', 'buddypress-member-types-pro' ),
					'network' => _x( 'Use default association(as specified below)', 'setting option', 'buddypress-member-types-pro' ),
				),
				'default' => 'none',
				'desc'    => sprintf( _x( 'Please see the <a href="%s">documentation</a> for help.', 'setting help text', 'buddypress-member-types-pro' ), $site_role_doc_link ),
			) );

			$section = $panel->add_section( 'multisite-role-settings', _x( 'Members Types to Roles for Sub sites.', 'setting section title', 'buddypress-member-types-pro' ) );

			$roles      = get_editable_roles();
			$roles_list = array();
			foreach ( $roles as $key => $role ) {
				$roles_list[ $key ] = $role['name'];// save.
			}

			foreach ( $member_types as $key => $type ) {
				$section->add_field( array(
					'name'    => 'member_type_' . $key . '_roles',
					'label'   => $type->labels['singular_name'],
					'type'    => 'multicheck',
					'options' => $roles_list,
				) );
			}

		}

		do_action( 'bpmtp_network_admin_settings_page', $page );

		$page->init();
	}


	/**
	 * Register settings for sub site.
	 */
	public function register_subsite_settings() {

		$page = new Page( $this->subsite_option_name );
		$this->subsite_page = $page;
		// Add a panel to to the admin
		// A panel is a Tab and what comes under that tab.
		$member_types = bp_get_member_types( array(), 'objects' );

		// ask user to create member types if none found.
		if ( empty( $member_types ) ) {
			$panel = $page->add_panel( 'no-member-type-found', _x( 'General', 'setting panel title', 'buddypress-member-types-pro' ), _x( 'Please create some member type and then you can update the settings here.', 'admin panel description', 'buddypress-member-types-pro' ) );
			return ;
		}

		$roles = get_editable_roles();

		// check and see if we need to add a mapping from member type to roles(On main site we don't).
		$this->add_member_type_panel( $page, $member_types, $roles );

		do_action( 'bpmtp_subsite_admin_settings_page', $page );

		$page->init();
	}

	/**
	 * Add a panel on the sub site if enabled.
	 *
	 * @param Page $page page object.
	 */
	public function add_member_type_panel( $page, $member_types, $roles ) {

		$panel = $page->add_panel( 'member-types', _x( 'Member Types', 'Admin settings panel title', 'buddypress-member-types-pro' ) );

		$section = $panel->add_section( 'member-type-role-settings', _x( 'Members Types to Roles.','setting option label', 'buddypress-member-types-pro' ) );


		$roles_list = array();
		foreach ( $roles as $key => $role ) {
			$roles_list[ $key ] = $role['name'];// save.
		}

		foreach ( $member_types as $key => $type ) {
			$section->add_field( array(
				'name'    => 'member_type_' . $key . '_roles',
				'label'   => $type->labels['singular_name'],
				'type'    => 'multicheck',
				'options' => $roles_list,
			) );
		}

	}
	/**
	 * Is it the options.php page that saves settings?
	 *
	 * @return bool
	 */
	private function is_options_page() {
		global $pagenow;

		// We need to load on options.php otherwise settings won't be reistered.
		if ( 'options.php' === $pagenow ) {
			return true;
		}

		return false;
	}

	/**
	 * Is network admin settings page.
	 *
	 * @return bool
	 */
	public function is_network_admin() {
		return is_network_admin() && isset( $_GET['page'] ) && ( $this->page_slug === $_GET['page'] );
	}

	/**
	 * Sync option to the site meta.
	 *
	 * @param mixed $value value of the meta.
	 * @param mixed $old_value old value.
	 *
	 * @return mixed
	 */
	public function sync_options( $value, $old_value ) {
		update_site_option( $this->option_name, $value );
		return $value;
	}

	/**
	 * Is the admin settings page for us?
	 *
	 * @return bool
	 */
	public function is_bpmtp_settings_page() {

		if ( isset( $_GET['page'] ) && $this->page_slug === $_GET['page'] ) {
			return true;
		}

		return false;
	}


}
$bpmtp_netwrok_admin = new BPMTP_Network_Admin_Settings();
$bpmtp_netwrok_admin->setup();

