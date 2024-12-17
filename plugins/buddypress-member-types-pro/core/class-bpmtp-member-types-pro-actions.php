<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class to register the internal member type post type and the actual Member type
 */
class BPMTP_Member_Types_Pro_Actions {


	/**
	 * Singleton instance
	 *
	 * @var BPMTP_Member_Types_Pro_Actions
	 */


	private static $instance = null;

	/**
	 * Track whether we are currently updating, It helps us avoid recursion.
	 *
	 * @var boolean
	 */
	private $updating;

	/**
	 * Track whether we are currently updating, It helps us avoid recursion.
	 *
	 * @var boolean
	 */
	private $updating_membertype = false;

	/**
	 * Track whether we are currently updating, It helps us avoid recursion.
	 *
	 * @var boolean
	 */
	private $updating_field = false;

	/**
	 * Constructor
	 */
	private function __construct() {

		// Register internal post type used to handle the member type.
		add_action( 'bp_init', array( $this, 'maybe_register_post_type' ) );

		// Register member type.
		add_action( 'bp_register_member_types', array( $this, 'register_member_type' ) );

		// Update member type when field is updated.
		//add_action( 'xprofile_data_after_save', array( $this, 'update_member_type' ) );
		// for multi field.
		//add_action( 'xprofile_data_after_save', array( $this, 'update_member_types' ) );

		// Sync field on member type update.
		//add_action( 'bp_set_member_type', array( $this, 'update_field_data' ), 10, 3 );
		//add_action( 'bp_set_member_type', array( $this, 'update_field_data_for_multi' ), 10, 3 );

	}

	/**
	 * Get singleton instance
	 *
	 * @return BPMTP_Member_Types_Pro_Actions
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Should we aregister post types on current blog ?
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
		} elseif ( ! bpmtp_member_types_pro()->is_network_active() ) {
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

		$do_register = apply_filters( 'bpmtp_do_register_post_type', $do_register );

		if ( $do_register  ) {
			$this->register_post_type();
		}
	}

	/**
	 * Register internal post type
	 */
	public function register_post_type() {

		$is_admin = is_super_admin();

		register_post_type( bpmtp_get_post_type(), array(
			'label'  => __( 'BuddyPress Member Types', 'buddypress-member-types-pro' ),
			'labels' => array(
				'name'               => __( 'Member Types', 'buddypress-member-types-pro' ),
				'singular_name'      => __( 'Member Type', 'buddypress-member-types-pro' ),
				'menu_name'          => __( 'Member Types( pro )', 'buddypress-member-types-pro' ),
				'add_new_item'       => __( 'New Member Type', 'buddypress-member-types-pro' ),
				'new_item'           => __( 'New Member Type', 'buddypress-member-types-pro' ),
				'edit_item'          => __( 'Edit Member Type', 'buddypress-member-types-pro' ),
				'search_items'       => __( 'Search Member Types', 'buddypress-member-types-pro' ),
				'not_found_in_trash' => __( 'No Member Types found in trash', 'buddypress-member-types-pro' ),
				'not_found'          => __( 'No Member Type found', 'buddypress-member-types-pro' ),
			),

			'public'       => false,// this is a private post type, not accesible from front end.
			'show_ui'      => $is_admin,
			'show_in_menu' => 'users.php',
			//	'menu_position'			=> 60,
			'menu_icon'    => 'dashicons-groups',
			'supports'     => array( 'title' ),
			// 'register_meta_box_cb'	=> array( $this, 'register_metabox'),
		) );
	}

	/**
	 * Register all active member types
	 */
	public function register_member_type() {

		// get all posts in member type post type.
		$active_types = bpmtp_get_active_member_type_entries();

		foreach ( $active_types as $member_type => $mt_object ) {

			// if not active or no unique key, do not register.
			if ( ! $member_type || ! $mt_object->is_active ) {
				continue;
			}


			$enable_directory = $mt_object->has_directory;
			$directory_slug   = $mt_object->directory_slug;

			$has_dir = false;

			if ( $enable_directory ) {

				if ( $directory_slug ) {
					$has_dir = $directory_slug;
				} else {
					$has_dir = true;
				}
			}

			bp_register_member_type( $member_type, array(
				'labels'        => array(
					'name'          => $mt_object->label_name,
					'singular_name' => $mt_object->label_singular_name,
				),
				'has_directory' => $has_dir, // only applies to bp 2.3+.
			) );

		}
	}


	/**
	 * Update the member type of a user when member type field is updated
	 *
	 * @param Object $data_field Xprofile data object.
	 */
	public function update_member_type( $data_field ) {

		$field = xprofile_get_field( $data_field->field_id );

		// we only need to worry about member type field.
		if ( 'membertype' !== $field->type ) {
			return;
		}

		if ( $this->updating ) {
			return;
		}

		$this->updating = true;

		$user_id     = $data_field->user_id;
		$member_type = maybe_unserialize( $data_field->value );

		// validate too?
		if ( empty( $member_type ) ) {

			// remove all member type?
			bp_set_member_type( $user_id, '' );
			$this->updating = false;
			return;
		}
		// Is this members type registered and active?, Then update.
		if ( bp_get_member_type_object( $member_type ) ) {
			bp_set_member_type( $user_id, $member_type );
		}
		$this->updating = false;
	}

	/**
	 * Update the member types of a user when member types field is updated(Multi member type)
	 *
	 * @param Object $data_field Xprofile data object.
	 */
	public function update_member_types( $data_field ) {

		$field = xprofile_get_field( $data_field->field_id );

		// we only need to worry about member type field.
		if ( 'membertypes' !== $field->type ) {
			return;
		}

		if ( $this->updating ) {
			return;
		}

		$this->updating = true;

		$user_id     = $data_field->user_id;
		$member_types = maybe_unserialize( $data_field->value );

		// remove all member type?
		bp_set_member_type( $user_id, '' );

		// validate too?
		if ( empty( $member_types ) ) {
			$this->updating = false;
			return; // Do not set new types.
		}

		// reset old member type.
		foreach ( $member_types as $member_type ) {
			// Append all member types.
			bp_set_member_type( $user_id, $member_type, true );
		}
		$this->updating = false;
	}

	/**
	 * Update field data when the member type is updated.
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append is it reset member type or append.
	 */
	public function update_field_data( $user_id, $member_type, $append ) {
		global $wpdb;
		// if the account is being deleted, there is no need to syc the fields
		// it will also help us break the cyclic dependency(infinite loop).
		if ( did_action( 'delete_user' ) || did_action( 'wpmu_delete_user' ) ) {
			return;
		}

		if ( $this->updating ) {
			return;
		}

		$fields = bpmtp_get_member_type_field_ids();

		if ( empty( $fields ) ) {
			return;
		}

		$this->updating = true;



		$list = '(' . join( ',', $fields ) . ')';

		$table = buddypress()->profile->table_name_data;

		$query = $wpdb->prepare( "SELECT field_id, value FROM {$table} WHERE field_id IN {$list} AND user_id = %d", $user_id );

		$data_fields = $wpdb->get_results( $query );

		// No value was set earlier for this field, we need to add one?
		if ( empty( $data_fields ) ) {

			foreach ( $fields as $field_id ) {
				xprofile_set_field_data( $field_id, $user_id, $member_type );
			}
			$this->updating = false;
			return;
		}

		// It will only run for member types.
		foreach ( $data_fields as $row ) {
			if ( $row->value && $row->value == $member_type ) {
				continue;
				// no need to update.
			}
			// if we are here, sync it.
			xprofile_set_field_data( $row->field_id, $user_id, $member_type );
		}
		$this->updating = false;
	}

	/**
	 * Update field data when the member type is updated.
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append is it reset member type or append.
	 */
	public function update_field_data_for_multi( $user_id, $member_type, $append ) {
		global $wpdb;
		// if the account is being deleted, there is no need to syc the fields
		// it will also help us break the cyclic dependency(infinite loop).
		if ( did_action( 'delete_user' ) || did_action( 'wpmu_delete_user' ) ) {
			return;
		}

		if ( $this->updating ) {
			return;
		}

		$fields = bpmtp_get_multi_member_type_field_ids();

		if ( empty( $fields ) ) {
			return;
		}

		$this->updating = true;

		$list = '(' . join( ',', $fields ) . ')';

		$table = buddypress()->profile->table_name_data;

		$query = $wpdb->prepare( "SELECT field_id, value FROM {$table} WHERE field_id IN {$list} AND user_id = %d", $user_id );

		$data_fields = $wpdb->get_results( $query );

		// get all current member types of the user and save it.
		$member_types = bp_get_member_type( $user_id, false );
		// No value was set earlier for this field, we need to add one?
		if ( empty( $data_fields ) ) {

			foreach ( $fields as $field_id ) {
				xprofile_set_field_data( $field_id, $user_id, $member_types );
			}
			$this->updating = false;
			return;
		}

		// Reset all existing fields with the current member types.
		foreach ( $data_fields as $row ) {
			//if ( $row->value && $row->value == $member_type ) {
			//	continue;
				// no need to update.
			//}
			// if we are here, sync it.
			xprofile_set_field_data( $row->field_id, $user_id, $member_types );
		}
		$this->updating = false;
	}
}

BPMTP_Member_Types_Pro_Actions::get_instance();
