<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Our entry object for the profile tab.
 */
class BPPTC_Profile_Tabs_Tab_Entry {

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
	 * Default sub nav Tab slug
	 * Parent Nav only.
	 *
	 * @var string
	 */
	public $default_subnav_slug = '';

	/**
	 * Item ID CSS
	 *
	 * @var string
	 */
	public $item_css_id = '';

	/**
	 * Show for site admin only? Only site admin can see it on user profiles.
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
	 * Is this tab active/enabled?
	 *
	 * @var bool|int
	 */
	public $is_active = 0;

	/**
	 * Array of subnav entries.
	 *
	 * @var BPPTC_Profile_Tabs_Subnav_Entry[]
	 */
	public $sub_navs = array();

	/**
	 * Tab will be enabled for these users.
	 *
	 * @var array
	 */
	public $enabled_roles = array();

	/**
	 * Tab will be visible to these roles.
	 *
	 * @var array
	 */
	public $visible_roles = array();

	/**
	 * Is it a predefined tab by BuddyPress or plugins?
	 *
	 * @var bool
	 */
	public $is_existing = false;

	/**
	 * Is this tab set as default component?
	 *
	 * @var bool
	 */
	public $is_default_component = false;

	/**
	 * Slug used for adminbar nav item.
	 *
	 * It used used while modifying the existing nav to remove the item from adminbar.
	 *
	 * @var string
	 */
	public $item_adminbar_slug = '';

	/**
	 * Add to adminbar? Applies to new tabs only.
	 *
	 * @var bool
	 */
	public $add_to_adminbar = false;

	/**
	 * Constructs entry from the post meta array and the post id.
	 *
	 * @param array $meta Post meta data array.
	 * @param int   $post_id numeric post id.
	 */
	public function __construct( $meta, $post_id = 0 ) {
		$post          = get_post( $post_id );
		$this->post_id = $post_id;

		$this->label = $post->post_title;
		$this->slug  = $post->post_name;

		$this->default_subnav_slug = isset( $meta['_bpptc_tab_default_subnav_slug'] ) ? $meta['_bpptc_tab_default_subnav_slug'][0] : '';

		$this->link = isset( $meta['_bpptc_tab_link'] ) ? trim( $meta['_bpptc_tab_link'][0] ) : '';

		$this->item_css_id = isset( $meta['_bpptc_tab_item_css_id'] ) ? $meta['_bpptc_tab_item_css_id'][0] : '';

		$site_admin_only = isset( $meta['_bpptc_site_admin_only'] ) ? $meta['_bpptc_site_admin_only'][0] : '';

		if ( isset( $meta['_bpptc_tab_position'] ) && $meta['_bpptc_tab_position'] >= 0 ) {
			$this->position = $meta['_bpptc_tab_position'][0];
		} else {
			$this->position = random_int( 1, 1000 );
			$this->random_position = true;
		}

		$this->site_admin_only = 'on' === $site_admin_only ? true : false;

		// Is this member type active?
		$is_active = isset( $meta['_bpptc_tab_is_active'] ) ? $meta['_bpptc_tab_is_active'][0] : false;

		$this->is_active = 'on' === $is_active ? true : false;

		// Is this the default component?
		$is_default_component = isset( $meta['_bpptc_is_default_component'] ) ? $meta['_bpptc_is_default_component'][0] : false;

		$this->is_default_component = 'on' === $is_default_component ? true : false;

		// Is this an existing, predefined tab.
		$update_label = isset( $meta['_bpptc_tab_update_label'] ) ? $meta['_bpptc_tab_update_label'][0] : false;

		$this->update_label = 'on' === $update_label ? true : false;


		// Is this an existing, predefined tab.
		$is_existing = isset( $meta['_bpptc_tab_is_existing'] ) ? $meta['_bpptc_tab_is_existing'][0] : false;

		$this->is_existing = 'on' === $is_existing ? true : false;

		$this->item_adminbar_slug = isset( $meta['_bpptc_item_adminbar_slug'] ) ? trim( $meta['_bpptc_item_adminbar_slug'][0] ) : '';

		$add_to_adminbar = isset( $meta['_bpptc_tab_add_to_adminbar'] ) ? $meta['_bpptc_tab_add_to_adminbar'][0] : false;
		$this->add_to_adminbar = 'on' === $add_to_adminbar ? true : false;

		$this->enabled_roles = isset( $meta['_bpptc_tab_enabled_roles'] ) ? $meta['_bpptc_tab_enabled_roles'][0] : array();
		$this->enabled_roles = maybe_unserialize( $this->enabled_roles );

		$this->visible_roles = isset( $meta['_bpptc_tab_visible_roles'] ) ? $meta['_bpptc_tab_visible_roles'][0] : array();
		$this->visible_roles = maybe_unserialize( $this->visible_roles );

		// Labels.
		$this->sub_navs = $this->prepare_subnavs( $meta );

		// if sub nav slug is not given, let us make first tab as sub nav.
		if ( $this->sub_navs && ! $this->default_subnav_slug && ! $this->is_existing ) {
			$this->default_subnav_slug = current( $this->sub_navs )->slug;
		}
	}


	/**
	 * Prepare sub nav items.
	 *
	 * @param array $meta meta.
	 *
	 * @return array
	 */
	private function prepare_subnavs( $meta ) {
		$subnav_details = isset( $meta['_bpptc_subnav_items'] ) ? $meta['_bpptc_subnav_items'][0] : '';

		if ( $subnav_details ) {
			$subnav_details = maybe_unserialize( $subnav_details );
		} else {
			$subnav_details = array();
		}

		$subnav_items = array();

		foreach ( $subnav_details as $subnav_item_meta ) {
			$subnav_entry                        = new BPPTC_Profile_Tabs_Subnav_Entry( $subnav_item_meta );

			// we must have a valid slug.
			if ( $subnav_entry->slug ) {
				$subnav_items[ $subnav_entry->slug ] = $subnav_entry;
			}
		}

		return $subnav_items;
	}
}
