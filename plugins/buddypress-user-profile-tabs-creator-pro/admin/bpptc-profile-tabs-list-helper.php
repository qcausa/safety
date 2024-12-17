<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Profile Tabs List screen helper
 */
class BPPTC_Admin_List_Helper {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type = '';

	/**
	 * BPPTC_Admin_List_Helper constructor.
	 */
	public function __construct() {

		$this->post_type = bpptc_get_post_type();
		$this->init();
	}

	/**
	 * Initialize.
	 */
	private function init() {
		// add column.
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'show_data' ), 10, 2 );
		// sortable columns.
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'add_request_filter' ) );

		// hide quick edit link on the custom post type list screen.
		add_filter( 'post_row_actions', array( $this, 'hide_quickedit' ), 10, 2 );
	}

	/**
	 * Add new columns to the post type list screen
	 *
	 * @param array $columns columns array.
	 *
	 * @return array
	 */
	public function add_column( $columns ) {

		$columns['title'] = __( 'Label', '' );

		$date_label = $columns['date'];
		unset( $columns['date'] );

		$columns['slug']      = __( 'Slug', 'buddypress-user-profile-tabs-creator-pro' );
		$columns['position']      = __( 'Position', 'buddypress-user-profile-tabs-creator-pro' );
		$columns['is_active']        = __( 'Active?', 'buddypress-user-profile-tabs-creator-pro' );
		$columns['is_new']        = __( 'Is New Tab?', 'buddypress-user-profile-tabs-creator-pro' );
		$columns['is_default']        = __( 'Is Default Component?', 'buddypress-user-profile-tabs-creator-pro' );
		$columns['date'] = $date_label;

		return $columns;
	}

	/**
	 * These columns are sortable.
	 *
	 * @param array $columns columns array.
	 *
	 * @return mixed
	 */
	public function add_sortable_columns( $columns ) {

		$columns['is_active'] = 'is_active';
		$columns['slug']      = 'slug';
		$columns['position']  = 'position';

		return $columns;
	}

	/**
	 * Show data.
	 *
	 * @param string $column column name.
	 * @param int    $post_id post id.
	 */
	public function show_data( $column, $post_id ) {

		switch ( $column ) {

			case 'slug':
				echo esc_html( get_post( $post_id )->post_name );
				break;

			case 'is_active':

				if ( 'publish' === get_post( $post_id )->post_status && 'on' === get_post_meta( $post_id, '_bpptc_tab_is_active', true ) ) {
					echo __( 'Yes', 'buddypress-user-profile-tabs-creator-pro' );
				} else {
					echo __( 'No', 'buddypress-user-profile-tabs-creator-pro' );
				}

				break;

			case 'position':
				$pos = get_post_meta( $post_id, '_bpptc_tab_position', true );

				if ( ! $pos ) {
					if ( 'on' === get_post_meta( $post_id, '_bpptc_tab_is_existing', true ) ) {
						$pos = __( 'N/A', 'buddypress-user-profile-tabs-creator-pro' );
					} else {
						$pos = __( 'Random', 'buddypress-user-profile-tabs-creator-pro' );
					}
				}

				echo $pos;

				break;

			case 'is_default':

				if ( 'on' === get_post_meta( $post_id, '_bpptc_is_default_component', true ) ) {
					echo __( 'Yes', 'buddypress-user-profile-tabs-creator-pro' );
				} else {
					echo __( 'No', 'buddypress-user-profile-tabs-creator-pro' );
				}
				break;

			case 'is_new':

				if ( 'on' === get_post_meta( $post_id, '_bpptc_tab_is_existing', true ) ) {
					echo __( 'No', 'buddypress-user-profile-tabs-creator-pro' );
				} else {
					echo __( 'Yes', 'buddypress-user-profile-tabs-creator-pro' );
				}
				break;
		}

	}

	/**
	 * Filter based on the sort option.
	 */
	public function add_request_filter() {
		add_filter( 'request', array( $this, 'sort_items' ) );
	}

	/**
	 * Sort list of member type post types
	 *
	 * @param array $qv query variable.
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

			case 'slug':
				$qv['orderby']  = 'post_name';

				break;

			case 'is_active':

				$qv['meta_key'] = '_bpptc_tab_is_active';
				$qv['orderby']  = 'meta_value';

				break;


			case 'position':

				$qv['meta_key'] = '_bpptc_tab_position';
				$qv['orderby']  = 'meta_value_num';

				break;
		}

		return $qv;
	}

	/**
	 * Hide quick edit link
	 *
	 * @param array   $actions actions array.
	 * @param WP_Post $post post object.
	 *
	 * @return array
	 */
	public function hide_quickedit( $actions, $post ) {

		if ( $this->post_type === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}
}

new BPPTC_Admin_List_Helper();
