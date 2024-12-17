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
class BP_Activity_Rest_Api_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $id;

	/**
	 * The activity count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $user_id;

	/**
	 * The total activity count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $component;

	/**
	 * Array of activities located by the query.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $type;

	/**
	 * The activity object currently being iterated on.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $action;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $content;

	/**
	 * URL parameter key for activity pagination. Default: 'acpage'.
	 *
	 * @since 2.1.0
	 * @var string
	 */
	public $primary_link;

	/**
	 * The page number being requested.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $item_id;

	/**
	 * The number of items being requested per page.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $secondary_item_id;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $date_recorded;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $user_email;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $user_nicename;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $user_login;


	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $display_name;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $user_fullname;

	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $children;


	/**
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $depth;


	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct( $response_activity_data ) {

		if(is_array($response_activity_data) && !empty($response_activity_data)){
			
			$user_id                    = intval($response_activity_data['user_id']);                
			$userdata                   = get_userdata( $user_id );
			$user_login                 = sanitize_text_field($userdata->data->user_login);
			$primary_link               = esc_url(get_site_url() . '/member/' . $user_login);
			$user_nicename              = sanitize_text_field($userdata->data->user_nicename);
			$user_email                 = sanitize_email($userdata->data->user_email);
			$display_name               = sanitize_text_field($userdata->data->display_name);

			$this->id                   = intval($response_activity_data['id']);
			$this->user_id              = intval($response_activity_data['user_id']);
			$this->component            = sanitize_text_field($response_activity_data['component']);
			$this->type                 = sanitize_text_field($response_activity_data['type']);
			$this->action               = sanitize_text_field($response_activity_data['title']);
			$this->content              = sanitize_text_field($response_activity_data['content']);

			$this->primary_link         = $primary_link;
			$this->item_id              = isset( $response_activity_data['item_id'] ) ? intval($response_activity_data['item_id']) : 0;
			$this->secondary_item_id    = $response_activity_data['secondary_item_id'];
			$this->date_recorded        = isset( $response_activity_data['date_gmt'] ) ? strtotime(sanitize_text_field($response_activity_data['date_gmt'])) : 0;
			$this->hide_sitewide        = 0;
			$this->mptt_left            = 0;
			$this->mptt_right           = 0;
			$this->is_spam              = '';
			$this->user_nicename        = $user_nicename;
			$this->user_login           = $user_login;
			$this->display_name         = $display_name;
			$this->user_fullname        = $display_name;
			$this->user_email           = $user_email;
			$this->children             = isset( $response_activity_data['comments'] ) ? shortcodes_for_buddypress_iterate_comments($response_activity_data['comments']) : array();  // $children is a comment for activity
		}
		
	}

}
