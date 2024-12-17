<?php
/**
 * Member Types Pro - Conditional registration helper.
 *
 * @package BuddyPress_Member_Types_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Members Type to BuddyPress groups association.
 */
class BPMTP_Conditional_Registration_Helper {

	private $base_fields = array();
	private $member_types_count = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup hooks
	 */
	public function setup() {
		// force fetch all fields, irrespective of availability to member types.
		add_filter( 'bp_after_has_profile_parse_args', array( $this, 'filter_profile_loop_args' ) );
		// On activation, ensure the data integrity.
		add_action( 'bp_core_activated_user', array( $this, 'update_saved_fields' ), 2 );
		// load assets on signup page.
		add_action( 'bp_enqueue_scripts', array( $this, 'load_assets' ) );
		//add_filter( 'bp_get_the_profile_field_is_required', array( $this, 'filter_field_required_attribute' ), 10, 2 );

		add_filter( 'bp_xprofile_field_edit_html_elements', array( $this, 'filter_html_atts' ) );

		add_action( 'bp_signup_pre_validate', array( $this, 'setup_pre_validate' ), 0 );
	}

	/**
	 * Update field ids to keep a tab on required fields.
	 */
	public function setup_pre_validate() {

		if ( ! $this->is_enabled() || ! bp_is_active( 'xprofile' ) || empty( $_POST['signup_profile_field_ids'] ) ) {
			return;
		}

		global $wpdb;
		$table = buddypress()->profile->table_name_fields;
		// Find the first member type field in base profile field group.
		$query     = "SELECT id, type FROM {$table} WHERE (type = %s or type = %s) AND group_id = %d";
		$mtp_field = $wpdb->get_row( $wpdb->prepare( $query, 'membertype', 'membertypes', 1 ) );

		// If no field founmd or a field found but no data was submitted for it, let BuddyPress do the process.
		if ( empty( $mtp_field ) || empty( $_POST[ 'field_' . $mtp_field->id ] ) ) {
			// No member type field in first group
			// we don't need to do anything.
			return;
		}

		// Get the member type field value which was submitted by the user.
		$member_types = $_POST[ 'field_' . $mtp_field->id ];

		$exclude_field_ids = array();

		// make sure that for single field, only single value is submitted.
		if ( 'membertype' === $mtp_field->type ) {
			$member_types = (array) $member_types;
			if ( count( $member_types ) > 1 ) {
				return; // no fooling around.
			}
		}

		// validate member types.
		$selected_member_types = array();
		foreach ( $member_types as $member_type ) {
			$mtp_object = bp_get_member_type_object( $member_type );
			if ( ! $mtp_object ) {
				continue;
			}
			$selected_member_types[] = $member_type;
		}

		if ( empty( $selected_member_types ) ) {
			return;
		}

		// we are good if we are here.
		$map = $this->get_fields_map();

		// Now, There can be multiple conditional fields.
		foreach ( $map as $field_id => $field_member_types ) {
			// Is field available for user's submitted member type?
			$available = array_intersect( $field_member_types, $selected_member_types );
			if ( $available ) {
				continue;
			}

			$fid = str_replace( 'field_', '', $field_id );
			if ( xprofile_check_is_required_field( $fid ) ) {
				$exclude_field_ids[] = $fid;
			}
		}

		// Let's compact any profile field info into an array.
		$profile_field_ids                 = explode( ',', $_POST['signup_profile_field_ids'] );
		$profile_field_ids                 = array_diff( $profile_field_ids, $exclude_field_ids );
		$_POST['signup_profile_field_ids'] = join( ',', $profile_field_ids );
	}

	/**
	 * Filter html attributes to remove 'required' from the input attributes.
	 *
	 * @param $atts
	 *
	 * @return array
	 */
	public function filter_html_atts( $atts ) {

		if ( ! $this->is_enabled() || is_user_logged_in() ) {
			return $atts;
		}
		// load member types count.
		if ( is_null( $this->member_types_count ) ) {
			$this->member_types_count = count( bp_get_member_types( array() ) ) + 1;
		}

		$field        = xprofile_get_field( bp_get_the_profile_field_id() );
		$member_types = $field->get_member_types();

		if ( ! empty( $member_types ) && count( $member_types ) != $this->member_types_count ) {
			$atts = array_diff( $atts, array( 'required' ) );
			unset( $atts['aria-required'] );
		}

		return $atts;
	}

	/*
	public function filter_field_required_attribute( $is, $field_id ) {
		// we do not modify it for logged in user or if the conditions are not enabled.
		if ( ! $this->is_enabled() || is_user_logged_in() ) {
			return $is;
		}
		if ( is_null( $this->member_types_count ) ) {
			$this->member_types_count = count( bp_get_member_types( array() ) ) + 1;
		}

		$field        = xprofile_get_field( $field_id );
		$member_types = $field->get_member_types();
		if ( ! empty( $member_types ) && count( $member_types ) != $this->member_types_count ) {
			if( isset( $this->base_fields[$field_id])) {
				$is = false;// mark not required.
			} else{
				$this->base_fields[$field_id] = true;
			}
		}

		return $is;
	}*/

	/**
	 * Filter has profile args. Force load all fields if needed.
	 *
	 * @param array $args args.
	 *
	 * @return array
	 */
	public function filter_profile_loop_args( $args ) {

		if ( ! $this->is_enabled() ) {
			return $args;
		}

		// Force to fetch fields for all member types.
		$args['member_type'] = false;

		return $args;
	}

	/**
	 * Delete the fields on new user activation/profile update that do not conform to our condition
	 * and yes, I am the boss here, don' ask me the logic :P
	 *
	 * @param int $user_id user id.
	 */
	public function update_saved_fields( $user_id ) {

		$map          = $this->get_fields_map();
		$member_types = bp_get_member_type( $user_id, false );
		// A User without a member type assigned.
		if ( empty( $member_types ) ) {
			$member_types = array( 'null' );// yes, 'null' is special value used by field member types.
		}

		// Now, There can be multiple conditional fields.
		foreach ( $map as $field_id => $field_member_types ) {
			// Is field available for user's member type?
			$available = array_intersect( $field_member_types, $member_types );
			// for each field triggering the condition, get the field data for this field.
			$field_id = (int) str_replace( 'field_', '', $field_id );
			// Remove the data.
			if ( ! $available ) {
				xprofile_delete_field_data( $field_id, $user_id );
			}
		}
	}
	/**
	 * Load js if enabled.
	 */
	public function load_assets() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_register_script( 'bpmtp-conditional-registration', bpmtp_member_types_pro()->get_url() . 'assets/buddypress-member-type-registration.js', array( 'jquery','underscore' ), null, false );
		wp_localize_script( 'bpmtp-conditional-registration', 'BPMTPFieldsMap', $this->get_fields_map() );
		wp_enqueue_script( 'bpmtp-conditional-registration' );
	}

	/**
	 * Is enabled?
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return bp_is_register_page();
	}

	/**
	 * Get field availability map for member types.
	 *
	 * @return array
	 */
	private function get_fields_map() {
		$args = array(
			'user_id'                => false,
			'member_type'            => false,
			'profile_group_id'       => 1,
			'hide_empty_groups'      => true,
			'hide_empty_fields'      => false,
			'fetch_fields'           => true,
			'fetch_field_data'       => false,
			'fetch_visibility_level' => false,
			'exclude_groups'         => false,
			'exclude_fields'         => false,
			'update_meta_cache'      => false,
		);

		if ( function_exists( 'bp_xprofile_get_signup_field_ids' ) ) {
			$args['profile_group_id']   = false;
			$args['signup_fields_only'] = true;
		}

		$groups = bp_xprofile_get_groups( $args );

		if ( empty( $groups ) ) {
			return array();
		}

		$map = array();
		foreach ( (array) $groups as $group ) {

			if ( empty( $group->fields ) ) {
				continue;
			}

			foreach ( $group->fields as $field ) {
				$map[ 'field_' . $field->id ] = $field->get_member_types();
			}
		}

		return $map;
	}

}

new BPMTP_Conditional_Registration_Helper();
