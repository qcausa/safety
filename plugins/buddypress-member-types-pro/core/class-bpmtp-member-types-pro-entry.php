<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Our entry object for the member type.
 */
class BPMTP_Member_Types_Pro_Entry {

	/**
	 * Member type name
	 *
	 * @var string
	 */
	public $member_type = '';


	/**
	 * Associated post ID.
	 *
	 * @var int
	 */
	public $post_id = 0;


	/**
	 * Does this member type has directory enabled?
	 *
	 * @var bool
	 */
	public $has_directory = false;


	/**
	 * What is the assigned directory slug?
	 *
	 * @var string
	 */
	public $directory_slug = '';


	/**
	 * Do we list it in the directory tabs?
	 *
	 * @var bool
	 */
	public $list_in_directory = 0;


	/**
	 * Should we exclude members belonging to this type from the directory?
	 *
	 * @var int
	 */
	public $excluded_members_from_directory = 0;


	/**
	 * Is this member type active?
	 *
	 * @var bool|int
	 */
	public $is_active = 0;


	/**
	 * Array of associated roles
	 *
	 * @var array|mixed
	 */
	public $roles = array();


	/**
	 * Label for the plural name of this member type
	 *
	 * @var string
	 */
	public $label_name;


	/**
	 * Label for singular name of this member type
	 *
	 * @var string
	 */
	public $label_singular_name;

	/**
	 * Dynamic login url.
	 *
	 * @var string
	 */
	public $login_redirect_url;

	/**
	 * First login redirect url.
	 *
	 * @var string
	 */
	public $first_login_redirect_url;

	/**
	 * Dynamic activation url.
	 *
	 * @var string
	 */
	public $activation_redirect_url;

	/**
	 * Criteria for showing default profile tab.
	 *
	 * @var string
	 */
	public $default_profile_tab_criteria;

	/**
	 * Default profile tab slug.
	 *
	 * @var string
	 */
	public $default_profile_tab_slug;

	/**
	 * Associated avatar urls
	 *
	 * @var array[ thumb => '', full => '' ]
	 */
	public $associated_avatar_urls = array();

	/**
	 * Associate cover image url
	 *
	 * @var string
	 */
	public $associated_cover_image_url;

	/**
	 * Constructs entry from the post meta array and the post id.
	 *
	 * @param array $meta Post meta data array.
	 * @param int   $post_id numeric post id.
	 */
	public function __construct( $meta, $post_id = 0 ) {

		$this->post_id     = $post_id;
		$this->member_type = isset( $meta['_bp_member_type_name'] ) ? $meta['_bp_member_type_name'][0] : '';

		// Is this member type active?
		$this->is_active = isset( $meta['_bp_member_type_is_active'] ) ? (boolean) $meta['_bp_member_type_is_active'][0] : true;

		// Labels.
		$this->label_name          = isset( $meta['_bp_member_type_label_name'] ) ? $meta['_bp_member_type_label_name'][0] : '';
		$this->label_singular_name = isset( $meta['_bp_member_type_label_singular_name'] ) ? $meta['_bp_member_type_label_singular_name'][0] : '';

		// Dir Settings.
		// enabled by default.
		$this->has_directory  = isset( $meta['_bp_member_type_enable_directory'] ) ? $meta['_bp_member_type_enable_directory'][0] : 1;
		$this->directory_slug = isset( $meta['_bp_member_type_directory_slug'] ) ? $meta['_bp_member_type_directory_slug'][0] : '';

		$this->excluded_members_from_directory = isset( $meta['_bp_member_type_is_member_excluded'] ) ? $meta['_bp_member_type_is_member_excluded'][0] : 0;
		$this->list_in_directory               = isset( $meta['_bp_member_type_is_dir_tab_listable'] ) ? $meta['_bp_member_type_is_dir_tab_listable'][0] : 1;

		// Associated roles.
		$this->roles = isset( $meta['_bp_member_type_roles'] ) ? $meta['_bp_member_type_roles'][0] : array();
		$this->roles = maybe_unserialize( $this->roles );

		// redirects.
		$this->login_redirect_url       = isset( $meta['_bp_member_type_login_redirect_url'] ) ? trim( $meta['_bp_member_type_login_redirect_url'][0] ) : '';
		$this->first_login_redirect_url = isset( $meta['_bp_member_type_first_login_redirect_url'] ) ? trim( $meta['_bp_member_type_first_login_redirect_url'][0] ) : '';
		$this->activation_redirect_url  = isset( $meta['_bp_member_type_activation_redirect_url'] ) ? trim( $meta['_bp_member_type_activation_redirect_url'][0] ) : '';

		$this->default_profile_tab_criteria = isset( $meta['_bp_member_type_profile_tab_criteria'] ) ? trim( $meta['_bp_member_type_profile_tab_criteria'][0] ) : '';
		$this->default_profile_tab_slug = isset( $meta['_bp_member_type_default_profile_tab_slug'] ) ? trim( $meta['_bp_member_type_default_profile_tab_slug'][0] ) : '';
		// Xprofile field settings
		// Show in the list?
		// Coming soon.

		$this->associated_avatar_urls['thumb'] = isset( $meta['_bp_member_type_associated_avatar_thumb_url'] ) ? trim( $meta['_bp_member_type_associated_avatar_thumb_url'][0] ) : '';
		$this->associated_avatar_urls['full']  = isset( $meta['_bp_member_type_associated_avatar_full_url'] ) ? trim( $meta['_bp_member_type_associated_avatar_full_url'][0] ) : '';

		$this->associated_cover_image_url = isset( $meta['_bp_member_type_associated_cover_image_url'] ) ? trim( $meta['_bp_member_type_associated_cover_image_url'][0] ) : '';
	}
}
