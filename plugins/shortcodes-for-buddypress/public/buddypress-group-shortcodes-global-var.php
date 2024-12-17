<?php
/**
 * BuddyPress Activity Template.
 *
 * @package BuddyPress
 * @subpackage ActivityTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main activity template loop class.
 *
 * This is responsible for loading a group of activity items and displaying them.
 *
 * @since 1.0.0
 */
class BP_Group_Rest_Api_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $id;

	/**
	 * The displayed user's creator_id.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $creator_id;

	/**
	 * The displayed user's user pass.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $name;

	/**
	 * The displayed user's user nicename.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $slug;

	/**
	 * The displayed user's user email.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $description;

	/**
	 * The displayed user's user url.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $status;

	/**
	 * The displayed user's user registered.
	 *
	 * @since 2.1.0
	 * @var string
	 */
	public $parent_id;

	/**
	 * The displayed user's user_activation_key.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $enable_forum;

	/**
	 * The displayed user's user status.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $date_created;

	/**
	 * The displayed user's display name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $admins;

	/**
	 * The displayed user's id.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $mods;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $total_member_count;

	/**
	 * The displayed user's friendship_status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $is_member;


	/**
	 * The displayed user's last_activity.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $is_user_member;

	/**
	 * The displayed user's total_friend_count.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $is_invited;

	/**
	 * The displayed user's latest_update.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $is_pending;


	/**
	 * Last_activity
	 *
	 * @var mixed
	 */
	public $last_activity;

	/**
	 * User_has_access
	 *
	 * @var mixed
	 */
	public $user_has_access;

	/**
	 * Is_visible
	 *
	 * @var mixed
	 */
	public $is_visible;


	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct( $response_group_data ) {
		$this->id = $response_group_data['id'];
		$this->creator_id = $response_group_data['creator_id'];
		$this->name = $response_group_data['name'];
		$this->slug = $response_group_data['slug'];
		$this->description = $response_group_data['description']['raw'];
		$this->status = $response_group_data['status'];
		$this->parent_id = $response_group_data['parent_id'];
		$this->enable_forum = $response_group_data['enable_forum'];
		$this->date_created = $response_group_data['date_created'];
		$this->admins = '';
		$this->mods = '';
		$this->total_member_count = '';
		$this->is_member = '';
		$this->is_user_member = '';
		$this->is_invited = '';
		$this->is_pending = '';
		$this->last_activity = '';
		$this->user_has_access = '';
		$this->is_visible = '';
		$this->args = '';
	}

}
