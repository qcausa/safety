<?php
// No Direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

use Press_Themes\PT_Settings\Page;

/**
 * BuddyPress Admin Power Tools Admin
 */
class BPMTP_Admin_Settings {

	/**
	 * Setting page
	 *
	 * @var Page
	 */
	private $page;

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'bpmtp-admin';

	/**
	 * Setup hooks.
	 */
	public function setup() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Init settings for the current blog setting.
	 */
	public function init() {

		if ( $this->is_options_page() || $this->is_bpmtp_settings_page() ) {
			$this->setup_settings();
		}
	}

	/**
	 * Add Menu page
	 */
	public function add_menu() {

		if ( ! apply_filters( 'bpmtp_show_admin_settings', is_main_site() ) ) {
			return;
		}

		add_options_page(
			_x( 'Member Type Settings', 'admin page title', 'buddypress-member-types-pro' ),
			_x( 'Member Types', 'admin menu title', 'buddypress-member-types-pro' ),
			'create_users', // Change to make sure admin only.
			$this->page_slug,
			array( $this, 'render' )
		);

	}

	/**
	 * Render the dashboard.
	 */
	public function render() {
		$this->page->render();
	}

	/**
	 * Setup settings for the site/blog.
	 */
	public function setup_settings() {

		$page       = new Page( 'bpmtp-settings' );
		$this->page = $page;
		// Add a panel to to the admin
		// A panel is a Tab and what comes under that tab.
		$member_types = bp_get_member_types( array(), 'objects' );

		// ask user to create member types if none found.
		if ( empty( $member_types ) ) {
			$panel = $page->add_panel( 'no-member-type-found', _x( 'General', 'setting panel title', 'buddypress-member-types-pro' ), _x( 'Please create some member type and then you can update the settings here', 'admin panel description', 'buddypress-member-types-pro' ) );

			return;
		}

		$roles = get_editable_roles();

		$this->add_roles_panel( $page, $member_types, $roles );
		do_action( 'bpmtp_admin_settings_page', $page );

		$page->init();
	}

	/**
	 * Add panel for mapping roles.
	 *
	 * @param Page  $page page object.
	 * @param array $member_types one or more member types.
	 * @param array $roles one or more roles.
	 */
	public function add_roles_panel( $page, $member_types, $roles ) {
		$doc_link = 'https://buddydev.com/docs/buddypress-member-types-pro/assigning-buddypress-member-types-based-wordpress-roles/';

		// changing roles on the sub site are not going to affect member type.
		if ( ! is_main_site() ) {
			return ;
		}
		// Allow mapping roles to the member type, changing role will update member type.
		$panel   = $page->add_panel( 'roles', _x( 'Roles', 'setting panel title', 'buddypress-member-types-pro' ) );
		$section = $panel->add_section( 'roles-section',  _x( 'Roles to member type association', 'Admin settings section title', 'buddypress-member-types-pro' ), sprintf( _x( 'You can update WordPress role to member type mapping here. Changing role will update the member type accordingly. View <a href="%s" target="_blank">documentation</a>', 'admin section description', 'buddypress-member-types-pro' ), $doc_link ) );

		$member_types_list = array();

		foreach ( $member_types as $key => $type ) {
			$member_types_list[ $key ] = $type->labels['singular_name'];
		}

		foreach ( $roles as $key => $role ) {
			$section->add_field( array(
				'name'    => 'role_' . $key . '_member_types',
				'label'   => $role['name'],
				'type'    => 'multicheck',
				'options' => $member_types_list,
				'default' => array(),
			) );
		}


		$panel = $page->add_panel( 'misc-settings', _x( 'Miscellaneous', 'setting panel title', 'buddypress-member-types-pro' ) );

		$doc_url = 'https://buddydev.com/docs/buddypress-member-types-pro/conditional-buddypress-registration-fields-based-on-buddypress-member-types/';

		$section_desc = sprintf( __( 'Please see our documentation for <a href="%s">Conditional Registration page</a> for more details', 'buddypress-member-types-pro' ), $doc_url );

		$section = $panel->add_section( 'registration-option', _x( 'Registration', 'Admin settings section title', 'buddypress-member-types-pro' ), $section_desc );

		$section->add_field(
			array(
				'name'    => 'enable_conditional_fields',
				'label'   => __( 'Enable Member Type based Conditional registration fields', 'buddypress-member-types-pro' ),
				'type'    => 'checkbox',
				'default' => 0,
				'desc'    => __( 'If enabled, Registration page honours fields available to a member type. Please make sure to read the docs.', 'buddypress-member-types-pro' ),
			)
		);

		do_action( 'bpmtp_admin_settings_page', $page );

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

}
$bpmtp_admin = new BPMTP_Admin_Settings();
$bpmtp_admin->setup();
