<?php
/**
 * Plugin Name: BuddyPress Member Types Pro
 * Version: 1.5.6
 * Plugin URI: https://buddydev.com/plugins/buddypress-member-types-pro/
 * Author: BuddyDev
 * Author URI: https://BuddyDev.com
 * Description: Allows site admins to create/manage Member types from WordPress dashboard. Also, Includes functionality to bulk assign member type to users.
 * License: GPL2 or above
 */

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Member Types Pro helper
 */
class BPMTP_Member_Types_Pro {

	/**
	 * Singleton instance
	 *
	 * @var BPMTP_Member_Types_Pro
	 */
	private static $instance = null;

	/**
	 * Absolute path to this plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Absolute url tot his plugin directory.
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
	 * Stores the shown field ids. A hack to make the field work with older version of themes.
	 *
	 * @var array
	 */
	private $shown_fields = array();

	/**
	 * Array of our registered field types.
	 *
	 * @var array
	 */
	private $field_types = array();

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
	 * @return BPMTP_Member_Types_Pro
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

		add_action( 'plugins_loaded', array( $this, 'admin_load' ), 9996 ); // pt-settings 1.0.2.
		add_action( 'bp_loaded', array( $this, 'load' ) );
		add_filter( 'bp_xprofile_get_field_types', array( $this, 'add_field_types' ) );
		// for old theme compat, show field.
		add_action( 'bp_custom_profile_edit_fields_pre_visibility', array( $this, 'may_be_show_field' ) );
		// save field meta.
		add_action( 'xprofile_fields_saved_field', array( $this, 'save_field_meta' ) );
		add_action( 'xprofile_fields_saved_field', array( $this, 'save_multi_field_meta' ) );

		add_action( 'bp_admin_enqueue_scripts', array( $this, 'load_admin_js' ) );
		add_action( 'plugins_loaded', array( $this, 'load_modules' ), 100 );
	}

	/**
	 * Load required files
	 */
	public function load() {

		$files = array(
			'core/bpmtp-functions.php',
			'core/class-bpmtp-url-parser.php',
			'core/class-bpmtp-shortcodes.php',
			'core/class-bpmtp-member-types-pro-actions.php',
			'core/class-bpmtp-redirection-manager.php',
			'core/class-bpmtp-member-types-pro-hooks.php',
			'core/class-bpmtp-member-types-pro-entry.php',
		);

		if ( bp_is_active( 'xprofile' ) ) {
			$files[] = 'core/field/class-bpmtp-xprofile-field-type-member-type.php';
			$files[] = 'core/field/class-bpmtp-xprofile-field-type-member-types.php';
			$files[] = 'core/field/class-bpmtp-xprofile-member-type-search-helper.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Load admin.
	 */
	public function admin_load() {

		if ( ! is_admin() || ! function_exists( 'buddypress' ) ) {
			return;
		}

		$files = array();

		$files[] = 'admin/bpmtp-admin-misc.php'; // edit screen helper.
		$files[] = 'admin/bpmtp-edit-helper.php'; // edit screen helper.
		$files[] = 'admin/bpmtp-list-helper.php';// member type list helper.

		if ( version_compare( buddypress()->version, '2.7.0', '<' ) ) {
			$files[] = 'admin/bpmtp-user-helper.php'; // user list helper for bulk manage.
		}

		if ( ! defined( 'DOING_AJAX' ) ) {

			$files[] = 'admin/pt-settings/pt-settings-loader.php';

			if ( is_main_site() ) {
				$files[] = 'admin/bpmtp-admin-settings.php';
			}

			if ( is_multisite() && $this->is_network_active() ) {
				$files[] = 'admin/bpmtp-network-admin-settings.php';// network admin settings page.
			}
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Load Extra Modules
	 */
	public function load_modules() {

		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		require_once $this->path . 'modules/class-bpmtp-xprofile-helper.php';
		require_once $this->path . 'modules/class-bpmpt-roles-helper.php';

		if ( bpmtp_get_option( 'enable_conditional_fields' ) ) {
			require_once $this->path . 'modules/class-bpmtp-conditional-registration-helper.php';
		}

		// WooCommerce membership exists. Let us add the support.
		if ( function_exists( 'wc_memberships' ) ) {
			if ( apply_filters( 'bpmtp_enable_wc_memberships_change_by_member_type', false ) ) {
				require $this->path . 'modules/class-bpmtp-wc-membership-helper.php';
			} else {
				require $this->path . 'modules/class-bpmtp-wc-member-type.php';
			}
		}

		if ( bp_is_active( 'groups' ) ) {
			require $this->path . 'modules/class-bpmtp-groups-helper.php';
		}

		if ( ! bp_get_option( 'bp-disable-avatar-uploads' ) ) {
			require $this->path . 'modules/class-bpmtp-avatar-helper.php';
		}

		if ( ! bp_get_option( 'bp-disable-cover-image-uploads' ) ) {
			require $this->path . 'modules/class-bpmtp-cover-image-helper.php';
		}

		// Paid Membership pro.
		if ( function_exists( 'pmpro_getAllLevels' ) ) {
			require $this->path . 'modules/class-bpmtp-pmpro-helper.php';
		}

		if ( class_exists( 'SFWD_LMS' ) ) {
			require $this->path . 'modules/class-bpmtp-learndash-group.php';
		}

		if ( class_exists( 'Ihc_Custom_BP_Endpoint' ) ) {
			require $this->path . 'modules/class-bpmtp-indeed-umpro-helper.php';
		}

		if ( class_exists( 'MeprCtrlFactory' ) ) {
			require_once $this->path . 'modules/class-bpmtp-memberpress-helper.php';
		}

		if ( class_exists( 'If_Menu' ) ) {
			require_once $this->path . 'integrations/if-menu-conditions.php';
		}


	}

	/**
	 * Add member type field.
	 *
	 * @param array $filed_types array of registered field types.
	 *
	 * @return array
	 */
	public function add_field_types( $filed_types ) {

		// You may be wondering why I am using array instead of $filed_types['membertype'] = 'class name'. Just for future updates to add more field types.
		$our_field_types = array(
			'membertype' => 'BPMTP_XProfile_Field_Type_MemberType',
			'membertypes' => 'BPMTP_XProfile_Field_Type_MemberTypes',
		);
		// store our list in the $this->field_types array.
		$this->field_types = array_keys( $our_field_types );

		return array_merge( $filed_types, $our_field_types );
	}

	/**
	 * Show field for older themes
	 * a work around for the themes that does not support newer hook.
	 */
	public function may_be_show_field() {

		$field_id = bp_get_the_profile_field_id();

		if ( ! $this->was_shown( $field_id ) && in_array( bp_get_the_profile_field_type(), $this->field_types ) ) {

			$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
			$field_type->edit_field_html();
		}
	}

	/**
	 * Mark the field as shown
	 *
	 * @param int $field_id Save the shown field id.
	 */
	public function set_shown( $field_id ) {
		$this->shown_fields[ 'field_' . $field_id ] = true;
	}

	/**
	 * Check if the given field was shown
	 *
	 * @param int $field_id Numeric field id to check if it was shown.
	 *
	 * @return boolean
	 */
	public function was_shown( $field_id ) {
		return isset( $this->shown_fields[ 'field_' . $field_id ] );
	}

	/**
	 * Load Js on New/Edit field.
	 */
	public function load_admin_js() {
		if ( get_current_screen()->id == 'users_page_bp-profile-setup' || get_current_screen()->id == 'users_page_bp-profile-setup-network' ) {

			wp_enqueue_script( 'fp-core-field-admin', $this->url . 'admin/assets/member-type-field-admin.js', array( 'jquery' ) );
		}
	}

	/**
	 * Save the text when the field is saved
	 *
	 * @param BP_XProfile_Field $field xprofile field.
	 */
	public function save_field_meta( $field ) {

		if ( 'membertype' !== $field->type ) {
			return;
		}

		// default to selectbox.
		$display_type = isset( $_POST['bpmtp-field-display-type'] ) ? trim( $_POST['bpmtp-field-display-type'] ) : 'selectbox';

		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_display_type', $display_type );

		$restriction = isset( $_POST['bpmtp-field-restriction'] ) ? trim( $_POST['bpmtp-field-restriction'] ) : 'all';
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_restriction', $restriction );

		$selected_types = isset( $_POST['bpmtp-field-selected-types'] ) ? $_POST['bpmtp-field-selected-types'] : array();

		if ( 'all' !== $restriction ) {
			bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_selected_types', $selected_types );
		} else {
			bp_xprofile_delete_meta( $field->id, 'field', 'bpmtp_field_selected_types' );
		}

		// Do we allow changing this field from profile?
		$allow_change = isset( $_POST['bpmtp-field-allow-edit'] ) ? absint( $_POST['bpmtp-field-allow-edit'] ) : 1;
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_allow_edit', $allow_change );

		// Do we allow linking to dir?
		$add_dir_link = isset( $_POST['bpmtp-field-link-to-dir'] ) ? absint( $_POST['bpmtp-field-link-to-dir'] ) : 1;
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_link_to_dir', $add_dir_link );

		$default = isset( $_POST['bpmtp-field-default-value'] ) ? trim( $_POST['bpmtp-field-default-value'] ) : '';
		// should we validate the member type?
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_default_value', $default );
	}

	/**
	 * Save the meta for Multi member type field.
	 *
	 * @param BP_XProfile_Field $field xprofile field.
	 */
	public function save_multi_field_meta( $field ) {

		if ( 'membertypes' !== $field->type ) {
			return;
		}
		// default to selectbox.
		$display_type = isset( $_POST['bpmtp-multi-field-display-type'] ) ? trim( $_POST['bpmtp-multi-field-display-type'] ) : 'selectbox';

		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_display_type', $display_type );

		$restriction = isset( $_POST['bpmtp-multi-field-restriction'] ) ? trim( $_POST['bpmtp-multi-field-restriction'] ) : 'all';
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_restriction', $restriction );

		$selected_types = isset( $_POST['bpmtp-multi-field-selected-types'] ) ? $_POST['bpmtp-multi-field-selected-types'] : array();

		if ( 'all' !== $restriction ) {
			bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_selected_types', $selected_types );
		} else {
			bp_xprofile_delete_meta( $field->id, 'field', 'bpmtp_field_selected_types' );
		}

		// Do we allow chaning this field from profile?
		$allow_change = isset( $_POST['bpmtp-multi-field-allow-edit'] ) ? absint( $_POST['bpmtp-multi-field-allow-edit'] ) : 1;
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_allow_edit', $allow_change );

		// Do we allow linking to dir?
		$add_dir_link = isset( $_POST['bpmtp-multi-field-link-to-dir'] ) ? absint( $_POST['bpmtp-multi-field-link-to-dir'] ) : 1;
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_link_to_dir', $add_dir_link );

		$default = isset( $_POST['bpmtp-multi-field-default-value'] ) ? trim( $_POST['bpmtp-multi-field-default-value'] ) : '';
		// should we validate the member type?
		bp_xprofile_update_field_meta( $field->id, 'bpmtp_field_default_value', $default );
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
	 * Is it the add/edit member type screen
	 *
	 * @return bool
	 */
	public function is_admin_add_edit() {
		if ( is_admin() && function_exists( 'get_current_screen' ) && bpmtp_get_post_type() === get_current_screen()->post_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if Member types pro is network active
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
	function has_php_version() {
		return version_compare( PHP_VERSION, '5.4', '>=' );
	}
}

// Instantiate.
BPMTP_Member_Types_Pro::get_instance();

/**
 * Helper method to access  BP_Member_Types_Pro instance
 *
 * @return BPMTP_Member_Types_Pro
 */
function bpmtp_member_types_pro() {
	return BPMTP_Member_Types_Pro::get_instance();
}
