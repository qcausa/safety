<?php
// No direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Manages Updating of member type based on WooCommerce Memberships.
 * Changing Membership will set/reset BuddyPress member types.
 */
class BPMTP_WC_Membership_Member_Type_Helper {

	/**
	 * Setup hooks.
	 */
	public function setup() {

		// WooMembership does not fire 'wc_memberships_user_membership_status_changed'
		// for the first time transition from auto-draft to wcm-active.
		// we need to do processing on it too.
		add_action( 'transition_post_status',                array( $this, 'on_membership_add' ), 10, 3 );

		// handle Membership status changes.
		add_action( 'wc_memberships_user_membership_status_changed', array(
			$this,
			'on_membership_status_change',
		), 10, 3 );
		// user membership deleted.
		add_action( 'before_delete_post', array( $this, 'on_membership_delete' ) );

		//add_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_saved' ),10,2 );

		// admin panel tabs on the add membership plan page.
		add_filter( 'wc_membership_plan_data_tabs', array( $this, 'admin_add_member_type_options' ) );
		add_action( 'wc_membership_plan_data_panels', array( $this, 'admin_member_type_data_panel' ) );

		// update roles association.
		add_action( 'wc_memberships_save_meta_box',array( $this, 'save_associations' ), 10, 4 );
	}

	/**
	 * Handle post status transitions for user memberships
	 *
	 * @param string  $new_status New status slug.
	 * @param string  $old_status Old status slug.
	 * @param WP_Post $post Related WP_Post object.
	 */
	public function on_membership_add( $new_status, $old_status, WP_Post $post ) {

		if ( 'wc_user_membership' !== $post->post_type || $new_status === $old_status ) {
			return;
		}

		// It complements the woo memberships, so we only need for the auto-draft etc.
		if ( 'new' !== $old_status && 'auto-draft' !== $old_status ) {
			return;
		}

		// not for us.
		if ( strpos( $new_status, 'wcm-' ) !== 0 ) {
			return;
		}


		$user_membership = wc_memberships_get_user_membership( $post );

		$this->update_member_type_for_plan( $user_membership->get_plan_id(), $user_membership->get_user_id(), $user_membership->get_status() );
	}

	/**
	 * Handle user membership status changes
	 *
	 * @param \WC_Memberships_User_Membership $user_membership user membership object.
	 * @param string                          $old_status old membership status.
	 * @param string                          $new_status new membership status.
	 */
	public function on_membership_status_change( $user_membership, $old_status, $new_status ) {

		if ( $old_status === $new_status ) {
			return;// no change.
		}
		$this->update_member_type_for_plan( $user_membership->get_plan_id(), $user_membership->get_user_id(), $new_status );
	}

	/**
	 * On membership delete.
	 *
	 * @param int $post_id post id.
	 */
	public function on_membership_delete( $post_id ) {

		if ( get_post_type( $post_id ) !== 'wc_user_membership' ) {
			return;
		}

		$user_membership = wc_memberships_get_user_membership( $post_id );
		// we will only work for active membership.
		if ( ! $user_membership || $user_membership->get_status() != 'active' ) {
			return;
		}

		$this->update_member_type_for_plan( $user_membership->get_plan_id(), $user_membership->get_user_id(), 'cancelled' );
	}


	/**
	 * Not used.
	 *
	 * @param WC_Memberships_Membership_Plan $plan plan.
	 * @param array                          $args plan/user info.
	 */
	public function membership_saved( $plan, $args ) {
		if ( ! $plan ) {
			return;
		}

		$user_id = $args['user_id'];
		$user_membership = wc_memberships_get_user_membership( $args['user_membership_id'] );
		$this->update_member_type_for_plan( $plan->get_id(), $user_id, $user_membership->get_status() );
	}


	/**
	 * Add new tab on add/edit plan.
	 *
	 * @param array $tabs tabs.
	 *
	 * @return array new tabs.
	 */
	public function admin_add_member_type_options( $tabs ) {
		$tabs['bpmpt_member_types'] = array(
			'label'  => __( 'Member Types', 'buddypress-member-types-pro' ),
			'target' => 'membership-plan-data-bpmtp-member-types',
		);

		return $tabs;
	}


	/**
	 * Add roles data on the panel.
	 */
	public function admin_member_type_data_panel() {
		global $post;
		$member_types = bp_get_member_types( array(), 'objects' );
		$selected = get_post_meta( $post->ID, '_wcmc_member_types', true );

		if ( empty( $selected ) ) {
			$selected = array();
		}
		foreach ( $member_types as $key => $type ) {
			$member_types_list[ $key ] = $type->labels['singular_name'];
		}
		?>

		<div id="membership-plan-data-bpmtp-member-types" class="panel woocommerce_options_panel">

			<div class="table-wrap">
				<div class="widefat js-rules">
					<h4><?php _e( 'Set member type to:', 'buddypress-member-types-pro' ); ?></h4>
					<?php foreach ( $member_types as $key => $type ) : ?>
						<label>
							<input name="_wcmc_member_types[]" type="checkbox" value="<?php echo esc_attr( $key );?>" <?php checked( true, in_array( $key, $selected ) );?>> <?php echo esc_html( $type->labels['singular_name'] );?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div><!--//#membership-plan-data-bpmtp-member-types-->

		<style type="text/css">
			#membership-plan-data-bpmtp-member-types label {
				float: none;
				width: auto;
				margin:10px;
				display: block;
			}
			#membership-plan-data-bpmtp-member-types input[type='checkbox'] {
				margin-right: 10px;
			}
		</style>
	<?php }

	/**
	 * Save plan to role association.
	 *
	 * @param array   $pos_vars $_POST data.
	 * @param string  $box_id meta box id.
	 * @param int     $post_id post id.
	 * @param WP_Post $post post object.
	 */
	public function save_associations( $pos_vars, $box_id, $post_id, $post ) {

		$member_types = isset( $_POST['_wcmc_member_types'] ) ? $_POST['_wcmc_member_types'] : false;
		if ( ! $member_types ) {
			delete_post_meta( $post_id, '_wcmc_member_types' );
		} else {
			update_post_meta( $post_id, '_wcmc_member_types', $_POST['_wcmc_member_types'] );
		}
	}

	/**
	 * Update user member type(s) by plan id.
	 *
	 * @param int    $plan_id plan id.
	 * @param int    $user_id user id.
	 * @param string $new_status membership status.
	 */
	private function update_member_type_for_plan( $plan_id, $user_id, $new_status ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;// invalid user.
		}

		// let us get the roles.
		$plan_member_types = get_post_meta( $plan_id, '_wcmc_member_types', true );

		if ( empty( $plan_member_types ) ) {
			return;
		}
		// possible status.
		// cancelled
		// paused
		// active
		// expired.
		if ( 'active' === $new_status ) {
			// Remove any previous member type?.
			bp_set_member_type( $user_id,'' );
			// add member types.
			foreach ( $plan_member_types as $member_type ) {
				bp_set_member_type( $user_id, $member_type, true );// append member type .
			}
		} elseif ( 'cancelled' === $new_status || 'paused' === $new_status || 'expired' === $new_status ) {
			// remove these member types.
			foreach ( $plan_member_types as $member_type ) {
				bp_remove_member_type( $user_id, $member_type );
			}
		}
	}
}

// init.
$wcm = new BPMTP_WC_Membership_Member_Type_Helper();
$wcm->setup();
