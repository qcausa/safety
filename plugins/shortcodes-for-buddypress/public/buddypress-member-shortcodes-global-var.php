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
class BP_Memebr_Rest_Api_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $ID;

	/**
	 * The displayed user's user login.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $user_login;

	/**
	 * The displayed user's user pass.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $user_pass;

	/**
	 * The displayed user's user nicename.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $user_nicename;

	/**
	 * The displayed user's user email.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $user_email;

	/**
	 * The displayed user's user url.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $user_url;

	/**
	 * The displayed user's user registered.
	 *
	 * @since 2.1.0
	 * @var string
	 */
	public $user_registered;

	/**
	 * The displayed user's user_activation_key.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $user_activation_key;

	/**
	 * The displayed user's user status.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $user_status;

	/**
	 * The displayed user's display name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $display_name;

	/**
	 * The displayed user's id.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $id;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $fullname;

	/**
	 * The displayed user's friendship_status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $friendship_status;


	/**
	 * The displayed user's last_activity.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $last_activity;

	/**
	 * The displayed user's total_friend_count.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $total_friend_count;

	/**
	 * The displayed user's latest_update.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $latest_update;


	/**
	 * The displayed user's template_meta.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $template_meta;


	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct( $response_member_data ) {
		$member_friend_count = bp_get_user_meta( $response_member_data['id'], 'total_friend_count', true );
		$member_last_activity = bp_get_user_last_activity( $response_member_data['id'] );
		$userdata = get_userdata( $response_member_data['id'] );
		$this->ID = $response_member_data['id'];
		$this->user_login = $response_member_data['user_login'];
		$this->user_pass = $userdata->data->user_pass;
		$this->user_nicename = $response_member_data['user_login'];
		$this->user_email = $userdata->data->user_email;
		$this->user_url = $userdata->data->user_url;
		$this->user_registered = $userdata->data->user_registered;
		$this->user_activation_key = '';
		$this->user_status = '';
		$this->display_name = $userdata->data->display_name;
		$this->id = $response_member_data['id'];
		$this->fullname = $userdata->data->display_name;
		$this->friendship_status = $response_member_data['friendship_status'];
		$this->last_activity = isset( $member_last_activity ) ? $member_last_activity : '';
		$this->total_friend_count = isset( $member_friend_count ) ? $member_friend_count : '';
		$this->template_meta = array(
			'last_activity' => bp_get_member_last_active(),
		);

	}

}
