<?php
/**
 * Helps in enhancing the post type list in the admni9m
 *
 * @package    BuddyPress Group Tabs Creator
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace PressThemes\BPGTC\Admin;

// Exit if file accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Admin hooks helper class
 */
class Post_Type_List_Helper {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type = '';

	/**
	 * Boot
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Setup function.
	 */
	private function setup() {

		$this->post_type = bpgtc_get_post_type();

		// add column.
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'show_data' ), 10, 2 );

		// sortable columns.
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'add_request_filter' ) );
		}

	/**
	 * Add new columns to the post type list screen
	 *
	 * @param array $columns columns array.
	 *
	 * @return array
	 */
	public function add_column( $columns ) {

		$columns['title'] = __( 'Label', 'buddypress-group-tabs-creator-pro' );

		$date_label = $columns['date'];
		unset( $columns['date'] );

		$columns['slug']       = __( 'Slug', 'buddypress-group-tabs-creator-pro' );
		$columns['position']   = __( 'Position', 'buddypress-group-tabs-creator-pro' );
		$columns['is_active']  = __( 'Active?', 'buddypress-group-tabs-creator-pro' );
		$columns['is_new']     = __( 'Is New Tab?', 'buddypress-group-tabs-creator-pro' );
		$columns['is_default'] = __( 'Is Default Component?', 'buddypress-group-tabs-creator-pro' );
		$columns['date']       = $date_label;

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

				if ( 'publish' === get_post( $post_id )->post_status && 'on' === get_post_meta( $post_id, '_bpgtc_tab_is_active', true ) ) {
					_e( 'Yes', 'buddypress-group-tabs-creator-pro' );
				} else {
					_e( 'No', 'buddypress-group-tabs-creator-pro' );
				}

				break;

			case 'position':
				$pos = get_post_meta( $post_id, '_bpgtc_tab_position', true );

				if ( ! $pos ) {
					if ( 'on' === get_post_meta( $post_id, '_bpgtc_tab_is_existing', true ) ) {
						$pos = __( 'N/A', 'buddypress-group-tabs-creator-pro' );
					} else {
						$pos = __( 'Random', 'buddypress-group-tabs-creator-pro' );
					}
				}

				echo $pos;

				break;

			case 'is_default':
				if ( 'on' === get_post_meta( $post_id, '_bpgtc_is_default_component', true ) ) {
					echo __( 'Yes', 'buddypress-group-tabs-creator-pro' );
				} else {
					echo __( 'No', 'buddypress-group-tabs-creator-pro' );
				}

				break;

			case 'is_new':
				if ( 'on' === get_post_meta( $post_id, '_bpgtc_tab_is_existing', true ) ) {
					echo __( 'No', 'buddypress-group-tabs-creator-pro' );
				} else {
					echo __( 'Yes', 'buddypress-group-tabs-creator-pro' );
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
	 * Sort items
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
				$qv['meta_key'] = '_bpgtc_tab_is_active';
				$qv['orderby']  = 'meta_value';

				break;


			case 'position':
				$qv['meta_key'] = '_bpgtc_tab_position';
				$qv['orderby']  = 'meta_value_num';

				break;
		}

		return $qv;
	}

	/**
	 * Hide quick edit link
	 *
	 * @param array   $actions actions array.
	 * @param \WP_Post $post post object.
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
