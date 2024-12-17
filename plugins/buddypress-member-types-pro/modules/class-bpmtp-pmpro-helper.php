<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Helper for PaidMembershipPro plugin
 */
class BPMTP_PMPro_Membership_Helper {

	/**
	 * Is level being updated?
	 *
	 * @var bool
	 */
	private $updating_level = false;

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

		add_action( 'pmpro_approvals_after_deny_member', array( $this, 'mark_updating_level' ), 1 );
		add_action( 'pmpro_approvals_after_reset_member', array( $this, 'mark_updating_level' ), 1 );
		add_action( 'pmpro_after_change_membership_level', array( $this, 'mark_updating_level' ), 1 );

	}

	/**
	 * Set a flag that membership level is being updated and do not update it again.
	 */
	public function mark_updating_level() {
		$this->updating_level = true;
	}

	/**
	 * Register the metabox for the PMPro plugin membership association to the member type.
	 */
	public function register_metabox() {
		add_meta_box( 'bp-member-type-pmpro-membership', __( 'Associated Paid Memberships Pro Levels', 'buddypress-member-types-pro' ), array(
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
		$selected_level = get_post_meta( $post->ID, '_bp_member_type_pmpro_level', true );

		$levels = pmpro_getAllLevels();

		?>
        <ul>
            <li>
                <label>
                    <input type="radio" value="" name="_bp_member_type_pmpro_level" <?php checked(  '', $selected_level );?> ><?php _e( 'None', 'buddypress-member-types-pro' ); ?>
                </label>
            </li>
			<?php foreach ( $levels as $level ): ?>
                <li>
                    <label>
                        <input type="radio"
                               value="<?php echo $level->id; ?>" <?php checked(  $level->id, $selected_level ); ?>
                               name="_bp_member_type_pmpro_level"><?php echo $level->name; ?>
                    </label>
                </li>
			<?php endforeach; ?>
        </ul>
        <p class='buddypress-member-types-pro-help'>
            <?php _e( 'The user will be assigned the associated level(s) when their member type is updated.', 'buddypress-member-types-pro' ); ?>
            <?php _e( 'Changing member type will mark old levels as inactive(if the user had any).', 'buddypress-member-types-pro' ); ?>
            <?php _e( 'Also, Changing membership levels will have no effect on member type.', 'buddypress-member-types-pro' ); ?>

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

		$level_id = isset( $_POST['_bp_member_type_pmpro_level'] ) ? absint( $_POST['_bp_member_type_pmpro_level'] ) : false;

		if ( $level_id ) {
			// should we validate the plans?
			// && wc_memberships_get_membership_plan( $membership )
			update_post_meta( $post_id, '_bp_member_type_pmpro_level', $level_id);
		} else {
			delete_post_meta( $post_id, '_bp_member_type_pmpro_level' );
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

		if ( $this->updating_level || empty( $member_type ) || empty( $active_types ) || empty( $active_types[ $member_type ] ) ) {
			return;
		}

		$mt_object = $active_types[ $member_type ];

		$level_id = get_post_meta( $mt_object->post_id, '_bp_member_type_pmpro_level', true );

		// We do not modify membership if the user is super admin or the new roles list is empty.
		// Based on feedback, we may want to remove all roles for empty in future.
		if ( empty( $level_id ) || is_super_admin( $user_id ) ) {
			return;
		}


		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		// pmpro_changeMembershipLevel( $level_id, $user_id );
		$this->pmpro_set_level( $level_id, $user_id );
	}

	/**
	 * Set the pmpro level.
	 *
	 * @param int $level_id level id.
	 * @param int $user_id user id.
	 */
	private function pmpro_set_level( $level_id, $user_id ) {
		global $wpdb;
		// The code below is a joke.
		// This is an example how badly wrinne plugins are a mess to integrate with
		// We are doing ti since PMPro won't take start/end date automatically
		// and this approach was suggested by their suooprt
		// @see https://wordpress.org/support/topic/not-showing-expire-date/#post-12903235   .
		$pmpro_level = pmpro_getLevel( $level_id );

		if ( ! $pmpro_level ) {
			return;
		}

		// if checking out for the same level they have, keep their old start date.
		$sqlQuery = $wpdb->prepare(
			"SELECT startdate
                                FROM {$wpdb->pmpro_memberships_users}
                                WHERE user_id = %d
                                  AND membership_id = %d
                                  AND status = 'active'
                                ORDER BY id DESC
                                LIMIT 1",
			$user_id,
			$pmpro_level->id
		);

		$old_startdate = $wpdb->get_var( $sqlQuery );

		if ( ! empty( $old_startdate ) ) {
			$startdate = "'" . $old_startdate . "'";
		} else {
			$startdate = "'" . current_time( 'mysql' ) . "'";
		}

		$custom_level = array(
			'user_id'        => $user_id,
			'membership_id'  => $pmpro_level->id,
			'code_id'        => '',
			'billing_amount' => '',
			'cycle_number'   => '',
			'cycle_period'   => '',
			'billing_limit'  => '',
			'trial_amount'   => '',
			'trial_limit'    => '',
			'startdate'      => $startdate,
			'enddate'        => '0000-00-00 00:00:00',
		);

		// set end date.
		if ( ! empty( $pmpro_level->expiration_number ) ) {
			$custom_level['enddate'] = date( "Y-m-d", strtotime( "+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time( 'timestamp' ) ) );
		}

		/**
		 * Filter for the level object.
		 */
		$custom_level = apply_filters( 'pmprowoo_checkout_level', $custom_level );
		// Only add the first membership level found.
		pmpro_changeMembershipLevel( $custom_level, $user_id );

	}
}

new BPMTP_PMPro_Membership_Helper();
