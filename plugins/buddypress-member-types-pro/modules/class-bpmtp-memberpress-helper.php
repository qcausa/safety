<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Helper for PaidMembershipPro plugin
 */
class BPMTP_MemberPress_Helper {

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
	    // Meta box actions.
		add_action( 'mepr-product-options-tabs', array( $this, 'add_membership_options_tab' ) );
		add_action( 'mepr-product-options-pages', array( $this, 'render_member_types' ) );
		add_action( 'mepr-membership-save-meta', array( $this, 'save_associated_member_type' ) );

		add_action( 'mepr-txn-status-complete', array( $this, 'on_complete' ) );
		add_action( 'mepr-txn-status-refunded', array( $this, 'on_refund' ) );
		add_action( 'mepr-transaction-expired', array( $this, 'on_expire' ) );
		//add_action( 'mepr-subscr-status-active', array( $this, 'on_complete' ) );
		add_action( 'mepr-subscr-status-suspended', array( $this, 'on_suspension' ) );
		add_action( 'mepr-subscr-status-cancelled', array( $this, 'on_cancellation' ) );
		add_action( 'mepr-subscr-status-pending', array( $this, 'on_cancellation' ) );
	}

	/**
	 * Add new tab for associate member type
	 */
	public function add_membership_options_tab() {
		?>
        <a class="nav-tab main-nav-tab" href="#" id="associated-member-types"><?php esc_html_e( 'Associated Member Types', 'buddypress-member-types-pro' ); ?></a>
		<?php
	}

	/**
     * Compare
     *
	 * @param $a
	 * @param $b
	 *
	 * @return int|lt
	 */
	private function cmp( $a, $b ) {
		return strcmp( $a->label_name, $b->label_name );
	}

	/**
     * Render associate member types options
     *
	 * @param \MeprProduct $product MemberPress Product object
	 */
	public function render_member_types( $product ) {
		$member_type_entries   = bpmtp_get_active_member_type_entries();
		$saved_member_types    = $this->get_associated_member_types( $product->ID );
		$is_sync_on_membership = get_post_meta( $product->ID, '_memberpress_bpmtp_sync_membership', true );
		$is_sync_on_membership = $is_sync_on_membership ? $is_sync_on_membership : 0;
		$is_sync_on_removal    = get_post_meta( $product->ID, '_memberpress_bpmtp_sync_removal', true );
		$is_sync_on_removal    = $is_sync_on_removal ? $is_sync_on_removal : 0;

        ?>
		<div class="product_options_page associated-member-types">
			<div class="product-options-panel">

				<?php if ( $member_type_entries ) : ?>

                    <?php usort( $member_type_entries, array( $this, 'cmp' ) ); ?>

					<p><label><?php esc_html_e( 'Select Member Types:', 'buddypress-member-type-pro' ); ?></label></p>
					<p>
					<?php foreach ( $member_type_entries as $member_type_entry ) : ?>

						<label>
							<input type="checkbox" name="memberpress-associated-member-types[]" value="<?php echo esc_attr( $member_type_entry->member_type ); ?>" <?php checked( in_array( $member_type_entry->member_type, $saved_member_types ), true ); ?>>
							<?php echo esc_html( $member_type_entry->label_name ); ?>
						</label>

					<?php endforeach; ?>
					</p>
                    <p><label><?php esc_html_e( 'Sync:', 'buddypress-member-type-pro' ); ?></label></p>
                    <p>
                        <label>
                            <input type="checkbox" name="memberpress-bpmtp-sync-membership" value="1" <?php checked( $is_sync_on_membership, 1 ); ?>>
	                        <?php _e( "On membership activation, set user's member type(s) to the selected ones.", 'buddypress-member-types-pro' );?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="memberpress-bpmtp-sync-removal" value="1" <?php checked( $is_sync_on_removal, 1 ); ?>>
							<?php _e( "On membership expire/removal, remove these member types from user.", 'buddypress-member-types-pro' );?>
                        </label>
                    </p>
				<?php else: ?>

					<p>
						<?php esc_html_e( 'No member type found.', 'buddypress-member-types-pro' ); ?>
						<a href="<?php echo admin_url( 'post-new.php?post_type=bp-member-type' ); ?>"><?php esc_html_e( 'add here', 'buddypress-member-types-pro' ); ?></a>
					</p>

				<?php endif; ?>

			</div>
		</div>
		<?php
    }

	/**
	 * Save associated member type
	 *
	 * @param \MeprProduct $product Product object.
	 */
	public function save_associated_member_type( $product ) {
		$selected_member_types = isset( $_POST['memberpress-associated-member-types'] ) ? $_POST['memberpress-associated-member-types'] : array();
		$is_sync_on_membership = isset( $_POST['memberpress-bpmtp-sync-membership'] ) ? 1 : 0;
		$is_sync_on_removal    = isset( $_POST['memberpress-bpmtp-sync-removal'] ) ? 1 : 0;

		update_post_meta( $product->ID, '_memberpress_associated_member_types', $selected_member_types );
		update_post_meta( $product->ID, '_memberpress_bpmtp_sync_membership', $is_sync_on_membership );
		update_post_meta( $product->ID, '_memberpress_bpmtp_sync_removal', $is_sync_on_removal );
	}

	/**
	 * On transaction completed
	 *
	 * @param \MeprTransaction $txn Tax object.
	 */
	public function on_complete( $txn ) {
		//$membership = new MeprProduct($txn->product_id); //A MeprProduct object
		$associated_member_types = $this->get_associated_member_types( $txn->product_id );
		$is_sync_on_membership   = get_post_meta( $txn->product_id, '_memberpress_bpmtp_sync_membership', true );

		// if sync is enabled, first remove all member types.
		if ( ! empty( $is_sync_on_membership ) ) {
			// if sync is enabled, first remove all member types.
			bp_set_member_type( $txn->user_id, '' );
		}

		foreach ( $associated_member_types as $associated_member_type ) {
			bp_set_member_type( $txn->user_id, $associated_member_type, true );
		}
	}

	/**
     * Remove membertype for the membership
     *
	 * @param int $user_id User id.
	 * @param int $membership_id Membership id.
	 */
	private function remove_membership_member_types( $user_id, $membership_id ) {
		$associated_member_types = $this->get_associated_member_types( $membership_id );
		$is_sync_on_removal      = get_post_meta( $membership_id, '_memberpress_bpmtp_sync_removal', true );

		// if sync is not enabled, don't change member types.
		if ( empty( $is_sync_on_removal ) ) {
			// if sync is not enabled, don't change member types.
			return;
		}

		foreach ( $associated_member_types as $associated_member_type ) {
			bp_remove_member_type( $user_id, $associated_member_type );
		}
    }

	/**
	 * On transaction expire
	 *
	 * @param \MeprTransaction $txn Tax object.
	 */
	public function on_refund( $txn ) {
		$this->remove_membership_member_types( $txn->user_id, $txn->product_id );
	}

	/**
	 * On transaction expire
	 *
	 * @param \MeprTransaction $txn Tax object.
	 */
	public function on_expire( $txn ) {
		$this->remove_membership_member_types( $txn->user_id, $txn->product_id );
	}

	/**
	 * Remove member types
	 *
	 * @param MeprSubscription $subscription Subscription object.
	 */
	public function on_suspension( $subscription ) {
		$this->remove_membership_member_types( $subscription->user_id, $subscription->product_id );
	}

	/**
	 * Remove member types
	 *
	 * @param MeprSubscription $subscription Subscription object.
	 */
	public function on_cancellation( $subscription ) {
		$this->remove_membership_member_types( $subscription->user_id, $subscription->product_id );
    }

	/**
	 * Get associated member types with the membership
	 *
	 * @param int $membership_id Membership id.
	 *
	 * @return array
	 */
	private function get_associated_member_types( $membership_id ) {
		$types = get_post_meta( $membership_id,'_memberpress_associated_member_types', true );

		if ( empty( $types ) ) {
			return array();
		}

		return $types;
	}
}

new BPMTP_MemberPress_Helper();

