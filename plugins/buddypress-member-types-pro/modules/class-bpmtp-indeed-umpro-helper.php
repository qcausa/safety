<?php
/**
 * WP Indeed's Ultimate Membership Module for Member Types.
 *
 * @package    BuddyPress Member Types Pro
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

// No direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Manages Updating of member type based on Ultimate Membershis Pro's Memberships.
 * Changing Membership will set/reset BuddyPress member types.
 */
class BPMTP_Indeed_UMPro_Helper {

	/**
	 * Setup hooks.
	 */
	public function setup() {
		add_action( 'ihc_level_admin_html', array( $this, 'admin_member_type_data_panel' ) );
		add_action( 'ihc_admin_edit_save_level_after_submit_form', array( $this, 'save_meta' ), 10, 2 );

		add_filter( 'ihc_save_level_meta_names_filter', array( $this, 'enable_member_types_keys' ) );
		//add_filter( 'ihc_save_level_filter', array( $this, 'remove_member_types_if_empty' ) );

		// On user subscription activation(avoid, use assignment).
		// add_action( 'ihc_action_after_subscription_activated', array( $this, 'on_membership_add' ), 10, 2 );
		add_action( 'ihc_new_subscription_action', array( $this, 'on_membership_add' ), 110, 2 );

		// on user subscription deactivation.
		add_action( 'ihc_action_after_subscription_delete', array( $this, 'on_membership_remove' ), 10, 2 );
	}

	/**
	 * Enable member types keys for saving with level data.
	 *
	 * @param array $data level data.
	 *
	 * @return array
	 */
	public function enable_member_types_keys( $data ) {
		$data['bp_member_types']                  = array();
		$data['bp_member_type_sync_subscription'] = false;
		$data['bp_member_type_sync_removal']      = false;

		return $data;
	}

	/**
	 * Save member types associated with the level.
	 *
	 * @param array $data level data.
	 *
	 * @return array
	 */
	public function remove_member_types_if_empty( $data ) {

		if ( empty( $data['bp_member_types'] ) ) {
			unset( $data['bp_member_types'] );
		}

		return $data;
	}

	/**
	 * On add membership update member types.
	 *
	 * @param int $user_id user id.
	 * @param int $level_id level id.
	 */
	public function on_membership_add( $user_id, $level_id ) {

		$level = ihc_get_level_by_id( $level_id );

		if ( ! $level ) {
			return;
		}

		if ( ! empty( $level['bp_member_type_sync_subscription'] ) ) {
			// if sync is enabled, first remove all member types.
			bp_set_member_type( $user_id, '' );
		}

		$plan_member_types = isset( $level['bp_member_types'] ) ? maybe_unserialize( $level['bp_member_types'] ) : array();

		if ( empty( $plan_member_types ) ) {
			$plan_member_types = array();
		}

		// add member types.
		foreach ( $plan_member_types as $member_type ) {
			bp_set_member_type( $user_id, $member_type, true );// append member type .
		}
	}

	/**
	 * On add membership update member types.
	 *
	 * @param int $user_id user id.
	 * @param int $level_id level id.
	 */
	public function on_membership_remove( $user_id, $level_id ) {

		$level = ihc_get_level_by_id( $level_id );

		if ( ! $level ) {
			return;
		}

		if ( empty( $level['bp_member_type_sync_removal'] ) ) {
			// if sync is not enabled, don't change member types.
			return;
		}

		$plan_member_types = isset( $level['bp_member_types'] ) ? maybe_unserialize( $level['bp_member_types'] ) : array();

		if ( empty( $plan_member_types ) ) {
			$plan_member_types = array();
		}

		// add member types.
		foreach ( $plan_member_types as $member_type ) {
			bp_remove_member_type( $user_id, $member_type );
		}
	}

	/**
	 * Add roles data on the panel.
	 *
	 * @param array $level_data level data.
	 */
	public function admin_member_type_data_panel( $level_data ) {

		$member_types = bp_get_member_types( array(), 'objects' );
		$selected     = empty( $level_data['bp_member_types'] ) ? array() : maybe_unserialize( $level_data['bp_member_types'] );

		if ( empty( $selected ) ) {
			$selected = array();
		}

		foreach ( $member_types as $key => $type ) {
			$member_types_list[ $key ] = $type->labels['singular_name'];
		}

		$sync_sub = isset( $level_data['bp_member_type_sync_subscription'] )? $level_data['bp_member_type_sync_subscription'] : false;
		$sync_removal = isset( $level_data['bp_member_type_sync_removal'] )? $level_data['bp_member_type_sync_removal'] : false;
		?>
        <div class="ihc-stuffbox">
            <h3 class="ihc-h3"><?php _e( "Member Types", 'buddypress-member-types-pro' ); ?></h3>
            <div id="membership-plan-data-bpmtp-member-types" class="inside iumpro_options_panel">
                <p><a href="https://buddydev.com/docs/buddypress-member-types-pro/ultimate-memberships-pro-buddypress-member-types-integration/" title="<?php _e( 'View docs', 'buddypress-member-types-pro');?>"><?php _e( 'View Docs', 'buddypress-member-types-pro' );?></a> </p>
                <div class="iump-form-line">
                    <h4><?php _e( 'Set member type to:', 'buddypress-member-types-pro' ); ?></h4>
					<?php foreach ( $member_types as $key => $type ) : ?>
                        <label>
                            <input name="bp_member_types[]" type="checkbox"
                                   value="<?php echo esc_attr( $key ); ?>" <?php checked( true, in_array( $key, $selected ) ); ?>> <?php echo esc_html( $type->labels['singular_name'] ); ?>
                        </label>
					<?php endforeach; ?>
                </div>

                <div class="iump-form-line">
                    <h4><?php _e( 'Sync:', 'buddypress-member-types-pro' ); ?></h4>

                    <label>
                        <input type="checkbox" value="1" name="bp_member_type_sync_subscription" <?php checked( 1, $sync_sub  );?> />
                        <?php _e( "On subscription activation, set user's member type(s) to the selected ones.", 'buddypress-member-types-pro' );?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" value="1" name="bp_member_type_sync_removal" <?php checked( 1, $sync_removal  );?> />
                        <?php _e( 'On subscription expire/removal, remove these member types from user.', 'buddypress-member-types-pro' );?>
                    </label>

                </div>
                <div class="ihc-submit-form" style="margin-top: 20px;">
                    <input type="submit" value="<?php _e( 'Save Changes', 'buddypress-member-types-pro' ); ?>" name="ihc_save_level"
                           class="button button-primary button-large"/>
                </div>

            </div><!--//#membership-plan-data-bpmtp-member-types-->
        </div>
		<?php
	}

	/**
	 * Save meta
	 *
	 * @param int   $level_id Level id.
	 * @param array $posted_data Posted data.
	 */
	public function save_meta( $level_id, $posted_data ) {
		$bp_member_types                  = isset( $posted_data['bp_member_types'] ) ? $posted_data['bp_member_types'] : array();
		$bp_member_type_sync_subscription = isset( $posted_data['bp_member_type_sync_subscription'] ) ? 1 : 0;
		$bp_member_type_sync_removal      = isset( $posted_data['bp_member_type_sync_removal'] ) ? 1 : 0;

		\Indeed\Ihc\Db\Memberships::deleteOneMeta( $level_id, 'bp_member_types' );
		\Indeed\Ihc\Db\Memberships::deleteOneMeta( $level_id, 'bp_member_type_sync_subscription' );
		\Indeed\Ihc\Db\Memberships::deleteOneMeta( $level_id, 'bp_member_type_sync_removal' );

		\Indeed\Ihc\Db\Memberships::saveMeta( $level_id, 'bp_member_types', maybe_serialize( $bp_member_types ) );
		\Indeed\Ihc\Db\Memberships::saveMeta( $level_id, 'bp_member_type_sync_subscription', $bp_member_type_sync_subscription );
		\Indeed\Ihc\Db\Memberships::saveMeta( $level_id, 'bp_member_type_sync_removal', $bp_member_type_sync_removal );
	}
}

// init.
$indeed_umpro_mtp = new BPMTP_Indeed_UMPro_Helper();
$indeed_umpro_mtp->setup();
