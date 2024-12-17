<?php
/**
 * Group tab actions
 *
 * @package BPGTC
 * @subpackage BuddyPress\Groups
 */

namespace PressThemes\BPGTC\BuddyPress\Groups;

// If accessed directly code will exit.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class to register the internal tab post type and the actual tabs
 */
class Group_Tabs_Actions {

	/**
	 * Boot
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Setup callbacks
	 */
	private function setup() {

		// Register internal post type used to handle the tabs.
		add_action( 'bp_init', array( $this, 'maybe_register_post_type' ) );

		//add_action( 'bp_init', array( $this, 'setup_default_component' ), 2 );

		// Register new tabs.
		add_action( 'bp_actions', array( $this, 'setup_nav' ), 9 );
		// add_action( 'groups_setup_nav', array( $this, 'setup_nav' ), 110 );
		add_filter( 'bp_groups_default_extension', array( $this, 'switch_default_tab' ), 50 );
	}

	/**
	 * Should we register post types on current blog ?
	 *
	 * We will register post types if:-
	 *  - We are on non multisite
	 *  Or We are on multisite
	 *      - And plugin is not network active
	 *      - Or Plugin is network active and it is our root blog.
	 */
	public function maybe_register_post_type() {

		$do_register = false;

		if ( ! is_multisite() ) {
			// Non multisite, always register.
			$do_register = true;
		} elseif ( ! bpgtc_group_tabs()->is_network_active() ) {
			// Multisite but not network active, do register.
			$do_register = true;
		} elseif ( ! bp_is_multiblog_mode() ) {
			// Multisite, network active but not in multi blog mode.
			// is the current blog root blog?
			$do_register = bp_is_root_blog();
		} elseif ( bp_is_multiblog_mode() ) {
			// Multisite, Network active, Multiblog mode.
			// It is multi blog mode and the most difficult to detect whether to register or not.
			$do_register = is_main_site();
		}

		$do_register = apply_filters( 'bpgtc_group_tabs_register_post_type', $do_register );

		if ( $do_register ) {
			$this->register_post_type();
		}
	}

	/**
	 * Register all new BuddyPress nav tabs.
	 */
	public function setup_nav() {

		// get all posts in member type post type.
		$tabs = bpgtc_get_active_group_tab_entries();

		if ( empty( $tabs ) ) {
			return;
		}

		foreach ( $tabs as $tab_object ) {

			// if not active or no unique key, do not register.
			if ( ! $tab_object->is_active || ! $tab_object->slug ) {
				continue;
			}
			// either add tab or modify existing tab.
			$this->register_tab( $tab_object );
		}
	}

	/**
	 * Register a new tab(or modify).
	 *
	 * @param Group_Tab_Entry $tab tab object.
	 */
	private function register_tab( $tab ) {

		$group = bp_is_group() ? groups_get_current_group() : null;

		// must have a user id.
		if ( ! $group ) {
			return;
		}


		$group_id = $group->id;

		// check for exclusion first.
		if ( ! empty( $tab->excluded_group_ids ) && in_array( $group_id, (array) $tab->excluded_group_ids ) ) {
			return;
		}

		if ( ! empty( $tab->associated_group_ids ) && ! in_array( $group_id, (array) $tab->associated_group_ids ) ) {
			return;
		}

		if ( ! empty( $tab->associated_group_types ) && ! array_intersect( (array) $tab->associated_group_types, (array) bp_groups_get_group_type( $group_id, false ) ) ) {
			return;
		}

		// overwrite default sub nav.
		if ( $tab->default_subnav_slug ) {
			$tab->default_subnav_slug = $this->get_visible_subnav_slug( $group_id, $tab, $tab->default_subnav_slug );
		}

		if ( $tab->is_existing ) {
			$this->modify_tab( $group, $tab );

		} else {
			// add new tab.
			$this->add_tab( $group, $tab );
		}
	}
	/**
	 * Switched default tab of a group based on preference
	 *
	 * @param string $default_tab Default tab.
	 *
	 * @return string
	 */
	public function switch_default_tab( $default_tab ) {

		$group = groups_get_current_group();

		if ( ! $group ) {
			return $default_tab;
		}

		if ( 'public' !== $group->status && ( ! is_user_logged_in() || ! groups_is_user_member( get_current_user_id(), $group->id ) ) ) {
			return $default_tab;
		}


		$active_tabs = bpgtc_get_active_group_tab_entries();

		if ( empty( $active_tabs ) ) {
			return $default_tab;
		}

		foreach ( $active_tabs as $active_tab ) {

			if ( ! bpgtc_is_tab_available( $active_tab, $group ) ) {
				continue;
			}

			if ( $active_tab->is_default_component ) {
				$selected_tab = $active_tab->slug;
				break;
			}
		}

		if ( ! empty( $selected_tab ) ) {
			// for BuddyBoss sub groups, we ensure that it is a parent group to allow rename.
			if ( 'subgroups' !== $selected_tab || function_exists( 'bp_get_descendent_groups' ) && bp_get_descendent_groups( $group->id ) ) {
				$default_tab = $selected_tab;
			}
		}

		return $default_tab;
	}

	/**
	 * Add new BuddyPress group tab.
	 *
	 * @param \BP_Groups_Group $current_group Current group.
	 * @param Group_Tab_Entry  $tab tab object.
	 */
	private function add_tab( $current_group, $tab ) {

		// do not add tab if does not apply to this group.
		if ( ! bpgtc_is_tab_available( $tab, $current_group ) ) {
			return;
		}

		// a set of registered tabs slug, we use it to avoid duplicate slug registration
		// two tab object might have same slug as of now.
		static $registered_tabs = array();

		if ( isset( $registered_tabs[ $tab->slug ] ) ) {
			return;// a tab with this slug, was alreday registered.
		}

		$group_link = bpgtc_get_group_url( $current_group );

		if ( ! $group_link ) {
			return;
		}

		// If a dynamic tab link is specified, we need to edit nav as BuddyPress does not allow
		// specifying the url directly.
		$link = '';
		if ( $tab->link ) {
			$link = trailingslashit( bpgtc_parse_group_tab_url( $tab->link ) );
		}

		bp_core_new_subnav_item(
			array(
				'name'            => $tab->label,
				'slug'            => $tab->slug,
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug,
				'screen_function' => 'bpgtc_screen_handler',
				'position'        => $tab->position,
				'item_css_id'     => $tab->item_css_id,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
				'link'            => $link,

			),
			'groups'
		);

		$registered_tabs[ $tab->slug ] = true;
	}

	/**
	 * Modify existing tab for the group?
	 *
	 * @param \BP_Groups_Group $group Group.
	 * @param Group_Tab_Entry  $tab tab object.
	 *
	 * @return mixed
	 */
	private function modify_tab( $group, $tab ) {

		$group_id = $group->id;

		if ( ! bpgtc_is_tab_enabled_for( $group_id, $tab ) ) {

			// On user profile, if the tab does not apply to displayed user, remove it.
			$this->remove_tab( $group, $tab );
			return;
		}

		// if we are here, we should check if the tab is visible to current user.
		if ( ! bpgtc_is_tab_visible_for( $group_id, $tab ) ) {
			$this->remove_tab( $group, $tab );
			return;
		}

		$group      = groups_get_group( $group_id );
		$group_link = bp_is_group() ? bp_get_group_link( $group ) : '';

		// if we are here, the tab will be visible.
		$args = array();

		if ( $tab->label && $tab->update_label ) {
			$args['name'] = $this->get_modified_label( $tab, $group );
		}

		if ( $tab->position && ! $tab->random_position ) {
			$args['position'] = $tab->position;
		}

		if ( $tab->item_css_id ) {
			$args['item_css_id'] = $tab->item_css_id;
		}

		if ( $tab->link ) {
			$group_link   = trailingslashit( bpgtc_parse_group_tab_url( $tab->link ) );
			$args['link'] = $group_link;
		}

		// Edit this nav item.
		buddypress()->groups->nav->edit_nav( $args, $tab->slug, $group->slug );
	}

	/**
	 * Remove BuddyPress Group Tab.
	 *
	 * @param \BP_Groups_Group $group object.
	 * @param Group_Tab_Entry  $tab tab object.
	 */
	private function remove_tab( $group, $tab ) {
		// Remove BP Tab.
		bp_core_remove_subnav_item( $group->slug, $tab->slug, 'groups' );

		$this->do_404( $group, $tab );
	}

	/**
	 * Do 404 if Tab is not accessible.
	 *
	 * @param \BP_Groups_Group $group object.
	 * @param Group_Tab_Entry  $tab tab object.
	 */
	private function do_404( $group, $tab ) {

		if ( ! function_exists( 'bp_core_get_query_parser' ) || 'rewrites' !== bp_core_get_query_parser() ) {
			return;
		}

		if ( $tab->slug && bp_is_current_action( $tab->slug ) ) {
			bp_do_404();

			return;
		}
	}

	/**
	 * Find most suitable sub nav to be used as default nav.
	 *
	 * @param int             $group_id Group id.
	 * @param Group_Tab_Entry $tab tab object.
	 * @param string          $default_slug default slug.
	 *
	 * @return string
	 */
	private function get_visible_subnav_slug( $group_id, $tab, $default_slug ) {

		if ( ! $tab || ! $tab->sub_navs ) {
			return $default_slug;
		}

		// if the default sub nav has the right access, we should be good.
		if ( $default_slug && isset( $tab->sub_navs[ $default_slug ] ) && bpgtc_is_tab_enabled_for( $group_id, $tab->sub_navs[ $default_slug ] ) && bpgtc_is_tab_visible_for( $group_id, $tab ) ) {
			return $default_slug;
		}

		foreach ( $tab->sub_navs as $sub_nav ) {

			if ( ! $sub_nav->is_active || ( $sub_nav->site_admin_only && ! is_super_admin() ) ) {
				continue;
			}

			// we check for enabled_roles
			// so, It provides back compat.
			if ( ! bpgtc_is_tab_enabled_for( $group_id, $sub_nav ) ) {
				continue;
			}

			if ( ! bpgtc_is_tab_visible_for( $group_id, $sub_nav ) ) {
				continue;
			}

			return $sub_nav->slug;
		}

		// if we are here, it will be an issue.
		return $default_slug;
	}

	/**
	 * Register internal post type
	 */
	private function register_post_type() {

		$is_admin = apply_filters( 'bpgtc_enable_tabs_admin_ui', is_super_admin() );

		register_post_type(
			bpgtc_get_post_type(),
			array(
				'label'  => __( 'Group Tabs', 'buddypress-group-tabs-creator-pro' ),
				'labels' => array(
					'name'               => __( 'Group Tabs', 'buddypress-group-tabs-creator-pro' ),
					'singular_name'      => __( 'Group Tab', 'buddypress-group-tabs-creator-pro' ),
					'menu_name'          => __( 'Group Tabs', 'buddypress-group-tabs-creator-pro' ),
					'add_new'            => __( 'New Group Tab', 'buddypress-group-tabs-creator-pro' ),
					'add_new_item'       => __( 'New Group Tab', 'buddypress-group-tabs-creator-pro' ),
					'new_item'           => __( 'New Group Tab', 'buddypress-group-tabs-creator-pro' ),
					'edit_item'          => __( 'Edit Group Tab', 'buddypress-group-tabs-creator-pro' ),
					'search_items'       => __( 'Search Group Tabs', 'buddypress-group-tabs-creator-pro' ),
					'not_found_in_trash' => __( 'No Group tab found in trash', 'buddypress-group-tabs-creator-pro' ),
					'not_found'          => __( 'No Group found', 'buddypress-group-tabs-creator-pro' ),
				),

				'public'        => false, // This is a private post type, not accessible from front end.
				'show_ui'       => $is_admin,
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-groups',
				'supports'      => array( 'title' ),
				'menu_position' => 75,
			)
		);
	}

	/**
	 * Get modified label.
	 *
	 * @param Group_Tab_Entry  $tab tab entry.
	 * @param \BP_Groups_Group $group group object.
	 *
	 * @return string
	 */
	private function get_modified_label( $tab, $group ) {
		$label = $tab->label;
		// Nouveau does the count parsing.
		if ( class_exists( 'BP_Nouveau' ) ) {
			return $label;
		}

		// parse count.
		$nav            = buddypress()->groups->nav->get( $group->slug . '/' . $tab->slug );
		$existing_count = false;
		if ( $nav ) {
			$existing_count = bpgtc_parse_total_count( $nav['name'] );
		}

		if ( false !== $existing_count ) {
			$label = $label . '<span class="count">' . $existing_count . '</span>';
		}

		return $label;
	}
}
