<?php
/**
 * Member Types Pro - Groups module.
 *
 * @package BuddyPress_Member_Types_pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Members Type to BuddyPress groups association.
 */
class BPMTP_Groups_Helper {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup hooks
	 */
	public function setup() {

		// ad admin meta box.
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		// save the preference
		// update on member type change.
		add_action( 'buddypress_member_types_pro_details_saved', array( $this, 'save_details' ) );

		add_action( 'bpmtp_set_member_type', array( $this, 'update_groups' ), 10, 3 );

		add_action( 'bpmtp_post_type_admin_enqueue_scripts', array( $this, 'load_js' ) );

		add_action( 'wp_ajax_bpmtp_get_groups_list', array( $this, 'group_auto_suggest_handler' ) );

	}

	/**
	 * Register the metabox for the WooCommerce Memberships plugin membership association to the member type.
	 */
	public function register_meta_box() {
		add_meta_box(
			'bp-member-type-groups',
			__( 'Associated BuddyPress Groups', 'buddypress-member-types-pro' ),
			array(
				$this,
				'render_meta_box',
			),
			bpmtp_get_post_type()
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param WP_Post $post currently editing member type post object.
	 */
	public function render_meta_box( $post ) {
		$meta            = get_post_custom( $post->ID );
		$selected_groups = isset( $meta['_bp_member_type_groups'] ) ? $meta['_bp_member_type_groups'][0] : array();
		$selected_groups = maybe_unserialize( $selected_groups );

		$sync = empty( $meta['_bp_member_type_sync_group'] ) ? 0 : 1;

		if ( ! empty( $selected_groups ) ) {
			$groups = groups_get_groups(
				array(
					'include'     => $selected_groups,
					'show_hidden' => true,
				)
			);
			$groups = $groups['groups'];

		} else {
			$groups = array();
		}

		?>
        <ul id="bpmtp-selected-groups-list">
			<?php foreach ( $groups as $group ) : ?>
                <li class="bpmtp-group-entry" id="bpmtp-group-<?php echo esc_attr( $group->id );?>">
                    <input type="hidden" value="<?php echo esc_attr( $group->id );?>" name="_bp_member_type_groups[]" />
                    <a class="bpmtp-remove-group" href="#">X</a>
                    <a href="<?php echo bpmtp_get_group_url( $group );?>"><?php echo $group->name;?> </a>
                </li>
			<?php endforeach; ?>
        </ul>
        <h3><?php _e( 'Select Group', 'buddypress-member-types-pro' );?></h3>
        <p>
            <input type="text" placeholder="<?php _e( 'Type group name.', 'buddypress-member-types-pro' );?>" id="bpmtp-group-selector" />
        </p>
        <p class='buddypress-member-types-pro-help'>
            <?php _e( 'The user will be added to these groups when his/her member type is updated.', 'buddypress-member-types-pro' ); ?>
        </p>

        <p>
            <label>
                <input type="checkbox" name="_bp_member_type_sync_group" value="1" <?php checked(1, $sync );?>/>
                <?php _e( 'Sync groups to member type.', 'buddypress-member-types-pro' ) ?>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'>
			<?php _e( 'If you enable it, the user will be removed from all other groups and only added to the selected group. In case a user is assigned multiple member type, the groups form the last member type will be the only groups they will be part of.', 'buddypress-member-types-pro' ); ?>
			<?php _e( 'If the group list is empty and sync is enabled, user will be removed from all groups.', 'buddypress-member-types-pro' ); ?>

        </p>
        <style type="text/css">
            .bpmtp-remove-group {
                padding-right: 5px;
                color: red;
            }
        </style>
    	<?php
	}

	/**
	 * Save the subscription association
	 *
	 * @param int $post_id numeric post id of the post containing member type details.
	 */
	public function save_details( $post_id ) {

		$groups = isset( $_POST['_bp_member_type_groups'] ) ? $_POST['_bp_member_type_groups'] : false;

		if ( $groups ) {
			$groups = array_unique( $groups );
			// should we validate the groups?
			// Let us trust site admins.
			update_post_meta( $post_id, '_bp_member_type_groups', $groups );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_groups' );
		}

		$sync = isset( $_POST['_bp_member_type_sync_group'] ) ? 1 : 0;
		if ( $sync ) {
			update_post_meta( $post_id, '_bp_member_type_sync_group', $sync );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_sync_group', $sync );
		}

	}

	/**
	 * Update role on new member type change
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append whether the member type was appended or reset.
	 */
	public function update_groups( $user_id, $member_type, $append ) {

		$active_types = bpmtp_get_active_member_type_entries();

		if ( empty( $member_type ) || empty( $active_types ) || empty( $active_types[ $member_type ] ) ) {
			return;
		}
		$mt_object = $active_types[ $member_type ];

		$groups = get_post_meta( $mt_object->post_id, '_bp_member_type_groups', true );

		$sync = get_post_meta( $mt_object->post_id, '_bp_member_type_sync_group', true );

		// We do not modify membership if the user is super admin or the new roles list is empty.
		// Based on feedback, we may want to remove all roles for empty in future.
		if ( empty( $groups ) ) {
			if ( $sync ) {
				// remove all user groups.
				$this->remove_all_groups( $user_id );
			}

			return;
		}


		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		if ( $sync ) {
			$current_groups = $this->get_all_user_groups( $user_id );

			$removable_groups = array_diff( $current_groups, $groups );
			$this->remove_groups( $user_id, $removable_groups );
		}

		// enable group joining as sending request to the group.
		$enable_request_for_private_groups = apply_filters( 'bpmtp_enable_group_join_as_request', false );
		// Now add the one/more roles to the user.
		foreach ( $groups as $group_id ) {
			// make sure to type cast.
			$group_id = (int) $group_id;
			if ( $enable_request_for_private_groups && bp_get_group_status( groups_get_group( $group_id ) ) === 'private' ) {
				groups_send_membership_request(
					array(
						'group_id' => $group_id,
						'user_id'  => $user_id,
					)
				);
			} else {
				groups_join_group( $group_id, $user_id );
			}
		}

	}

	/**
	 * Load Js
	 */
	public function load_js() {

		wp_register_script( 'bpmtp-admin-groups-helper', bpmtp_member_types_pro()->get_url() . 'admin/assets/js/bpmtp-admin-groups-helper.js', array(
			'jquery',
			'jquery-ui-autocomplete',
		) );
		wp_enqueue_script( 'bpmtp-admin-groups-helper' );
	}

	/**
	 * Group response builder
	 */
	public function group_auto_suggest_handler() {

		$search_term = isset( $_POST['q'] ) ? $_POST['q'] : '';
		$excluded = isset( $_POST['included'] )? wp_parse_id_list( $_POST['included'] ) : '';

		$groups      = groups_get_groups( array( 'search_terms' => $search_term, 'exclude'=> $excluded, 'show_hidden'=> true, ) );

		$groups = $groups['groups'];

		$list = array();
		foreach ( $groups as $group ) {
			$list[] = array(
				'label' => $group->name,
				'url'   => bpmtp_get_group_url( $group ),
				'id'    => $group->id,
			);
		}

		echo json_encode( $list );
		exit( 0 );

	}


	/**
     * Remove all groups from user.
     *
	 * @param int $user_id user id.
	 */
	private function remove_all_groups( $user_id ) {
		$this->remove_groups( $user_id, $this->get_all_user_groups( $user_id ) );
	}

	/**
     * Remove user from given groups.
     *
	 * @param int   $user_id user id.
	 * @param array $group_ids group ids.
	 */
	private function remove_groups( $user_id, $group_ids ) {
		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {
			groups_remove_member( $user_id, (int) $group_id );
		}

	}

	private function get_all_user_groups( $user_id ) {
		$groups = groups_get_user_groups( $user_id );
		return isset( $groups['groups'] ) ? $groups['groups']  : array();
	}
}

new BPMTP_Groups_Helper();
