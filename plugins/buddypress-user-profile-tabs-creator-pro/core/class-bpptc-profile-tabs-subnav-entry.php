<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Sub nav entry object.
 */
class BPPTC_Profile_Tabs_Subnav_Entry {

	/**
	 * Tab label
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Tab slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Subnav Tab url
	 *
	 * @var string
	 */
	public $link = '';

	/**
	 * Item ID CSS
	 *
	 * @var string
	 */
	public $item_css_id = '';

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
	 * Show for site admin only? Only site admin can see it on user profiles.
	 *
	 * @var bool
	 */
	public $site_admin_only = 0;

	/**
	 * Is this tab active/enabled?
	 *
	 * @var bool|int
	 */
	public $is_active = 0;

	/**
	 * Content to display on this tab.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Tab will be enabled for these users.
	 *
	 * @var array
	 */
	public $enabled_roles = array();

	/**
	 * Roles for which it is visible.
	 *
	 * @var array
	 */
	public $visible_roles = array();

	/**
	 * Is it a predefined sub tab and we are only modifying it?
	 *
	 * @var bool
	 */
	public $is_existing = false;

	/**
	 * Sub nav position.
	 *
	 * Only specified when modifying an existing tab.
	 *
	 * @var int
	 */
	public $position = 0;
	/**
	 * Constructs entry from the post meta array.
	 *
	 * @param array $meta Post meta data array.
	 */
	public function __construct( $meta ) {
		// nav label.
		$this->label          = isset( $meta['label'] ) ? $meta['label'] : '';
		// nav slug.
		$this->slug = isset( $meta['slug'] ) ? $meta['slug'] : '';
		// in case the admin wants to link to some other page.
		$this->link = isset( $meta['url'] ) ? $meta['url'] : '';

		$this->item_css_id = isset( $meta['item_css_id'] ) ? $meta['item_css_id'] : '';
		$this->item_adminbar_slug = isset( $meta['item_adminbar_slug'] ) ? trim( $meta['item_adminbar_slug'] ) : '';
		$add_to_adminbar = isset( $meta['add_to_adminbar'] ) ? $meta['add_to_adminbar'] : false;
		$this->add_to_adminbar = 'on' === $add_to_adminbar ? true : false;

		// we may want to set it dynamically.
		$this->site_admin_only = 0;

		// Is this member type active?
		$is_active = isset( $meta['is_active'] ) ? $meta['is_active'] : '';
		$this->is_active = 'on' === $is_active ? true: false;

		// Is this an existing, predefined tab.
		$is_existing = isset( $meta['is_existing'] ) ? $meta['is_existing'] : false;

		$this->is_existing = 'on' === $is_existing ? true : false;
		$this->position = isset( $meta['position'] ) ? intval( $meta['position'] ) : 0;

		$this->enabled_roles = isset( $meta['enabled_roles'] ) ? $meta['enabled_roles'] : array();
		$this->enabled_roles = maybe_unserialize( $this->enabled_roles );

		$this->visible_roles = isset( $meta['visible_roles'] ) ? $meta['visible_roles'] : array();
		$this->visible_roles = maybe_unserialize( $this->visible_roles );


		$this->content = isset( $meta['content'] ) ? $meta['content'] : '';
	}
}
