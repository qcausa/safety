<?php
/**
 * Core functions file
 *
 * @package BPGTC
 */

use PressThemes\BPGTC\BuddyPress\Groups\Group_Tab_Entry;
use PressThemes\BPGTC\BuddyPress\Groups\Group_Subtab_Entry;
use PressThemes\BPGTC\BuddyPress\Groups\Tab_Entry;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Get Post type for the group tab.
 *
 * @return string
 */
function bpgtc_get_post_type() {
	return 'bpgtc_group_tab';
}

/**
 * Get our Internal Group Tab object corresponding to the given tab/component
 *
 * Please avoid directly using it, you should use the bpgtc_get_active_group_tab_entries() for bulk fetch.
 *
 * @param string $slug Group tab slug.
 *
 * @return Group_Tab_Entry|null
 */
function bpgtc_get_group_tab_entry( $slug ) {

	$post_id = bpgtc_get_post_id( $slug );

	if ( empty( $post_id ) ) {
		return null;
	}

	$meta = get_post_custom( $post_id );

	return new Group_Tab_Entry( $meta, $post_id );
}

/**
 * Get an array of Active Group Tabs entries
 *
 * @return Group_Tab_Entry[]
 */
function bpgtc_get_active_group_tab_entries() {

	// case 1: non multisite.
	if ( ! is_multisite() ) {
		return _bpgtc_get_active_group_tab_entries();
	}

	// case 2: multisite non network active.
	// if we are here, we are on multisite.
	if ( ! bpgtc_group_tabs()->is_network_active() ) {
		return _bpgtc_get_active_group_tab_entries();
	}

	// Case 3 a. Multisite network active, non multis blog mode.
	$root_blog_id = 0;
	if ( ! bp_is_multiblog_mode() ) {
		$root_blog_id = bp_get_root_blog_id();
	} else {
		// case 3.b Multisite, network active, multi blog mode.
		$root_blog_id = get_main_site_id();
	}

	if ( $root_blog_id ) {
		switch_to_blog( $root_blog_id );
	}

	$tabs = _bpgtc_get_active_group_tab_entries();

	if ( $root_blog_id ) {
		restore_current_blog();
	}

	return $tabs;
}

/**
 * Used internally to retrieve tabs.
 *
 * @see bpgtc_get_active_group_tab_entries()
 * @internal
 *
 * @return array
 */
function _bpgtc_get_active_group_tab_entries() {

	static $active_tabs;

	if ( isset( $active_tabs ) ) {
		return $active_tabs;
	}

	$active_ids = bpgtc_get_active_group_tabs_post_ids();

	if ( empty( $active_ids ) ) {
		$active_tabs = array();

		return $active_tabs;
	}

	$active_tabs = array();

	foreach ( $active_ids as $active_id ) {

		$meta = get_post_custom( $active_id );
		$obj  = new Group_Tab_Entry( $meta, $active_id );

		if ( ! $obj->slug || ! $obj->post_slug ) {
			continue;
		}

		$active_tabs[ $obj->post_slug ] = $obj;
	}

	return $active_tabs;
}

/**
 * Get an array of post ids associated with active group tabs.
 *
 * @return array of post ids.
 */
function bpgtc_get_active_group_tabs_post_ids() {

	global $wpdb;

	$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s AND ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value = %s ) ";

	$post_ids = $wpdb->get_col( $wpdb->prepare( $query, 'publish', bpgtc_get_post_type(), '_bpgtc_tab_is_active', 'on' ) );

	_prime_post_caches( $post_ids, false, true );

	$active_post_ids = $post_ids; // bpgtc_filter_active_post_ids( $post_ids );

	return $active_post_ids;
}

/**
 * Get the post ID which stores details for this group tab
 *
 * Please avoid directly using it, you should use the bpgtc_get_active_member_type_entries() for bulk fetch.
 *
 * @param string $slug tab slug.
 *
 * @return int post id.
 */
function bpgtc_get_post_id( $slug ) {

	global $wpdb;
	// The reason to do select from the post table is to make sure that the post exists and not just the meta.
	$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s ";

	$post_id = $wpdb->get_var( $wpdb->prepare( $query, bpgtc_get_post_type(), $slug ) );

	return $post_id;
}

/**
 * Get the main blog id.
 *
 * We store the group tabs details on this blog.
 *
 * @return int|string
 */
function bpgtc_get_main_blog_id() {
	$blog_id = 1;
	if ( function_exists( 'get_network' ) ) {
		$blog_id = get_network()->blog_id;
	} elseif ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
		$blog_id = BLOG_ID_CURRENT_SITE;
	}

	return apply_filters( 'bpgtc_main_blog_id', $blog_id );
}

/**
 * Is tab enabled for group type
 *
 * @param int                                $group_id Group id.
 * @param Group_Tab_Entry|Group_Subtab_Entry $tab Tab object.
 *
 * @return bool
 */
function bpgtc_is_tab_enabled_for( $group_id, $tab ) {

	if ( empty( $tab->enabled_for ) ) {
		return false;
	}

	$enabled_for = (array) $tab->enabled_for;

	if ( in_array( 'none', $enabled_for, true ) ) {
		return false;
	}

	$group = groups_get_group( $group_id );

	if ( empty( $group->id ) ) {
		return false;
	}

	// check for exclusion first.
	if ( ! empty( $tab->excluded_group_ids ) && in_array( $group_id, (array) $tab->excluded_group_ids ) ) {
		return false;
	} elseif ( ! empty( $tab->associated_group_ids ) && ! in_array( $group_id, (array) $tab->associated_group_ids ) ) {
		return false;
	} elseif ( ! empty( $tab->associated_group_types ) && ! array_intersect( (array) $tab->associated_group_types, (array) bp_groups_get_group_type( $group_id, false ) ) ) {
		return false;
	} elseif ( in_array( 'all', $enabled_for, true ) ) {
		return true;
	} elseif ( in_array( 'public', $enabled_for, true ) && 'public' === $group->status ) {
		return true; // Do not modify the visibility for existing tabs.
	} elseif ( in_array( 'private', $enabled_for, true ) && 'private' === $group->status ) {
		return true;
	} elseif ( in_array( 'hidden', $enabled_for, true ) && 'hidden' === $group->status ) {
		return true;
	}

	return false;
}

/**
 * Is the tab for the group is visible to the given user.
 *
 * @param int                                $group_id Group id.
 * @param Group_Tab_Entry|Group_Subtab_Entry $tab Tab object.
 *
 * @return bool
 */
function bpgtc_is_tab_visible_for( $group_id, $tab ) {

	if ( empty( $tab->visible_for ) ) {
		return false;
	}

	$visible_for = (array) $tab->visible_for;

	// the site admin only tabs.
	if ( $tab->site_admin_only && ! is_super_admin() ) {
		return false;
	}

	if ( is_super_admin() ) {
		return true;
	}

	if ( in_array( 'all', $visible_for, true ) ) {
		return true;
	} elseif ( in_array( 'do_not_modify', $visible_for, true ) ) {
		return true; // Do not modify the visibility for existing tabs.
	} elseif ( in_array( 'logged_in', $visible_for, true ) && is_user_logged_in() ) {
		return true;
	} elseif ( in_array( 'members_only', $visible_for, true ) && groups_is_user_member( get_current_user_id(), $group_id ) ) {
		return true;
	} elseif ( in_array( 'admin_only', $visible_for, true ) && groups_is_user_admin( get_current_user_id(), $group_id ) ) {
		return true;
	} elseif ( in_array( 'mods_only', $visible_for, true ) && groups_is_user_mod( get_current_user_id(), $group_id ) ) {
		return true;
	}

	return false;
}

/**
 * Parse group tab url
 *
 * @param string $url Group url.
 *
 * @return string
 */
function bpgtc_parse_group_tab_url( $url ) {
	$search = array(
		'[displayed-group-url]',
		'[displayed-group-id]',
		'[logged-user-url]',
		'[logged-user-id]',
		'[site-url]',
		'[blog-url]',
	);

	$group    = groups_get_current_group();
	$group_id = $group ? $group->id : '';
	$replace  = array(
		bpgtc_get_group_url( $group ),
		strval( $group_id ),
		bp_loggedin_user_domain(),
		strval( get_current_user_id() ),
		network_home_url( '/' ),
		site_url( '/' ),
	);

	return str_replace( $search, $replace, $url );
}

/**
 * Register a new Group Extension.
 *
 * Not Used.
 *
 * @param string $group_extension_object Group extension object.
 */
function bpgtc_register_group_extension( $group_extension_object ) {
	// Register the group extension on the bp_init action so we have access
	// to all plugins.
	$extension = $group_extension_object;
	add_action( 'bp_actions', array( &$extension, '_register' ), 8 );
	add_action( 'admin_init', array( &$extension, '_register' ) );
}

/**
 * Screen Handler for the tabs/subtabs added by this plugin.
 */
function bpgtc_screen_handler() {

	// before we handle it, make sure the slug is valid.
	$tab         = bpgtc_get_current_tab();
	$subtab_slug = bp_action_variable( 0 );

	if ( ! $tab ) {
		return;
	}

	if ( $subtab_slug && isset( $tab->sub_navs[ $subtab_slug ] ) ) {
		$subtab = $tab->sub_navs[ $subtab_slug ];
	} elseif ( empty( $subtab_slug ) ) {
		$subtab = current( $tab->sub_navs );
	} else {
		$subtab = null;
	}

	// upto BuddyPress 2.9.2, BuddyPress does not support a tab without a default sub tab,
	// See ticket https://buddypress.trac.wordpress.org/ticket/7628
	// for the details.
	if ( ! $subtab ) {
		return;
	}

	$current_group = groups_get_current_group();

	$available = bpgtc_is_tab_enabled_for( $current_group->id, $subtab ) && bpgtc_is_tab_visible_for( $current_group->id, $subtab );

	if ( ! $available ) {
		return;
	}


	// Hook content generator.
	add_action( 'bp_template_content', 'bpgtc_content_generator' );
	// load plugins template.
	bp_core_load_template( array( 'groups/single/plugins' ) );
}

/**
 * Tab/Sub tab content generator.
 */
function bpgtc_content_generator() {

	$tab    = bpgtc_get_current_tab();
	$subtab = bpgtc_get_current_sub_tab();

	if ( ! $tab || ! $subtab ) {
		return;
	}

	$content       = $subtab->content;
	$current_group = groups_get_current_group();

	// allow admins to use displayed_user_id and logged_user_id in their shortcodes.
	$content = str_replace(
		array(
			'#displayed_group_id#',
			'#logged_user_id#',
		),
		array(
			$current_group->id,
			bp_loggedin_user_id(),
		),
		$content
	);

	setup_postdata( get_post( $tab->post_id ) );
	// applying the_content gives us all the benefits of the default the_content hooks.
	bpgtc_subnav_tab();

	printf( '<div class="bpgtc-tab-content bpgtc-tab-%1$s-content bpgtc-tab-%1$s-%2$s-content ">', sanitize_key( $tab->slug ), sanitize_key( $subtab->slug ) );
	echo apply_filters( 'the_content', $content );
	echo '</div>';

	wp_reset_postdata();
}

/**
 * Show sub nav.
 */
function bpgtc_subnav_tab() {

	$tab = bpgtc_get_current_tab();
	if ( ! $tab ) {
		return;
	}

	$current_group = groups_get_current_group();

	$link = '';
	$current_subnav_slug = bp_action_variable( 0 );
	$group_link = bpgtc_get_group_url( $current_group );

	if ( $tab->link ) {
		$link = trailingslashit( bpgtc_parse_group_tab_url( $tab->link ) );
	} else {
		$link = trailingslashit( $group_link . $tab->slug );
	}

	$position      = 0;
	$sub_nav_items = $tab->sub_navs;

	$items = array();

	foreach ( $sub_nav_items as $sub_nav_item ) {
		$position += 10;
		$allowed  = bpgtc_is_tab_enabled_for( $current_group->id, $sub_nav_item ) && bpgtc_is_tab_visible_for( $current_group->id, $sub_nav_item );

		if ( ! $allowed ) {
			continue;
		}

		if ( $sub_nav_item->position ) {
			$position = $sub_nav_item->position;
		}

		$sub_nav_item->position = $position;// make sure the position is set.
		$sub_nav_item->url      = ! empty( $sub_nav_item->url ) ? trailingslashit( bpgtc_parse_group_tab_url( $sub_nav_item->url ) ) : trailingslashit( $link . $sub_nav_item->slug );

		$items[] = $sub_nav_item;
	}

	if ( count( $items ) < 2 && apply_filters( 'bpgtc_hide_sub_tabs_if_one', true ) ) {
		return;// if there is only one, do not show the sub tab.
	}

	uasort( $items, 'bpgtc_nav_item_compare' );

	$html = '';

	// force default sub tab to be selected.
	if ( empty( $current_subnav_slug ) && ! empty( $items[0] ) ) {
		$current_subnav_slug = $items[0]->slug;
	}

	foreach ( $items as $item ) {
		$class = $current_subnav_slug == $item->slug ? 'current selected' : '';
		$id_attr = $item->item_css_id ? $item->item_css_id : uniqid( 'gstab-' );

		$html .= sprintf( '<li class="%1$s" id="%4$s-li"><a href="%2$s" id="%4$s">%3$s</a></li>', esc_attr( $class ), esc_url( $item->url ), esc_html( $item->label ), esc_attr( $id_attr ) );
	}

	$html = bpgtc_subnav_wrap_nav_list( $html );

	echo $html;
}

/**
 * Generate html for sub nav wrapper.
 *
 * @param string $html List of sub nav items li.
 *
 * @return string
 */
function bpgtc_subnav_wrap_nav_list( $html ) {

	if ( function_exists( 'bp_nouveau' ) ) {
		return '<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation"><ul class="subnav">' . $html . '</ul></nav>';
	}

	return '<div class="item-list-tabs no-ajax" id="subnav" role="navigation"><ul>' . $html . '</ul></div>';
}

/**
 * Reorder Nav Items.
 *
 * @param Group_Subtab_Entry $item1 sub tab entry.
 * @param Group_Subtab_Entry $item2 sub tab entry.
 *
 * @return mixed
 */
function bpgtc_nav_item_compare( $item1, $item2 ) {
	return $item1->position - $item2->position;
}

/**
 * Get current tab object.
 *
 * @return null|Group_Tab_Entry
 */
function bpgtc_get_current_tab() {
	$tab_slug = bp_current_action();
	if ( ! $tab_slug ) {
		return null;
	}

	$active_tabs = bpgtc_get_active_group_tab_entries();

	$group       = groups_get_current_group();
	$current_tab = null;

	foreach ( $active_tabs as $active_tab ) {
		if ( $tab_slug === $active_tab->slug && $group && bpgtc_is_tab_available( $active_tab, $group ) ) {
			$current_tab = $active_tab;
			break;
		}
	}

	return $current_tab; // isset( $active_tabs[ $tab_slug ] ) ? $active_tabs[ $tab_slug ] : null;
}

/**
 * Get current subtab object.
 *
 * @return null|Group_Subtab_Entry
 */
function bpgtc_get_current_sub_tab() {


	$tab = bpgtc_get_current_tab();

	if ( ! $tab ) {
		return null;
	}

	$sub_tab_slug = bp_action_variable( 0 );

	if ( $sub_tab_slug && isset( $tab->sub_navs[ $sub_tab_slug ] ) ) {
		$sub_tab = $tab->sub_navs[ $sub_tab_slug ];
	} else {
		$sub_tab = current( $tab->sub_navs );
	}

	// upto BuddyPress 2.9.2, BuddyPress does not support a tab without a default sub tab,
	// See ticket https://buddypress.trac.wordpress.org/ticket/7628
	// for the details.
	if ( ! $sub_tab ) {
		return null;
	}

	return $sub_tab;
}

/**
 * Is tab available in the current context.
 *
 * @param Group_Tab_Entry|Group_Subtab_Entry $tab tab object.
 * @param BP_Groups_Group $group group object.
 *
 * @return bool
 */
function bpgtc_is_tab_available( $tab, $group ) {
	return  bpgtc_is_tab_enabled_for( $group->id, $tab ) && bpgtc_is_tab_visible_for( $group->id, $tab );
}

/**
 * Get the total count from label.
 *
 * @param string $text string to extract from.
 *
 * @return bool|int false on failure otherwise count.
 */
function bpgtc_parse_total_count( $text ) {

	$matches = array();
	$regex   = '#<\s*?span\b[^>]*>(.*?)</span\b[^>]*>#s';

	if ( ! preg_match( $regex, $text, $matches ) ) {
		return false;
	}

	return $matches[1];
}

/**
 * Filter ids.
 *
 * @param array $post_ids post id.
 *
 * @return array
 */
function bpgtc_filter_active_post_ids( $post_ids ) {

	if ( empty( $post_ids ) ) {
		return $post_ids;
	}

	if ( ! function_exists( 'wpml_get_current_language' ) ) {
		return $post_ids;
	}

	static $current_lang_post_ids;
	if ( is_null( $current_lang_post_ids ) ) {
		global $wpdb;
		$current_lang_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code = %s AND element_type = 'post_bpgtc_group_tab'", wpml_get_current_language() ) );
	}

	return array_intersect( (array) $post_ids, (array) $current_lang_post_ids );
}

/**
 * Get group types
 *
 * @param array $args Array key => value pair.
 *
 * @return array
 */
function bpgtc_get_group_types( $args = array() ) {
	$group_types = array();

	$existing_types = bp_groups_get_group_types( $args, 'objects' );

	foreach ( $existing_types as $key => $obj ) {
		$group_types[ $key ] = $obj->labels['singular_name'];
	}

	return $group_types;
}

/**
 * Retrieves group url
 *
 * @param BP_Groups_Group|int $group Group object.
 *
 * @return string
 */
function bpgtc_get_group_url( $group ) {

	if ( function_exists( 'bp_get_group_url' ) ) {
		return bp_get_group_url( $group );
	}

	return bp_get_group_permalink( $group );
}
