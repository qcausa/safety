<?php
/**
 * BuddyPress Member Types Pro - Admin Tools
 *
 * Tool to Rename member types.
 *
 * @package    BuddyPress Member Types pro
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Class BPMTP_Admin_Tools
 */
class BPMTP_Admin_Tools {

	/**
	 * Class instance
	 *
	 * @var BPMTP_Admin_Tools
	 */
	private static $instance = null;

	/**
	 * The constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Get instance
	 *
	 * @return BPMTP_Admin_Tools
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup menu and and link to member type screen
	 */
	private function setup() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 100 );
		add_action( 'wp_ajax_bpmtp_rename_member', array( $this, 'rename' ) );
	}

	/**
	 * Rename.
	 */
	public function rename() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bpmtp_action_rename' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid action.', 'buddypress-member-types-pro' ),
			) );
		}

		// Only allow super admins to change.
		if ( ! is_super_admin() ) {
			wp_send_json_error( array(
				'message' => __( 'Access denied.', 'buddypress-member-types-pro' ),
			) );
		}

		// 1. make sure the current member type is valid.
		$current_type = isset( $_POST['current_type'] ) ? trim( $_POST['current_type'] ) : '';
		$new_type     = isset( $_POST['new_type'] ) ? trim( $_POST['new_type'] ) : '';

		if ( empty( $current_type ) || empty( $new_type ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid action.', 'buddypress-member-types-pro' ),
			) );
		}

		// is active empty?
		if ( ! bp_get_member_type_object( $current_type ) ) {
			wp_send_json_error( array(
				'message' => __( 'We only support renaming active member types.', 'buddypress-member-types-pro' ),
			) );
		}

		// Bp Restricts these and we should honour it.
		$illegal_names = apply_filters( 'bp_member_type_illegal_names', array( 'any', 'null', '_none' ) );
		if ( in_array( $new_type, $illegal_names, false) ) {
			// not valid.
			wp_send_json_error( array(
				'message' => __( 'The new value for member type is not allowed.', 'buddypress-member-types-pro' ),
			) );
		}

		// is same as new?
		if ( $current_type === $new_type ) {
			// can't change.
			wp_send_json_error( array(
				'message' => __( 'The old and new name should be different.', 'buddypress-member-types-pro' ),
			) );
		}

		$mtype = bpmtp_get_member_type_entry( $current_type );

		if ( ! $mtype ) {
			wp_send_json_error( array(
				/* translators: member type name */
				'message' => sprintf( __( 'The member type %s is not managed by BuddyPress Member Types Pro.', 'buddypress-member-types-pro' ), sanitize_text_field( $current_type ) ),
			) );
		}

		// 2. Check that the new type is available.
		if ( bpmtp_is_duplicate_name( $mtype->post_id, $new_type ) ) {
			wp_send_json_error( array(
				/* translators: member type name */
				'message' => sprintf( __( 'Please use a different name. %s is already a registered member type.', 'buddypress-member-types-pro' ), sanitize_text_field( $new_type ) ),
			) );
		}

		$new_term_slug = sanitize_key( $new_type );

		$tax = bp_get_member_type_tax_name();

		/**
		 * Is the term used by other taxonomy?
		 *
		 * We do not use shared terms.
		 */
		global $wpdb;
		$existing_term_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE slug = %s", $new_term_slug ) );

		if ( $existing_term_id ) {
			// already used by this taxonomy or some other taxonomy.
			wp_send_json_error( array(
				/* translators: member type name */
				'message' => sprintf( __( 'Please use a different name. We are unable to use %s due to the implemnetation limitations.', 'buddypress-member-types-pro' ), sanitize_text_field( $new_type ) ),
			) );
		}

		$term = get_term_by( 'slug', $current_type, $tax );

		if ( ! $term ) {
			// Problem.
			wp_send_json_error( array(
				'message' => __( 'There was a problem. Please try again later.', 'buddypress-member-types-pro' ),
			) );
		}

		// update terms table with the new type.
		// We change the slug of term. It helps us keep the old user associations.
		$updated = wp_update_term( $term->term_id, $tax, array( 'slug' => $new_term_slug ) );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( array(
				'message' => __( 'There was a problem saving details. Please try again later.', 'buddypress-member-types-pro' ),
			) );
		}

		// update our mtp posts with the new type.
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s", $new_type, $mtype->post_id, '_bp_member_type_name' ) );

		// update profile data with new type.
		$this->update_xprofile_field_data( $current_type, $new_type );

		// Prepare Output.
		ob_start();
		?>
        <tr>
            <th scope="row">
				<?php echo $mtype->label_name; ?>
            </th>
            <td>
				<?php echo $new_type; ?>
            </td>
            <td>
                <input type="text" value="" name="bpmtp_new_name" class="bpmtp-new-name" placeholder="<?php _e( 'New Name', 'buddypress-member-types-pro' ); ?>">
                <button class="button bpmtp-rename-action"><?php _e( 'Rename', 'buddypress-member-types-pro' ); ?></button>
                <input type="hidden" class="bpmtp-current-type"  name="bpmtp-current-type" value="<?php echo esc_attr( $mtype->member_type ); ?>" />
                <span class="bpmtp-loader" style="display: none;">...</span>
            </td>
        </tr>
		<?php

		$html = ob_get_clean();
		// all good.
		wp_send_json_success( array(
			'message' => __( 'Renamed.', 'buddypress-member-types-pro' ),
			'html'    => $html,
		) );
	}

	/**
	 * Update the xprofile field data to use new member type.
	 *
	 * @param string $old_type old member typ.
	 * @param string $new_type new member type.
	 */
	private function update_xprofile_field_data( $old_type, $new_type ) {

		if ( ! bp_is_active( 'xprofile' ) ) {
			return;
		}

		$field_ids = bpmtp_get_member_type_field_ids();

		if ( empty( $field_ids ) ) {
			return;
		}

		$in_ids = '(' . join( ',', $field_ids ) . ')';

		global $wpdb;
		$table = buddypress()->profile->table_name_data;
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET value = %s WHERE value = %s AND field_id IN {$in_ids}", $new_type, $old_type ) );
	}

	/**
	 * Adding submenu pages
	 */
	public function register_menu() {
		$hook = add_management_page( __( 'Member Types', 'buddypress-member-types-pro' ), __( 'Member Types', 'buddypress-member-types-pro' ), 'delete_users', 'member-types-tools', array(
			$this,
			'render',
		) );

		add_action( "admin_print_scripts-{$hook}", array( $this, 'load_assets' ) );
	}

	/**
	 * Load assets.
	 */
	public function load_assets() {
		wp_register_script( 'bpmtp-admin-tools', bpmtp_member_types_pro()->get_url() . 'admin/assets/member-type-admin-tools.js', array( 'jquery' ) );
		wp_enqueue_script( 'bpmtp-admin-tools' );
		wp_localize_script( 'bpmtp-admin-tools', 'BPMTPTools', array(
			'ajaxURL'             => admin_url( 'admin-ajax.php' ),
			'emptyTypeNameNotice' => __( 'Please provide new member type name.', 'buddypress-member-types-pro' ),
			'wpnonce'             => wp_create_nonce( 'bpmtp_action_rename' ),
		) );
	}

	/**
	 * Render import screen
	 */
	public function render() {
		?>
        <div class="wrap">
            <h2><?php _ex( 'Member Types Pro: Tools', 'Page heading', 'buddypress-member-types-pro' ) ?></h2>

            <div id="bpmtp-rename-section">
                <h4><?php _e( 'Member Type Safe Rename', 'buddypress-member-types-pro');?></h4>
                <hr/>
				<?php
				$member_types = bpmtp_get_active_member_type_entries();
				?>
                <table class="bpmtp-tools-form-table">
                    <thead>
                    <tr>
                        <th scope="col"><?php _e( 'Label', 'buddypress-member-types-pro' ); ?></th>
                        <th scope="col"><?php _e( 'Member Type', 'buddypress-member-types-pro' ); ?></th>
                        <th scope="col"><?php _e( 'Action', 'buddypress-member-types-pro' ); ?></th>
                    </tr>
                    </thead>
					<?php if ( $member_types ) : ?>
						<?php foreach ( $member_types as $mtype ): ?>
                            <tr>
                                <th scope="row">
									<?php echo $mtype->label_name; ?>
                                </th>
                                <td>
									<?php echo $mtype->member_type; ?>
                                </td>
                                <td>
                                    <input type="text" value="" name="bpmtp_new_name" class="bpmtp-new-name"
                                           placeholder="<?php _e( 'New Name', 'buddypress-member-types-pro' ); ?>">
                                    <button class="button bpmtp-rename-action"><?php _e( 'Rename', 'buddypress-member-types-pro' ); ?></button>
                                    <input type="hidden" class="bpmtp-current-type"  name="bpmtp-current-type" value="<?php echo esc_attr( $mtype->member_type ); ?>" />
                                    <span class="bpmtp-loader" style="display: none;">...</span>
                                </td>
                            </tr>
						<?php endforeach; ?>
					<?php else : ?>
                        <tr>
                            <td colspan="3">
								<?php __( 'No active member types found.', 'buddypress-member-types-pro' ); ?>
                            </td>
                        </tr>
					<?php endif; ?>
                </table>

            </div>
        </div>
		<?php
	}

	/**
	 * Render export screen
	 */
	public function render_export_screen() {
		?>
        <div class="wrap">
            <h2><?php _ex( 'Export Member Types', 'Page heading', 'buddypress-member-types-pro' ) ?></h2>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
							<?php _e( "export" ) ?>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
		<?php
	}
}

BPMTP_Admin_Tools::get_instance();

