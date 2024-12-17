<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class to register the internal member type post type and the actual Member type
 */
class BPMTP_Xprofile_Mapping_Helper {

	/**
	 * Is xprofile field change the current trigger?
	 *
	 * @var bool
	 */
	private $field_updating = false;

	/**
	 * Is member type updating?
	 *
	 * @var bool
	 */
	private $member_type_updating = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		// on account activation, trigger xprofile update for the member type fields.
		add_action( 'bp_core_activated_user', array( $this, 'update_field_on_activation' ), -1 );

		// Update member type when field is updated.
		add_action( 'xprofile_data_after_save', array( $this, 'update_member_type' ) );

		// for multi field.
		add_action( 'xprofile_data_after_save', array( $this, 'update_member_types' ) );


		// Sync field on member type update.
		add_action( 'bpmtp_set_member_type', array( $this, 'update_field_data' ), 10, 3 );
		add_action( 'bpmtp_set_member_type', array( $this, 'update_field_data_for_multi' ), 10, 3 );

		// Remove member type, clear field data.
		add_action( 'bp_remove_member_type', array( $this, 'clear_member_type_field' ), 10, 2 );
		//add_action( 'bp_remove_member_type', array( $this, 'clear_member_type_field' ), 10, 2 );

	}


	/**
	 * We use it as a work around to go overt BuddyPress's inconsistency with role/field set.
	 *
	 * @param int $user_id numeric user id.
	 */
	public function update_field_on_activation( $user_id ) {

		// on multisite, if the option is enable, add user to current blog.
		if ( is_multisite() && bpmtp_get_network_option('add_user_to_site_on_registration', 1 ) ) {
			add_user_to_blog( get_current_blog_id(), $user_id, get_option( 'default_role' ) );
		}

		// Get the field ids.
		$fields = bpmtp_get_member_type_field_ids();
		$fields = array_merge( $fields, bpmtp_get_multi_member_type_field_ids() );

		if ( empty( $fields ) ) {
			return;
		}

		global $wpdb;

		$list = '(' . join( ',', $fields ) . ')';

		$table = buddypress()->profile->table_name_data;

		$query = $wpdb->prepare( "SELECT field_id, value FROM {$table} WHERE field_id IN {$list} AND user_id = %d", $user_id );

		$data_fields = $wpdb->get_results( $query );

		foreach ( $data_fields as $field ) {
			xprofile_set_field_data( $field->field_id, $user_id, maybe_unserialize( $field->value ) );// It will trigger the xprofile set field data.
		}
	}


	/**
	 * Update the member type of a user when member type field is updated
	 *
	 * @param Object $data_field Xprofile data object.
	 */
	public function update_member_type( $data_field ) {

		$field = xprofile_get_field( $data_field->field_id );
		// we only need to worry about member type field.
		if ( 'membertype' !== $field->type ) {
			return;
		}

		// do not update member type unless bp_core_Activated has been called.
		if ( ! $this->is_xprofile_update() ) {
			return;
		}

		if ( $this->member_type_updating ) {
			return;
		}

		$this->field_updating = true;
		$user_id              = $data_field->user_id;
		$member_type          = maybe_unserialize( $data_field->value );

		$field_id = $data_field->field_id;
		// only affect field if the field is related to this member type.
		$restriction    = bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_restriction', true );
		$is_restricted = false;
		if ( 'restricted' === $restriction ) {
			$is_restricted  = true;
			$allowed_types = (array) bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_selected_types', true );
		} else {
			$allowed_types = bp_get_member_types();
		}
		// remove all member types governed by the field?
		if ( empty( $member_type ) ) {
			foreach ( $allowed_types as $selected_type ) {

				if ( ! bp_has_member_type( $user_id, $selected_type ) ) {
					continue;
				}

				bp_remove_member_type( $user_id, $selected_type );
			}
			// all done.
			$this->field_updating = false;

			return;
		}

		// if we are here, It is not empty.
		// Remove all except the member type.
		foreach ( $allowed_types as $selected_type ) {

			if ( $selected_type == $member_type || ! bp_has_member_type( $user_id, $selected_type ) ) {
				continue;
			}

			bp_remove_member_type( $user_id, $selected_type );
		}

		// Add this member type.
		if ( ! bp_has_member_type( $user_id, $member_type ) && bp_get_member_type_object( $member_type ) ) {
			// in case of restricted, we append.
			bp_set_member_type( $user_id, $member_type, $is_restricted );
		}
		// all done.
		$this->field_updating = false;
	}


	/**
	 * Update the member types of a user when member types field is updated(Multi member type)
	 *
	 * @param Object $data_field Xprofile data object.
	 */
	public function update_member_types( $data_field ) {

		$field = xprofile_get_field( $data_field->field_id );

		// we only need to worry about member type field.
		if ( 'membertypes' !== $field->type ) {
			return;
		}

		// do not update member type unless bp_core_Activated has been called.
		if ( ! $this->is_xprofile_update() ) {
			return;
		}

		if ( $this->member_type_updating ) {
			return;
		}


		$this->field_updating = true;

		$user_id      = $data_field->user_id;
		$member_types = (array) maybe_unserialize( $data_field->value );

		$field_id = $data_field->field_id;
		// only affect field if the field is related to this member type.
		$restriction   = bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_restriction', true );
		$is_restricted = false;
		if ( 'restricted' === $restriction ) {
			$is_restricted = true;
			$allowed_types = (array) bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_selected_types', true );
		} else {
			$allowed_types = bp_get_member_types();
		}
		// remove all member types governed by the field?
		if ( empty( $member_types ) ) {
			foreach ( $allowed_types as $allowed_type ) {

				if ( ! bp_has_member_type( $user_id, $allowed_type ) ) {
					continue;
				}

				bp_remove_member_type( $user_id, $allowed_type );
			}
			// all done.
			$this->field_updating = false;

			return;
		}
		// Member Type is not empty.
		// if we are here, It is not empty.
		// Remove all except the member type.
		foreach ( $allowed_types as $allowed_type ) {

			if ( in_array( $allowed_type, $member_types ) || ! bp_has_member_type( $user_id, $allowed_type ) ) {
				continue;
			}

			bp_remove_member_type( $user_id, $allowed_type );
		}

		foreach ( $member_types as $member_type ) {
			// Add this member type.
			if ( ! bp_has_member_type( $user_id, $member_type ) && bp_get_member_type_object( $member_type ) ) {
				// in case of restricted, we append.
				bp_set_member_type( $user_id, $member_type, true ); // append.
			}
		}

		$this->field_updating = false;
	}

	/**
	 * Update field data when the member type is updated.
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append is it reset member type or append.
	 */
	public function update_field_data( $user_id, $member_type, $append = false ) {
		global $wpdb;
		// if the account is being deleted, there is no need to syc the fields
		// it will also help us break the cyclic dependency(infinite loop).
		if ( did_action( 'delete_user' ) || did_action( 'wpmu_delete_user' ) ) {
			return;
		}

		if ( $this->field_updating ) {
			return;
		}

		$fields = bpmtp_get_member_type_field_ids();

		if ( empty( $fields ) ) {
			return;
		}

		$this->member_type_updating = true;

		$list = '(' . join( ',', $fields ) . ')';

		$table = buddypress()->profile->table_name_data;

		$query = $wpdb->prepare( "SELECT field_id, value FROM {$table} WHERE field_id IN {$list} AND user_id = %d", $user_id );

		$data_fields = $wpdb->get_results( $query );

		// No value was set earlier for this field, we need to add one?
		if ( empty( $data_fields ) ) {

			foreach ( $fields as $field_id ) {
				$this->set_field_data( $user_id, $field_id, $member_type );
			}

			$this->member_type_updating = false;
			return;
		}

		// It will only run for member types.
		foreach ( $data_fields as $row ) {
			if ( $row->value == $member_type ) {
				continue;
				// no need to update.
			}
			// if we are here, sync it.
			$this->set_field_data( $user_id, $row->field_id, $member_type );
		}
		$this->member_type_updating = false;
	}


	/**
	 * Update field data when the member type is updated.
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append is it reset member type or append.
	 */
	public function update_field_data_for_multi( $user_id, $member_type, $append = false ) {
		global $wpdb;
		// if the account is being deleted, there is no need to syc the fields
		// it will also help us break the cyclic dependency(infinite loop).
		if ( did_action( 'delete_user' ) || did_action( 'wpmu_delete_user' ) ) {
			return;
		}

		$fields = bpmtp_get_multi_member_type_field_ids();

		if ( empty( $fields ) ) {
			return;
		}

		if ( $this->field_updating ) {
			return;
		}

		$this->member_type_updating = true;

		$list = '(' . join( ',', $fields ) . ')';

		$table = buddypress()->profile->table_name_data;

		$query = $wpdb->prepare( "SELECT field_id, value FROM {$table} WHERE field_id IN {$list} AND user_id = %d", $user_id );

		$data_fields = $wpdb->get_results( $query );

		// get all current member types of the user and save it.
		$member_types = bp_get_member_type( $user_id, false );
		// No value was set earlier for this field, we need to add one?
		if ( empty( $data_fields ) ) {

			foreach ( $fields as $field_id ) {
				$this->set_field_data( $user_id, $field_id, $member_types );
			}
			$this->member_type_updating = false;
			return;
		}

		// Reset all existing fields with the current member types.
		foreach ( $data_fields as $row ) {
			// if we are here, sync it.
			$this->set_field_data( $user_id, $row->field_id, $member_types );
		}

		$this->member_type_updating = false;
	}

	/**
	 * Set xprofile field data if not already in the process of updating it.
	 *
	 * @param int   $user_id user id.
	 * @param int   $field_id field id.
	 * @param mixed $value member type.
	 */
	private function set_field_data( $user_id, $field_id, $value ) {
		// only affect field if the field is related to this member type.
		$restriction = bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_restriction', true );
		if ( 'restricted' === $restriction ) {
			$selected_types = bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_selected_types', true );

			if ( empty( $selected_types ) || ! is_array( $selected_types ) ) {
				return;
			}

			$is_multi = is_array( $value );
			// check for single valued and multi valued field.
			if ( ! $is_multi && ! in_array( $value, $selected_types ) ) {
				return; // it is single valued field.
			}
			if ( $is_multi ) {
				// only common values allowed.
				$value = array_intersect( $selected_types, $value );
			}
		}

		if ( empty( $value ) ) {
			xprofile_delete_field_data( $field_id, $user_id );
		} else {
			xprofile_set_field_data( $field_id, $user_id, $value );
		}
	}

	/**
	 * Update member type.
	 *
	 * @param int    $user_id numeric user id.
	 * @param string $member_type member type.
	 * @param bool   $append append of reset the member type.
	 */
	private function set_member_type( $user_id, $member_type, $append = false ) {
		bp_set_member_type( $user_id, $member_type, $append );
	}

	/**
	 * Member type is being removed.
	 *
	 * @param int    $user_id user id.
	 * @param string $member_type member type.
	 */
	public function clear_member_type_field( $user_id, $member_type ) {

		if ( $this->field_updating ) {
			return;
		}

		$this->field_updating = true;

		$fields = bpmtp_get_member_type_field_ids();

		foreach ( $fields as $field_id ) {
			xprofile_delete_field_data( $field_id, $user_id );
		}

		$fields = bpmtp_get_multi_member_type_field_ids();

		foreach ( $fields as $field_id ) {
			$values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );
			if ( $values ) {
				// if there was a value, check if we can retain some.
				$values = array_diff( $values, (array) $member_type );
				if ( $values ) {
					xprofile_set_field_data( $field_id, $user_id, $values );
					continue;
				}
			}
			xprofile_delete_field_data( $field_id, $user_id );
		}

		$this->field_updating = false;
	}


	/**
	 * Should we really consider this xprofile update as something valid?
	 *
	 * @return bool
	 */
	private function is_xprofile_update() {

		if ( is_user_logged_in() ) {
			return true;
		} elseif ( did_action( 'bp_core_activated_user' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

new BPMTP_Xprofile_Mapping_Helper();
