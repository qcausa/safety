<?php
/**
 * Adds compatibility with BP Nouveau nav reordering.
 *
 * Enforces the priority of Group Tabs Pro nav order.
 *
 * @package BPGTC
 * @subpackage BuddyPress\Groups
 */

namespace PressThemes\BPGTC\BuddyPress\Groups;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Nouveau Group Nav compat helper.
 */
class BP_Nouveau_Compat {

	/**
	 * Boots the cass.
	 *
	 * @return BP_Nouveau_Compat
	 */
	public static function boot() {
		static $self = null;
		if ( is_null( $self ) ) {
			$self = new self();
			$self->setup();
		}

		return $self;
	}

	/**
	 * Setup compat hooks.
	 */
	private function setup() {
		add_filter( 'bp_after_nouveau_appearance_settings_parse_args', array( $this, 'reorder_nav' ) );
	}

	/**
	 * Force reordering of nav by our plugin's order.
	 *
	 * @param array $args args.
	 *
	 * @return array
	 */
	public function reorder_nav( $args ) {

		if ( empty( $args['group_nav_order'] ) ) {
			return $args;
		}

		// optimize to avoid multiple db calls.
		static $cached = array();
		if ( $cached ) {
			$args['group_nav_order'] = $cached;

			return $args;
		}

		// Get all modified tabs and their order.
		$tabs = bpgtc_get_active_group_tab_entries();

		if ( empty( $tabs ) ) {
			return $args;
		}

		$ordered_tabs_gtc = array();
		foreach ( $tabs as $tab ) {
			// position not specified or not existing tab, don't do anything.
			if ( ! $tab->is_existing || $tab->random_position ) {
				continue;
			}

			$ordered_tabs_gtc[ $tab->slug ] = absint( $tab->position );
		}

		$group_navs = $args['group_nav_order'];
		$group_navs = array_merge( array_flip( $group_navs ), $ordered_tabs_gtc );
		asort( $group_navs );
		$cached                  = array_flip( $group_navs );
		$args['group_nav_order'] = $cached;

		return $args;
	}
}

