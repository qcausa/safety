<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class to register the internal tab post type and the actual tabs
 */
class BPPTC_Profile_Tabs_Actions {

	/**
	 * Singleton instance
	 *
	 * @var BPPTC_Profile_Tabs_Actions
	 */

	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {

		// Register internal post type used to handle the tabs.
		add_action( 'bp_init', array( $this, 'maybe_register_post_type' ) );

		add_action( 'bp_setup_globals', array( $this, 'setup_default_component' ), 15 );

		// Register new tabs.
		//add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 110 );
		add_action( 'bp_init', array( $this, 'setup_nav' ), 110 );

		// remove from admin bar if the existing tab is not available for logged in user.
		add_action( 'bp_setup_admin_bar', array( $this, 'add_adminbar_menus' ), 98 );
		add_action( 'bp_setup_admin_bar', array( $this, 'modify_admin_bar' ), 1110 );
	}

	/**
	 * Get singleton instance
	 *
	 * @return BPPTC_Profile_Tabs_Actions
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
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
		} elseif ( ! bpptc_profile_tabs_pro()->is_network_active() ) {
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

		$do_register = apply_filters( 'bpptc_profile_tabs_register_post_type', $do_register );

		if ( $do_register ) {
			$this->register_post_type();
		}
	}

	/**
	 * Setup default component. It won't work if someone already defined the BP_DEFAULT_COMPONENT constant
	 *  in the bp-custom.php or theme's functions.php.
	 */
	public function setup_default_component() {

		if ( defined( 'BP_DEFAULT_COMPONENT' ) ) {
			return;// some other plugin or theme code has already decided it.
		}

		$tabs = bpptc_get_active_profile_tab_entries();

		$default = '';
		$tab     = null;

		foreach ( $tabs as $tab ) {
			if ( $tab->is_default_component ) {
				$default = $tab->slug;
				break;
			}
		}

		// check if the tab is enabled for  the user
		// if not, return.
		if ( $default && $tab && ! bpptc_is_tab_enabled_for( bp_displayed_user_id(), $tab ) ) {
			return;
		}

		// if we are here, we should check if the tab is visible to current user.
		if ( $default && ! bpptc_is_tab_visible_for( bp_loggedin_user_id(), $tab ) ) {
			return;
		}

		if ( $default && defined( 'BP_PLATFORM_VERSION' ) ) {
			add_filter( 'bp_member_default_component', function () use ( $default ) {
				return $default;
			} );
		} elseif ( $default && ! defined( 'BP_DEFAULT_COMPONENT' ) ) {
			define( 'BP_DEFAULT_COMPONENT', $default );
			buddypress()->active_components[ $default ] = 1;
		}
	}

	/**
	 * Register all new BuddyPress nav tabs.
	 */
	public function setup_nav() {

		// get all posts in member type post type.
		$active_tabs = bpptc_get_active_profile_tab_entries();

		foreach ( $active_tabs as $tab => $tab_object ) {

			// if not active or no unique key, do not register.
			if ( ! $tab || ! $tab_object->is_active ) {
				continue;
			}
			// either add tab or modify existing tab.
			$this->register_tab( $tab_object );
		}
	}

	/**
	 * Add adminbar menus for new top level tabs.
	 */
	public function add_adminbar_menus() {
		// Bail if this is an ajax request or the user is not logged in.
		if ( ! is_user_logged_in() || defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false.
		if ( ! bp_use_wp_admin_bar() ) {
			return;
		}

		// get all posts in member type post type.
		$active_tabs = bpptc_get_active_profile_tab_entries();
		foreach ( $active_tabs as $tab => $tab_object ) {

			// if not active or no unique key, do not register.
			if ( ! $tab || ! $tab_object->is_active ) {
				continue;
			}
			// for existing tab.
			if ( ! $tab_object->is_existing ) {
				$this->maybe_add_admin_nav( $tab_object );
			}
		}
	}

	/**
	 * Modify admin bar, remove any non available nav from the current user.
	 */
	public function modify_admin_bar() {
		// Bail if this is an ajax request or the user is not logged in.
		if ( ! is_user_logged_in() || defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false.
		if ( ! bp_use_wp_admin_bar() ) {
			return;
		}

		// get all posts in member type post type.
		$active_tabs = bpptc_get_active_profile_tab_entries();
		foreach ( $active_tabs as $tab => $tab_object ) {

			// if not active or no unique key, do not register.
			if ( ! $tab || ! $tab_object->is_active ) {
				continue;
			}
			// for existing tab.
			if ( $tab_object->is_existing ) {
				$this->maybe_remove_admin_nav( $tab_object );
			}
		}
	}

	/**
	 * Register a new tab(or modify).
	 *
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function register_tab( $tab ) {

		$user_id = bp_is_user() ? bp_displayed_user_id() : bp_loggedin_user_id();

		// must have a user id.
		if ( ! $user_id ) {
			return;
		}

		// overwrite default sub nav.
		if ( $tab->default_subnav_slug ) {
			$tab->default_subnav_slug = $this->get_visible_subnav_slug( $user_id, $tab, $tab->default_subnav_slug );
		}

		if ( $tab->is_existing ) {
			$this->modify_tab( $user_id, $tab );

		} else {
			// add new tab.
			$this->add_tab( $user_id, $tab );
		}
	}

	/**
	 * Add new BuddyPress profile tab.
	 *
	 * @param int                          $user_id user id.
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function add_tab( $user_id, $tab ) {

		// check if the tab is enabled for  the user
		// if not, return.
		if ( ! bpptc_is_tab_enabled_for( $user_id, $tab ) ) {
			return;
		}

		// if we are here, we should check if the tab is visible to current user.
		if ( ! bpptc_is_tab_visible_for( bp_loggedin_user_id(), $tab ) ) {
			return;
		}

		$user_link = bp_is_user() ? bp_displayed_user_domain() : bp_loggedin_user_domain();

		if ( ! $user_link ) {
			return;
		}

		bp_core_new_nav_item(
			array(
				'name'                    => $tab->label,
				'slug'                    => $tab->slug,
				'default_subnav_slug'     => $tab->default_subnav_slug,
				'position'                => $tab->position,
				'show_for_displayed_user' => 1,
				'site_admin_only'         => $tab->site_admin_only,
				'item_css_id'             => $tab->item_css_id,
				'screen_function'         => 'bpptc_screen_handler',
			)
		);

		buddypress()->active_components[ $tab->slug ] = 1;

		// If a dynamic tab link is specified, we need to edit nav as BuddyPress does not allow
		// specifying the url directly.
		if ( $tab->link ) {
			$user_link = trailingslashit( bpptc_parse_profile_tab_url( $tab->link ) );
			buddypress()->members->nav->edit_nav( array( 'link' => $user_link ), $tab->slug );
		}

		$subnav_items = $tab->sub_navs;
		$position     = 0;
		$logged_id    = bp_loggedin_user_id();

		// add sub navs.
		foreach ( $subnav_items as $subnav_item ) {
			// make sure the sub tab is enabled.
			if ( ! bpptc_is_tab_enabled_for( $logged_id, $subnav_item ) ) {
				continue;
			}

			$position += 10;
			$this->add_subnav( $user_link, $tab, $subnav_item, $position, bpptc_is_tab_visible_for( $logged_id, $subnav_item ) );
		}
	}

	/**
	 * Modify existing tab for the user?
	 *
	 * @param int                          $user_id user id.
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function modify_tab( $user_id, $tab ) {

		if ( ! bpptc_is_tab_enabled_for( $user_id, $tab ) ) {
			// On user profile, if the tab does not apply to displayed user, remove it.
			return $this->remove_tab( $tab );
		}

		// if we are here, we should check if the tab is visible to current user.
		if ( ! bpptc_is_tab_visible_for( bp_loggedin_user_id(), $tab ) ) {
			return $this->remove_tab( $tab );
		}

		// if we are here, the tab will be visible.
		$args = array();
		if ( $tab->label && $tab->update_label ) {
			$args['name'] = $this->get_modified_tab_label( $tab->label, $tab->slug );
		}

		if ( $tab->position && ! $tab->random_position ) {
			$args['position'] = $tab->position;
		}

		if ( $tab->item_css_id ) {
			$args['item_css_id'] = $tab->item_css_id;
		}

		if ( $tab->link ) {
			$user_link    = trailingslashit( bpptc_parse_profile_tab_url( $tab->link ) );
			$args['link'] = $user_link;
		} else {
			$user_link = bp_is_user() ? bp_displayed_user_domain() : bp_loggedin_user_domain();
		}
		// Edit this nav item.
		buddypress()->members->nav->edit_nav( $args, $tab->slug );

		if ( ! $user_link ) {
			return;
		}

		if ( $tab->default_subnav_slug ) {
			bp_core_new_nav_default(
				array(
					'parent_slug' => $tab->slug,
					'subnav_slug' => $tab->default_subnav_slug,
				)
			);
		}

		$subnav_items = $tab->sub_navs;
		$position     = 0;
		$logged_id    = bp_loggedin_user_id();

		// add sub navs.
		foreach ( $subnav_items as $subnav_item ) {
			$position += 10;

			if ( $subnav_item->position ) {
				$position = $subnav_item->position;
			}

			$has_access = bpptc_is_tab_enabled_for( $user_id, $subnav_item ) && bpptc_is_tab_visible_for( $logged_id, $subnav_item );
			// are we modifying existing sub nav?
			if ( $subnav_item->is_existing ) {
				// modify it.
				$this->modify_subnav( $user_link, $tab, $subnav_item, $subnav_item->position, $has_access );
				continue;
			}


			$this->add_subnav( $user_link, $tab, $subnav_item, $position, $has_access );
		}
	}

	/**
	 * Add sub nav.
	 *
	 * @param string                          $user_link user link.
	 * @param BPPTC_Profile_Tabs_Tab_Entry    $tab tab object.
	 * @param BPPTC_Profile_Tabs_Subnav_Entry $subnav sub nav object.
	 * @param int                             $position nav position.
	 * @param bool                            $has_access current user has access.
	 */
	public function add_subnav( $user_link, $tab, $subnav, $position, $has_access ) {

		if ( ! $subnav->is_active ) {
			return;
		}
		// if we are here, we should check if the tab is visible to current user.
		bp_core_new_subnav_item(
			array(
				'name'            => $subnav->label,
				'slug'            => $subnav->slug,
				'parent_slug'     => $tab->slug,
				'parent_url'      => trailingslashit( $user_link . $tab->slug ),
				'user_has_access' => $has_access,
				'site_admin_only' => $subnav->site_admin_only,
				'item_css_id'     => $subnav->item_css_id,
				'position'        => $position,
				'screen_function' => 'bpptc_screen_handler',
				'link'            => bpptc_parse_profile_tab_url( $subnav->link ),
			)
		);
	}
	/**
	 * Modify the sub nav.
	 *
	 * @param string                          $user_link user link.
	 * @param BPPTC_Profile_Tabs_Tab_Entry    $tab tab object.
	 * @param BPPTC_Profile_Tabs_Subnav_Entry $subnav sub nav object.
	 * @param int                             $position nav position.
	 * @param bool                            $has_access is nav accessible.
	 */
	public function modify_subnav( $user_link, $tab, $subnav, $position, $has_access ) {
		if ( ! $subnav->is_active ) {
			return;
		}

		// back compat for v 1.0.4 or below.
		if ( ! $has_access && empty( $subnav->visible_roles ) ) {
			$has_access = true;
		}

		if ( ! $has_access ) {
			bp_core_remove_subnav_item( $tab->slug, $subnav->slug );

			return;
		}

		$args = array();

		if ( $subnav->label ) {
			$args['name'] = $this->get_modified_sub_tab_label( $subnav->label, $tab->slug, $subnav->slug );
		}

		if ( $position ) {
			$args['position'] = $position;
		}

		if ( $subnav->item_css_id ) {
			$args['item_css_id'] = $subnav->item_css_id;
		}

		if ( $subnav->link ) {
			$args['link'] = bpptc_parse_profile_tab_url( $subnav->link );
		}

		buddypress()->members->nav->edit_nav( $args, $subnav->slug, $tab->slug );

	}

	/**
	 * Register internal post type
	 */
	private function register_post_type() {

		$is_admin = is_super_admin();

		register_post_type(
			bpptc_get_post_type(),
			array(
				'label'  => __( 'BuddyPress Profile Tabs', 'buddypress-user-profile-tabs-creator-pro' ),
				'labels' => array(
					'name'               => __( 'Profile Tabs', 'buddypress-user-profile-tabs-creator-pro' ),
					'singular_name'      => __( 'Profile Tab', 'buddypress-user-profile-tabs-creator-pro' ),
					'menu_name'          => __( 'Profile Tabs', 'buddypress-user-profile-tabs-creator-pro' ),
					'add_new_item'       => __( 'New Profile Tab', 'buddypress-user-profile-tabs-creator-pro' ),
					'new_item'           => __( 'New Profile Tab', 'buddypress-user-profile-tabs-creator-pro' ),
					'edit_item'          => __( 'Edit Profile Tab', 'buddypress-user-profile-tabs-creator-pro' ),
					'search_items'       => __( 'Search profile Tabs', 'buddypress-user-profile-tabs-creator-pro' ),
					'not_found_in_trash' => __( 'No profile tab found in trash', 'buddypress-user-profile-tabs-creator-pro' ),
					'not_found'          => __( 'No profile found', 'buddypress-user-profile-tabs-creator-pro' ),
				),

				'public'       => false, // this is a private post type, not accessible from front end.
				'show_ui'      => $is_admin,
				'show_in_menu' => 'users.php',
				'menu_icon'    => 'dashicons-groups',
				'supports'     => array( 'title' ),
			)
		);
	}

	/**
	 * Remove BuddyPress profile Tab.
	 *
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function remove_tab( $tab ) {
		// Remove BP Tab.
		bp_core_remove_nav_item( $tab->slug );
	}

	/**
	 * Remove an account menu item from adminbar if needed.
	 *
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function maybe_remove_admin_nav( $tab ) {
		// How do we remove wp adminbar item?
		// There is no consistency in naming.
		global $wp_admin_bar;
		if ( ! $wp_admin_bar ) {
			return;
		}

		$logged_id = get_current_user_id();

		$slug      = defined( 'BP_XPROFILE_SLUG' ) && ( BP_XPROFILE_SLUG === $tab->slug ) ? 'xprofile' : $tab->slug;
		$admin_nav = $tab->item_adminbar_slug ? $tab->item_adminbar_slug : 'nav-account-' . $slug;

		// check if the tab is enabled for  the user
		// if no, we remove it..
		if ( $admin_nav && ! bpptc_is_tab_enabled_for( $logged_id, $tab ) ) {
			$wp_admin_bar->remove_node( $admin_nav );
			return;
			// since the parent node is removed, we should most probably return from here?
		}

		if ( ! $tab->item_adminbar_slug ) {
			return;
		}

		$this->update_admin_bar_node( $admin_nav, $tab );

		$default_url = trailingslashit( bp_loggedin_user_domain() . $tab->slug );

		$subnav_items = $tab->sub_navs;
		// remove sub navs if needed.
		foreach ( $subnav_items as $sub_nav ) {
			if ( ! $sub_nav->is_existing && $sub_nav->add_to_adminbar ) {
				$this->add_adminbar_sub_menu( $admin_nav, $default_url, $sub_nav );
				continue;
			}

			// if we are here, our goal is to remove.
			$node_id = $sub_nav->item_adminbar_slug;
			if ( ! $node_id ) {
				continue;
			}

			// we check for enabled_roles as the plugin did not support it before 1.0.5
			// so, It provides back compat.
			if ( $sub_nav->enabled_roles && ! bpptc_is_tab_enabled_for( $logged_id, $sub_nav ) ) {
				$wp_admin_bar->remove_node( $node_id );
				continue;
			}
			$this->update_admin_bar_node( $node_id, $sub_nav );
		}
	}

	/**
	 * Update adminbar node details(label/link).
	 *
	 * @param string                       $node_id node id.
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	private function update_admin_bar_node( $node_id, $tab ) {
		global $wp_admin_bar;
		// update label/link?
		$update_args = array();

		if ( $tab->link ) {
			$update_args['href'] = bpptc_parse_profile_tab_url( $tab->link );
		}

		$update_label = $tab instanceof BPPTC_Profile_Tabs_Tab_Entry ? $tab->update_label : $tab->label;

		if ( $update_label && $tab->label ) {
			$update_args['title'] = $tab->label;
		}

		if ( ! empty( $update_args ) ) {
			$update_args['id'] = $node_id;
			$wp_admin_bar->add_node( $update_args );
		}
	}

	/**
	 * Remove an account menu item from adminbar if needed.
	 *
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 */
	public function maybe_add_admin_nav( $tab ) {
		// How do we remove wp adminbar item?
		// There is no consistency in naming.
		global $wp_admin_bar;
		if ( ! $wp_admin_bar ) {
			return;
		}

		if ( ! $tab->add_to_adminbar ) {
			return;
		}

		$logged_id = get_current_user_id();

		if ( ! bpptc_is_tab_enabled_for( $logged_id, $tab ) ) {
			return;
		}

		$nav_id          = $tab->item_adminbar_slug ? $tab->item_adminbar_slug : sanitize_title_with_dashes( $tab->label . $tab->post_id . random_int( 500, 3000 ) );
		$tab_default_url = trailingslashit( bp_loggedin_user_domain() . $tab->slug );
		$url             = $tab->link ? bpptc_parse_profile_tab_url( $tab->link ) : $tab_default_url;

		// add to adminbar.
		if ( $nav_id ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => $nav_id,
					'title'  => $tab->label,
					'href'   => $url,
					'parent' => buddypress()->my_account_menu_id,
				)
			);
			// since the parent node is removed, we should most probably return from here?
		}

		$subnav_items = $tab->sub_navs;
		// remove sub navs if needed.
		foreach ( $subnav_items as $sub_nav ) {
			if ( ! $sub_nav->add_to_adminbar ) {
				continue;
			}

			// we check for enabled_roles as the plugin did not support it before 1.0.5
			// so, It provides back compat.
			if ( $sub_nav->enabled_roles && ! bpptc_is_tab_enabled_for( $logged_id, $sub_nav ) ) {
				continue;
			}
			// if we are here, we should most probably add the sub nav.
			$this->add_adminbar_sub_menu( $nav_id, $tab_default_url, $sub_nav );
		}
	}

	/**
	 * Add adminbar sub menu.
	 *
	 * @param string                          $nav_id nav id.
	 * @param string                          $parent_url parent url.
	 * @param BPPTC_Profile_Tabs_Subnav_Entry $sub_nav subnav.
	 */
	private function add_adminbar_sub_menu( $nav_id, $parent_url, $sub_nav ) {
		global $wp_admin_bar;
		// add the sub menu.
		$sub_slug = $sub_nav->item_adminbar_slug ? $sub_nav->item_adminbar_slug : $nav_id . '-' . $sub_nav->slug;

		$url = $sub_nav->link ? bpptc_parse_profile_tab_url( $sub_nav->link ) : trailingslashit( $parent_url . $sub_nav->slug );

		$wp_admin_bar->add_node(
			array(
				'id'     => $sub_slug,
				'parent' => $nav_id,
				'title'  => $sub_nav->label,
				'href'   => $url,
			)
		);
	}

	/**
	 * Find most suitable sub nav to be used as default nav.
	 *
	 * @param int                          $user_id user id.
	 * @param BPPTC_Profile_Tabs_Tab_Entry $tab tab object.
	 * @param string                       $default_slug default slug.
	 *
	 * @return string
	 */
	private function get_visible_subnav_slug( $user_id, $tab, $default_slug ) {
		if ( ! $tab || ! $tab->sub_navs ) {
			return $default_slug;
		}

		$logged_id = bp_loggedin_user_id();

		if ( $default_slug && isset( $tab->sub_navs[ $default_slug ] ) && bpptc_is_tab_enabled_for( $user_id, $tab->sub_navs[ $default_slug ] ) && bpptc_is_tab_visible_for( $logged_id, $tab ) ) {
			return $default_slug;
		}

		foreach ( $tab->sub_navs as $sub_nav ) {
			// we check for enabled_roles as the plugin did not support it before 1.0.5
			// so, It provides back compat.
			if ( $sub_nav->enabled_roles && ! bpptc_is_tab_enabled_for( $user_id, $sub_nav ) ) {
				continue;
			}

			if ( ! bpptc_is_tab_visible_for( $logged_id, $sub_nav ) ) {
				continue;
			}

			return $sub_nav->slug;
		}

		// if we are here, it will be an issue.
		return $default_slug;
	}

	/**
	 * Get modified tab label.
	 *
	 * @param string $label new label.
	 * @param string $slug tab slug.
	 *
	 * @return string
	 */
	private function get_modified_tab_label( $label, $slug ) {
		// parse count.
		$nav            = buddypress()->members->nav->get( $slug );
		$existing_count = false;

		if ( $nav ) {
			$existing_count = bpptc_parse_count_from_text( $nav['name'] );
		}

		if ( false !== $existing_count ) {
			$label = $label . ' <span class="count">' . $existing_count . '</span>';
		}

		return $label;

	}

	/**
	 * Get teh modified label.
	 *
	 * @param string $label sub nav label(new).
	 * @param string $tab_slug parent tab slug.
	 * @param string $subnav_slug child tab slug.
	 *
	 * @return string
	 */
	private function get_modified_sub_tab_label( $label, $tab_slug, $subnav_slug ) {

		$nav            = buddypress()->members->nav->get( $tab_slug . '/' . $subnav_slug );
		$existing_count = false;

		if ( $nav ) {
			$existing_count = bpptc_parse_count_from_text( $nav['name'] );
		}

		if ( false !== $existing_count ) {
			$label = $label . ' <span class="count">' . $existing_count . '</span>';
		}

		return $label;

	}
}

BPPTC_Profile_Tabs_Actions::get_instance();
