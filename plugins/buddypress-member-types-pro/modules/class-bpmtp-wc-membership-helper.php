<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Members Type to WooCommerce membership association.
 */
class BPMTP_WC_Membership_Helper {


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

		// ad admin metabox.
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		// save the preference
		// update on member type change.
		add_action( 'buddypress_member_types_pro_details_saved', array( $this, 'save_details' ) );

		add_action( 'bpmtp_set_member_type', array( $this, 'update_membership' ), 10, 3 );

	}


	/**
	 * Register the metabox for the WooCommerce Memberships plugin membership association to the member type.
	 */
	public function register_metabox() {
		add_meta_box( 'bp-member-type-wc-membership', __( 'Associated WooCommerce Memberships', 'buddypress-member-types-pro' ), array(
			$this,
			'render_metabox',
		), bpmtp_get_post_type() );
	}


	/**
	 * Render metabox.
	 *
	 * @param WP_Post $post currently editing member type post object.
	 */
	public function render_metabox( $post ) {
		$meta           = get_post_custom( $post->ID );
		$selected_plans = isset( $meta['_bp_member_type_wc_memberships'] ) ? $meta['_bp_member_type_wc_memberships'][0] : array();
		$selected_plans = maybe_unserialize( $selected_plans );

		$plans = wc_memberships_get_membership_plans();

		?>
        <ul>
			<?php foreach ( $plans as $plan ): ?>
                <li>
                    <label>
                        <input type="checkbox"
                               value="<?php echo $plan->id; ?>" <?php checked( true, in_array( $plan->id, $selected_plans ) ); ?>
                               name="_bp_member_type_wc_memberships[]"><?php echo $plan->name; ?>
                    </label>
                </li>
			<?php endforeach; ?>
        </ul>
        <p class='buddypress-member-types-pro-help'>
            <?php _e( 'The user will be assigned the associated plan(s) when their member type is updated.', 'buddypress-member-types-pro' ); ?>
            <?php _e( 'Changing member type will not remove old plans, only add new ones.', 'buddypress-member-types-pro' ); ?>
            <?php _e( 'Also, Changing membership plans will have no effect on member type.', 'buddypress-member-types-pro' ); ?>

        </p>
        <p><a href="https://buddydev.com/plugins/buddypress-member-types-pro/#woocommerce-memberships"><?php _e( 'View Documentation', 'buddypress-member-types-pro' );?></a></p>
		<?php
	}


	/**
	 * Save the subscription association
	 *
	 * @param int $post_id numeric post id of the post containing member type details.
	 */
	public function save_details( $post_id ) {

		$membership = isset( $_POST['_bp_member_type_wc_memberships'] ) ? $_POST['_bp_member_type_wc_memberships'] : false;

		if ( $membership ) {
			// should we validate the plans?
			// && wc_memberships_get_membership_plan( $membership )
			update_post_meta( $post_id, '_bp_member_type_wc_memberships', $membership );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_wc_memberships' );
		}

	}

	/**
	 * Update role on new member type change
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append whether the member type was appended or reset.
	 */
	public function update_membership( $user_id, $member_type, $append ) {

		$active_types = bpmtp_get_active_member_type_entries();

		if ( empty( $member_type ) || empty( $active_types ) || empty( $active_types[ $member_type ] ) ) {
			return;
		}
		$mt_object = $active_types[ $member_type ];

		$memberships = get_post_meta( $mt_object->post_id, '_bp_member_type_wc_memberships', true );

		// We do not modify membership if the user is super admin or the new roles list is empty.
		// Based on feedbak, we may want to remove all roles for empty in future.
		if ( empty( $memberships ) || is_super_admin( $user_id ) ) {
			return;
		}


		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		// Now add the one/more roles to the user.
		foreach ( $memberships as $membership ) {
			$args = array(
				'plan_id' => $membership,
				'user_id' => $user_id,
			);

			// add plan to user.
			wc_memberships_create_user_membership( $args );
			// Optional: get the new membership and add a note so we know how this was registered.
			$user_membership = wc_memberships_get_user_membership( $user_id, $args['plan_id'] );
			$user_membership->add_note( 'Membership access granted due to member type change.' );
		}

	}
}

new BPMTP_WC_Membership_Helper();
