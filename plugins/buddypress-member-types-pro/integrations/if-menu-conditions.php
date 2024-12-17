<?php
/**
 * Adds visibility conditions for If Menu plugin.
 *
 * @package    BuddyPress Member Types Pro
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Add If User has member type: 'Member Type Name' visibility conditions.
 *
 * @param array $conditions conditions.
 *
 * @return array
 */
function bpmpt_register_member_type_visibility_conditions_for_if_menu( $conditions ) {
	// Get all registered member types.
	$member_types = bp_get_member_types( array(), 'object' );
	foreach ( $member_types as $member_type => $member_type_object ) {
		$conditions[] = array(
			'id'        => 'has-bp-member-type-' . $member_type,
			// unique ID for the rule
			'name'      => sprintf( __( 'User has member type: %s' ), $member_type_object->labels['singular_name'] ),
			// name of the rule
			'condition' => function ( $item ) use ( $member_type ) {                                   // callback - must return Boolean
				return is_user_logged_in() && bp_has_member_type( get_current_user_id(), $member_type );
			},
			'group'		=>	__('User', 'if-menu')
		);
		$conditions[] = array(
			'id'        => 'does-not-have-bp-member-type-' . $member_type,
			// unique ID for the rule
			'name'      => sprintf( __( 'User does not have member type: %s' ), $member_type_object->labels['singular_name'] ),
			// name of the rule
			'condition' => function ( $item ) use ( $member_type ) {                                   // callback - must return Boolean
				return  ! bp_has_member_type( get_current_user_id(), $member_type );
			},
			'group'		=>	__('User', 'if-menu')
		);
	}

	return $conditions;
}

add_filter( 'if_menu_conditions', 'bpmpt_register_member_type_visibility_conditions_for_if_menu' );
