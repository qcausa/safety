<?php
/**
 * BuddyPress Member Types Pro
 *
 * @package buddypress-member-types-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Get an option value.
 *
 * @param string $setting_name option name.
 * @param mixed  $default default value.
 *
 * @return mixed setting value.
 */
function bpmtp_get_option( $setting_name, $default = null ) {

	$settings = get_option( 'bpmtp-settings', array() );

	if ( ! isset( $settings[ $setting_name ] ) ) {
		$value = $default;
	} else {
		$value = $settings[ $setting_name ];
	}

	return $value;
}

/**
 * Get network options.
 *
 * @param string $option_name option name.
 * @param mixed  $default default value.
 *
 * @return mixed setting value.
 */
function bpmtp_get_network_option( $option_name, $default = null ) {

	$settings = get_site_option( 'bpmtp-network-settings', array( 'allow_site_role' => 'none', 'add_user_to_site_on_registration' => 1 ) );

	if ( ! isset( $settings[ $option_name ] ) ) {
		$value = $default;
	} else {
		$value = $settings[ $option_name ];
	}

	return $value;
}

/**
 * Whether to use local role association on global for the multisite.
 *
 * If enabled, It allows associating member types to roles
 * on each blog of the multisite(settings->Member Types).
 * And when the member type changes, the role for the user is updated on each of the sub site.
 *
 * It may prove dangerous and we recommend keeping it off.
 */
function bpmtp_use_multisite_local_role_association() {
	return bpmtp_member_types_pro()->is_network_active() && bpmtp_get_network_option( 'allow_site_role' ) === 'site';
}

/**
 * Get Member type post type
 *
 * @return string
 */
function bpmtp_get_post_type() {
	return 'bp-member-type';
}

/**
 * Get our Internal Member Type object corresponding to the given member type
 *
 * @param string $member_type member type name.
 *
 * @return BPMTP_Member_Types_Pro_Entry|null
 */
function bpmtp_get_member_type_entry( $member_type ) {
	// case 1: non multisite.
	if ( ! is_multisite() ) {
		return _bpmtp_get_member_type_entry( $member_type );
	}

	// case 2: multisite non network active.
	// if we are here, we are on multisite.
	if ( ! bpmtp_member_types_pro()->is_network_active() ) {
		return _bpmtp_get_member_type_entry( $member_type );
	}

	// Case 3 a. Multisite network active, non multis blog mode.
	$root_blog_id = 0;
	if ( ! bp_is_multiblog_mode() ) {
		$root_blog_id = bp_get_root_blog_id();
	} else {
		// case 3.b Multisite, network active, multi blog mode.
		$root_blog_id = get_main_site_id();
	}

	$root_blog_id = bpmtp_get_main_blog_id( $root_blog_id );

	if ( $root_blog_id ) {
		switch_to_blog( $root_blog_id );
	}

	$entry = _bpmtp_get_member_type_entry( $member_type );

	if ( $root_blog_id ) {
		restore_current_blog();
	}

	return $entry;
}

/**
 * Get our Internal Member Type object corresponding to the given member type
 *
 * @param string $member_type member type name.
 *
 * @internal
 *
 * @return BPMTP_Member_Types_Pro_Entry|null
 */
function _bpmtp_get_member_type_entry( $member_type ) {

	$post_id = _bpmtp_get_post_id( $member_type );

	if ( empty( $post_id ) ) {
		return null;
	}

	$meta = get_post_custom( $post_id );

	return new BPMTP_Member_Types_Pro_Entry( $meta, $post_id );
}

/**
 * Get an array of Active Member Type entries
 *
 * @return BPMTP_Member_Types_Pro_Entry[]
 */
function bpmtp_get_active_member_type_entries() {

	// case 1: non multisite.
	if ( ! is_multisite() ) {
		return _bpmtp_get_active_member_type_entries();
	}

	// case 2: multisite non network active.
	// if we are here, we are on multisite.
	if ( ! bpmtp_member_types_pro()->is_network_active() ) {
		return _bpmtp_get_active_member_type_entries();
	}

	// Case 3 a. Multisite network active, non multis blog mode.
	$root_blog_id = 0;
	if ( ! bp_is_multiblog_mode() ) {
		$root_blog_id = bp_get_root_blog_id();
	} else {
		// case 3.b Multisite, network active, multi blog mode.
		$root_blog_id = get_main_site_id();
	}

	$root_blog_id = bpmtp_get_main_blog_id( $root_blog_id );

	if ( $root_blog_id ) {
		switch_to_blog( $root_blog_id );
	}

	$entries = _bpmtp_get_active_member_type_entries();

	if ( $root_blog_id ) {
		restore_current_blog();
	}

	return $entries;
}

/**
 * Get an array of Active Member Type entries
 *
 * @internal
 * @see bpmtp_get_active_member_type_entries()
 *
 * @return BPMTP_Member_Types_Pro_Entry[]
 */
function _bpmtp_get_active_member_type_entries() {

	static $active_types;

	if ( isset( $active_types ) ) {
		return $active_types;
	}

	$active_ids = _bpmtp_get_active_member_type_post_ids();

	if ( empty( $active_ids ) ) {
		$active_types = array();

		return $active_types;
	}

	update_meta_cache( 'post', $active_ids );

	$active_types = array();

	foreach ( $active_ids as $active_id ) {

		$meta = get_post_custom( $active_id );
		$obj  = new BPMTP_Member_Types_Pro_Entry( $meta, $active_id );

		if ( ! $obj->member_type ) {
			continue;
		}

		$active_types[ $obj->member_type ] = $obj;
	}

	return $active_types;
}

/**
 * Get an array of post ids associated with active member types
 *
 * @internal
 *
 * @return array of post ids.
 */
function _bpmtp_get_active_member_type_post_ids() {

	global $wpdb;

	$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value = %d ) ";

	$post_ids = $wpdb->get_col( $wpdb->prepare( $query, bpmtp_get_post_type(), '_bp_member_type_is_active', 1 ) );

	update_meta_cache( 'post', $post_ids );

	return $post_ids;
}

/**
 * Get the post ID which stores details for this member type
 *
 * @todo implement
 *
 * Please avoid directly using it, you should use the
 *
 * @param string $member_type member type name.
 *
 * @return int post id.
 */
function _bpmtp_get_post_id( $member_type ) {

	global $wpdb;
	// The reason to do select from the post table is to make sure that the post exists and not just the meta.
	$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value = %s ) ";

	$post_id = $wpdb->get_var( $wpdb->prepare( $query, bpmtp_get_post_type(), '_bp_member_type_name', $member_type ) );

	return $post_id;
}

/**
 * Get an array of member types to exclude from directory
 *
 * @return array
 */
function bpmtp_get_dir_excluded_types() {

	$active_types = bpmtp_get_active_member_type_entries();
	$exclude      = array();

	foreach ( $active_types as $member_type => $obj ) {

		if ( $obj->excluded_members_from_directory ) {
			$exclude[] = $member_type;
		}
	}

	return $exclude;
}

/**
 * Get all field ids whose type is set to member type
 *
 * @return array of numeric ids.
 */
function bpmtp_get_member_type_field_ids() {
	global $wpdb;
	$table = buddypress()->profile->table_name_fields;

	$query      = "SELECT id FROM {$table} WHERE type = %s";
	$fields_ids = $wpdb->get_col( $wpdb->prepare( $query, 'membertype' ) );

	return $fields_ids;
}

/**
 * Get all field ids whose type is multi member type(membertypes)
 *
 * @return array
 */
function bpmtp_get_multi_member_type_field_ids() {
	global $wpdb;
	$table = buddypress()->profile->table_name_fields;

	$query      = "SELECT id FROM {$table} WHERE type = %s";
	$fields_ids = $wpdb->get_col( $wpdb->prepare( $query, 'membertypes' ) );

	return $fields_ids;
}

/**
 * Check if the member type name already exists
 *
 * @global wpdb $wpdb
 *
 * @param int    $post_id numeric post id.
 * @param string $key the name for the member type.
 *
 * @return boolean
 */
function bpmtp_is_duplicate_name( $post_id, $key ) {

	global $wpdb;

	$check_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s AND post_id != %d";

	$ids = $wpdb->get_col( $wpdb->prepare( $check_query, '_bp_member_type_name', $key, $post_id ) );

	if ( ! empty( $ids ) ) {
		return true;
	}

	return false;
}

/**
 * Get an array of associated roles
 *
 * @param string $member_type the unique member type slug.
 *
 * @return array
 */
function bpmtp_get_member_type_associated_roles( $member_type ) {

	$active_types = bpmtp_get_active_member_type_entries();

	if ( isset( $active_types[ $member_type ] ) ) {
		return $active_types[ $member_type ]->roles;
	}

	$entry = bpmtp_get_member_type_entry( $member_type );

	if ( $entry ) {
		return $entry->roles;
	}

	return array();
}

/**
 * Get the roles associated with the member type for the current blog.
 *
 * @param string $member_type member type name.
 *
 * @return array|mixed
 */
function bpmtp_get_member_type_associated_roles_for_multisite( $member_type ) {

	$key = 'member_type_' . $member_type . '_roles';
	$pref = bpmtp_get_network_option( 'allow_site_role', 'none' );

	if ( 'none' === $pref ) {
		return array();
	} elseif ( 'network' === $pref ) {
		return bpmtp_get_network_option( $key, array() );
	} else {
		return bpmtp_get_option( $key, array() );
	}
}
/**
 * Get all the member type fields( Most probably one one) which are marked as non editable
 *
 * @return array
 */
function bpmtp_get_non_editable_field_ids() {
	global $wpdb;

	$meta_table = buddypress()->profile->table_name_meta;
	$field_table = buddypress()->profile->table_name_fields;

	// We use query_ids clause to validate that the type is membertype.
	$query_ids = $wpdb->prepare( "SELECT id FROM {$field_table} WHERE ( type = %s OR type = %s )", 'membertype', 'membertypes' );
	$query_meta = $wpdb->prepare( "SELECT object_id FROM {$meta_table} WHERE object_type = %s AND meta_key= %s and meta_value = %s", 'field', 'bpmtp_field_allow_edit', 0 );

	$query = $query_ids . " AND id IN ({$query_meta})";
	return $wpdb->get_col( $query );
}


/**
 * Get the main blog id.
 *
 * We store the member types details on this blog.
 * Its here to provide backward compatibility.
 *
 * @return int|string
 */
function bpmtp_get_main_blog_id( $blog_id ) {
	return apply_filters( 'bpmtp_main_blog_id', $blog_id );
}

/**
 * Filter profile field data and link to member type directory if the member type supports directory.
 *
 * @param string $member_type member type.
 * @param string $label member type label.
 * @param int    $field_id xprofile field id.
 * @return string
 */
function bpmtp_filter_member_type_field_display_data( $member_type, $label, $field_id = null ) {
	// Preserve old behaviour.
	$do_link = false;

	if ( $field_id ) {
		$do_link = bp_xprofile_get_meta( $field_id, 'field', 'bpmtp_field_link_to_dir', true );
	}

	if ( ! apply_filters( 'bpmtp_link_profile_field_data_to_directory', $do_link ) ) {
		return $label;
	}

	$member_type_object = bp_get_member_type_object( $member_type );

	if ( ! $member_type_object ) {
		return $label;
	}

	if ( isset( $member_type_object->has_directory ) && $member_type_object->has_directory ) {
		$label = sprintf( '<a href="%s">%s</a>', bp_get_member_type_directory_permalink( $member_type ), esc_html( $label ) );
	}

	return $label;
}

/**
 * Retrieves user url.
 *
 * @param int $user_id User id.
 *
 * @return string
 */
function bpmtp_get_user_url( $user_id ) {

	if ( function_exists( 'bp_members_get_user_url' ) ) {
		return bp_members_get_user_url( $user_id );
	}

	return bp_core_get_user_domain( $user_id );
}

/**
 * Retrieves user slug.
 *
 * @param int $user_id User id.
 *
 * @return string
 */
function bpmtp_get_user_slug( $user_id, $user_nicename = false, $user_login = false ) {

	if ( function_exists( 'bp_members_get_user_slug' ) ) {
		return bp_members_get_user_slug( $user_id );
	}

	return bp_core_get_username( $user_id, $user_nicename, $user_login );
}

/**
 * Retrieves group url.
 *
 * @param int|BP_Groups_Group $group Group object or id.
 *
 * @return string
 */
function bpmtp_get_group_url( $group ) {

	if ( function_exists( 'bp_get_group_url' ) ) {
		return bp_get_group_url( $group );
	}

	return function_exists( 'bp_get_group_permalink' ) ? bp_get_group_permalink( $group ) : '';
}

