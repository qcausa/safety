<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * BP Member type List screen helper
 *
 */
class BPMTP_Member_Generator_Admin_List_Helper {

	/**
	 *
	 * @var BPMTP_Member_Generator_Admin_List_Helper
	 */
	private static $instance = null;

	private $post_type = '';

	private function __construct() {

		$this->post_type = bpmtp_get_post_type();

		$this->init();
	}

	/**
	 *
	 * @return BPMTP_Member_Generator_Admin_List_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function init() {
		//add column
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'show_data' ), 10, 2 );
		//sortable columns
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'add_request_filter' ) );

		//hide quick edit link on the custom post type list screen
		add_filter( 'post_row_actions', array( $this, 'hide_quickedit' ), 10, 2 );
	}

	/**
	 * Add new columns to the post type list screen
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_column( $columns ) {

		$columns['title'] = __( 'Label', '' );

		$date_label = $columns['date'];
		unset( $columns['date'] );

		$columns['member_type']      = __( 'Member Type', 'buddypress-member-types-pro' );
		$columns['is_active']        = __( 'Active?', 'buddypress-member-types-pro' );
		$columns['shortcode']        = __( 'Shortcode', 'buddypress-member-types-pro' );
		$columns['enable_directory'] = __( 'Has Directory?', 'buddypress-member-types-pro' );
		$columns['directory_slug']   = __( 'Directory Slug?', 'buddypress-member-types-pro' );
		$columns['directory_url']    = __( 'Directory URL', 'buddypress-member-types-pro' );
		//move date to last column
		$columns['date'] = $date_label;

		return $columns;
	}

	public function add_sortable_columns( $columns ) {

		$columns['is_active']        = 'is_active';
		$columns['enable_directory'] = 'enable_directory';
		$columns['member_type']      = 'member_type';

		return $columns;
	}

	public function show_data( $column, $post_id ) {

		switch ( $column ) {

			case 'member_type':
				echo get_post_meta( $post_id, '_bp_member_type_name', true );
				break;

			case 'is_active':

				if ( get_post_meta( $post_id, '_bp_member_type_is_active', true ) ) {
					echo __( 'Yes', 'buddypress-member-types-pro' );
				} else {
					echo __( 'No', 'buddypress-member-types-pro' );
				}

				break;

			case 'enable_directory':

				if ( get_post_meta( $post_id, '_bp_member_type_enable_directory', true ) ) {
					echo __( 'Yes', 'buddypress-member-types-pro' );
				} else {
					echo __( 'No', 'buddypress-member-types-pro' );
				}

				break;

			case 'directory_slug':

				echo get_post_meta( $post_id, '_bp_member_type_directory_slug', true );

				break;

			case 'directory_url':

				$directory_slug = get_post_meta( $post_id, '_bp_member_type_directory_slug', true );

				if ( ! $directory_slug ) {
					$directory_slug = get_post_meta( $post_id, '_bp_member_type_name', true );
				}
				//get the type slug, do not change text domain as it will get the actual one from BuddyPress translated file
				$type_slug = apply_filters( 'bp_members_member_type_base', _x( 'type', 'member type URL base', 'buddypress' ) );
				echo trailingslashit( get_permalink( buddypress()->pages->members->id ) ) . $type_slug . '/' . $directory_slug;

				break;

			case 'shortcode':
					$member_type = get_post_meta( $post_id, '_bp_member_type_name', true );
					echo "[bpmtp-members-list member_type={$member_type}]";
				break;

		}

	}

	public function add_request_filter() {
		add_filter( 'request', array( $this, 'sort_items' ) );
	}

	/**
	 * Sort list of member type post types
	 *
	 * @param array $qv
	 *
	 * @return string
	 */
	public function sort_items( $qv ) {

		if ( ! isset( $qv['post_type'] ) || $qv['post_type'] != $this->post_type ) {
			return $qv;
		}

		if ( ! isset( $qv['orderby'] ) ) {
			return $qv;
		}

		switch ( $qv['orderby'] ) {

			case 'member_type':

				$qv['meta_key'] = '_bp_member_type_name';
				$qv['orderby']  = 'meta_value';

				break;

			case 'directory_slug':

				$qv['meta_key'] = '_bp_member_type_directory_slug';
				$qv['orderby']  = 'meta_value';

				break;

			case 'is_active':

				$qv['meta_key'] = '_bp_member_type_is_active';
				$qv['orderby']  = 'meta_value_num';

				break;

			case 'enable_directory':

				$qv['meta_key'] = '_bp_member_type_enable_directory';
				$qv['orderby']  = 'meta_value_num';

				break;
		}

		return $qv;
	}

	/**
	 * Hide quick edit link
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function hide_quickedit( $actions, $post ) {

		if ( $this->post_type == $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}
}

BPMTP_Member_Generator_Admin_List_Helper::get_instance();
