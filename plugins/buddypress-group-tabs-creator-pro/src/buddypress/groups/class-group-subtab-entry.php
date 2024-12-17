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
 * Sub nav entry object.
 */
class Group_Subtab_Entry {

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
	public $url = '';

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
	public $enabled_for = array();

	/**
	 * Roles for which it is visible.
	 *
	 * @var array
	 */
	public $visible_for = array();

	/**
	 * Sub nav position.
	 *
	 * Only specified when modifying an existing tab.
	 *
	 * @var int
	 */
	public $position = 0;

	/**
	 * Is existing
	 *
	 * @var bool
	 */
	public $is_existing = false;

	/**
	 * For future.
	 *
	 * @var array
	 */
	public $associated_group_ids = array();

	/**
	 * Excluded group ids(not applicable).
	 *
	 * @var array
	 */
	public $excluded_group_ids = array();

	/**
	 * List of associated group types for this tab(Not used, placing for future).
	 *
	 * @var array
	 */
	public $associated_group_types = array();

	/**
	 * Constructs entry from the post meta array.
	 *
	 * @param array $meta Post meta data array.
	 */
	public function __construct( $meta ) {
		// nav label.
		$this->label = isset( $meta['label'] ) ? $meta['label'] : '';
		// nav slug.
		$this->slug = isset( $meta['slug'] ) ? $meta['slug'] : '';
		// in case the admin wants to link to some other page.
		$this->url = isset( $meta['url'] ) ? $meta['url'] : '';

		$this->item_css_id = isset( $meta['item_css_id'] ) ? $meta['item_css_id'] : '';

		// we may want to set it dynamically.
		//$this->site_admin_only = 0;
		$site_admin_only       = isset( $meta['site_admin_only'] ) ? $meta['site_admin_only'] : '';
		$this->site_admin_only = 'on' === $site_admin_only ? true : false;

		// Is this member type active?
		$is_active       = isset( $meta['is_active'] ) ? $meta['is_active'] : '';
		$this->is_active = 'on' === $is_active ? true : false;

		$this->position = isset( $meta['position'] ) ? intval( $meta['position'] ) : 0;

		$this->enabled_for = isset( $meta['enabled_for'] ) ? $meta['enabled_for'] : array();
		$this->enabled_for = maybe_unserialize( $this->enabled_for );

		$this->visible_for = isset( $meta['visible_for'] ) ? $meta['visible_for'] : array();
		$this->visible_for = maybe_unserialize( $this->visible_for );

		$this->content = isset( $meta['content'] ) ? $meta['content'] : '';
	}
}
