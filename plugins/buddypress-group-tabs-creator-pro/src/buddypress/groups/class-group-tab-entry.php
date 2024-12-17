<?php
/**
 * Class for group tab
 *
 * @package BPGTC
 * @subpackage BuddyPress\Groups
 */

namespace PressThemes\BPGTC\BuddyPress\Groups;

// Exit if file accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Our entry object for the group tab.
 */
class Group_Tab_Entry extends Tab_Entry {

	/**
	 * Tab label
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Should we update label for the existing tabs?
	 *
	 * @var int
	 */
	public $update_label = 0;

	/**
	 * Tab slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Direct link to some url.
	 *
	 * @var string
	 */
	public $link = '';

	/**
	 * Show for site admin only? Only site admin can see it on on groups.
	 *
	 * @var bool
	 */
	public $site_admin_only = 0;

	/**
	 * Tab position
	 *
	 * @var int
	 */
	public $position = 0;

	/**
	 * Flag to check if position was set random.
	 *
	 * @var bool
	 */
	public $random_position = false;

	/**
	 * Associated post ID.
	 *
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Slug for the post associated with this entry.
	 *
	 * @var string
	 */
	public $post_slug = '';

	/**
	 * Is this tab active/enabled?
	 *
	 * @var bool|int
	 */
	public $is_active = 0;

	/**
	 * Tab will be added to these group type.
	 *
	 * @var array
	 */
	public $enabled_for = array();

	/**
	 * Tab will be visible to these users.
	 *
	 * @var array
	 */
	public $visible_for = array();

	/**
	 * Is it a predefined tab by BuddyPress or plugins?
	 *
	 * @var bool
	 */
	public $is_existing = false;

	/**
	 * Item id css.
	 *
	 * @var string
	 */
	public $item_css_id = '';

	/**
	 * Default sub nav Tab slug
	 * Parent Nav only.
	 *
	 * @var string
	 */
	public $default_subnav_slug = '';

	/**
	 * Array of subnav entries.
	 *
	 * @var Group_Subtab_Entry[]
	 */
	public $sub_navs = array();

	/**
	 * Is this tab set as default component?
	 *
	 * @var bool
	 */
	public $is_default_component = false;

	/**
	 * Associated groups if any.
	 *
	 * @var array
	 */
	public $associated_group_ids = array();

	/**
	 * Excluded group ids.
	 *
	 * @var array
	 */
	public $excluded_group_ids = array();

	/**
	 * List of associated group types for this tab.
	 *
	 * @var array
	 */
	public $associated_group_types = array();

	/**
	 * Constructs entry from the post meta array and the post id.
	 *
	 * @param array $meta Post meta data array.
	 * @param int   $post_id numeric post id.
	 */
	public function __construct( $meta, $post_id = 0 ) {
		$post = get_post( $post_id );

		$this->post_id   = $post_id;
		$this->post_slug = $post->post_name;

		$slug       = isset( $meta['_bpgtc_tab_slug'] ) ? trim( $meta['_bpgtc_tab_slug'][0] ) : '';
		$this->slug = empty( $slug ) ? $post->post_name : $slug;

		$label       = isset( $meta['_bpgtc_tab_label'] ) ? trim( $meta['_bpgtc_tab_label'][0] ) : '';
		$this->label = empty( $label ) ? $post->post_title : $label;

		$this->link        = isset( $meta['_bpgtc_tab_link'] ) ? trim( $meta['_bpgtc_tab_link'][0] ) : '';
		$this->item_css_id = isset( $meta['_bpgtc_tab_item_css_id'] ) ? $meta['_bpgtc_tab_item_css_id'][0] : '';


		if ( isset( $meta['_bpgtc_tab_position'] ) && $meta['_bpgtc_tab_position'] >= 0 ) {
			$this->position = $meta['_bpgtc_tab_position'][0];
		} else {
			$this->position        = random_int( 1, 1000 );
			$this->random_position = true;
		}

		$site_admin_only       = isset( $meta['_bpgtc_site_admin_only'] ) ? $meta['_bpgtc_site_admin_only'][0] : '';
		$this->site_admin_only = 'on' === $site_admin_only ? true : false;

		// Is this member type active?
		$is_active = isset( $meta['_bpgtc_tab_is_active'] ) ? $meta['_bpgtc_tab_is_active'][0] : false;
		$this->is_active = 'on' === $is_active ? true : false;

		// Is this an existing, predefined tab.
		$update_label       = isset( $meta['_bpgtc_tab_update_label'] ) ? $meta['_bpgtc_tab_update_label'][0] : false;
		$this->update_label = 'on' === $update_label ? true : false;

		// Is this an existing, predefined tab.
		$is_existing       = isset( $meta['_bpgtc_tab_is_existing'] ) ? $meta['_bpgtc_tab_is_existing'][0] : false;
		$this->is_existing = 'on' === $is_existing ? true : false;

		$this->visible_for = isset( $meta['_bpgtc_tab_visible_for'] ) ? $meta['_bpgtc_tab_visible_for'][0] : array();
		$this->visible_for = maybe_unserialize( $this->visible_for );

		$this->enabled_for = isset( $meta['_bpgtc_tab_enabled_for'] ) ? $meta['_bpgtc_tab_enabled_for'][0] : array();
		$this->enabled_for = maybe_unserialize( $this->enabled_for );

		$this->associated_group_ids = isset( $meta['_bpgtc_tab_groups'] ) ? $meta['_bpgtc_tab_groups'][0] : array();
		$this->associated_group_ids = maybe_unserialize( $this->associated_group_ids );

		$this->excluded_group_ids = isset( $meta['_bpgtc_tab_excluded_groups'] ) ? $meta['_bpgtc_tab_excluded_groups'][0] : array();
		$this->excluded_group_ids = maybe_unserialize( $this->excluded_group_ids );

		$this->associated_group_types = isset( $meta['_bpgtc_selected_group_types'] ) ? $meta['_bpgtc_selected_group_types'][0] : array();

		$this->associated_group_types = maybe_unserialize( $this->associated_group_types );

		$this->default_subnav_slug = isset( $meta['_bpgtc_tab_default_subnav_slug'] ) ? $meta['_bpgtc_tab_default_subnav_slug'][0] : '';

		// Is this the default component?
		$is_default_component = isset( $meta['_bpgtc_is_default_component'] ) ? $meta['_bpgtc_is_default_component'][0] : false;

		$this->is_default_component = 'on' === $is_default_component ? true : false;

		// Labels.
		$this->sub_navs = $this->prepare_sub_navs( $meta, $post_id );

		// if sub nav slug is not given, let us make first tab as sub nav.
		if ( $this->sub_navs && ! $this->default_subnav_slug && ! $this->is_existing ) {
			$this->default_subnav_slug = current( $this->sub_navs )->slug;
		}
	}

	/**
	 * Get associated group ids.
	 *
	 * @param array $meta Post meta.
	 *
	 * @return array
	 */
	private function get_associated_group_ids( $meta ) {
		$associated_group_ids = isset( $meta['_bpgtc_tab_groups'] ) ? $meta['_bpgtc_tab_groups'][0] : array();
		$associated_group_ids = maybe_unserialize( $associated_group_ids );

		$associated_group_types = isset( $meta['_bpgtc_selected_group_types'] ) ? $meta['_bpgtc_selected_group_types'][0] : array();
		$associated_group_types = maybe_unserialize( $associated_group_types );

		$group_ids = array();
		if ( $associated_group_types ) {
			$groups = groups_get_groups(
				array(
					'group_type' => $associated_group_types,
					'fields'     => 'ids',
					'per_page'   => - 1,
				)
			);

			$group_ids = empty( $groups['total'] ) ? array() : $groups['groups'];
		}

		return array_unique( array_map( 'absint', array_merge( $associated_group_ids, $group_ids ) ) );
	}

	/**
	 * Prepare sub nav items.
	 *
	 * @param array $meta meta.
	 * @param int   $post_id post id.
	 *
	 * @return array
	 */
	private function prepare_sub_navs( $meta, $post_id ) {

		$sub_nav_details = isset( $meta['_bpgtc_subnav_items'] ) ? $meta['_bpgtc_subnav_items'][0] : '';

		if ( $sub_nav_details ) {
			$sub_nav_details = maybe_unserialize( $sub_nav_details );
		} else {
			$sub_nav_details = array();
		}

		$sub_nav_items = array();

		foreach ( $sub_nav_details as $sub_nav_item_meta ) {
			$sub_nav_entry = new Group_Subtab_Entry( $sub_nav_item_meta );

			// we must have a valid slug.
			if ( $sub_nav_entry->slug ) {
				$sub_nav_items[ $sub_nav_entry->slug ] = $sub_nav_entry;
			}
		}

		return $sub_nav_items;
	}
}
