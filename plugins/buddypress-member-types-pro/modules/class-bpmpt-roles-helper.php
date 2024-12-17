<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper for WordPress roles mapping to member types and vice versa.
 */
class BPMTP_Roles_Mapping_Helper {

	/**
	 * Boolean used as toggle to avoid infinite loop.
	 *
	 * @var bool
	 */
	//private $updating = false;

	/**
	 * Is member type updating?
	 *
	 * @var bool
	 */
	private $member_type_updating = false;

	/**
	 * Is role updating?
	 *
	 * @var bool
	 */
	private $role_updating = false;


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
		// for activation.
		// if role is already set, disable BuddyPress overriding it.
		add_action( 'bp_core_activated_user', array( $this, 'maybe_disable_bp_role_override' ), 0 );

		// Update roles on member type change.
		add_action( 'bpmtp_set_member_type', array( $this, 'update_roles' ), 10, 3 );
		add_action( 'set_user_role', array( $this, 'update_member_types' ), 10, 3 );
		add_action( 'add_user_role', array( $this, 'update_member_types_on_role_add' ), 10, 2 );

		// ad admin metabox.
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		// update on member type change.
		add_action( 'buddypress_member_types_pro_details_saved', array( $this, 'save_details' ) );
		/**
         * Since WordPress already allows selecting role while adding to blog
         * We should not use member type to decide role.
         */
		//add_action( 'add_user_to_blog', array( $this, 'update_roles_on_user_add' ), 10, 3 );

	}

	/**
	 * Stop BuddyPress from overwriting roles on activation.
	 *
	 * @param int $user_id user id.
	 */
	public function maybe_disable_bp_role_override( $user_id ) {

		if ( apply_filters( 'bpmtp_disable_bp_role_override', false ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}


		// If user has role assigned, and it does not contain the default role, disable bp from overwriting it.
		if ( $user->roles && ! in_array( bp_get_option( 'default_role' ), $user->roles ) ) {
			remove_action( 'bp_core_activated_user', 'bp_members_add_role_after_activation', 1 );
		} else {
			// otherwise force BuddyPress to override it.
			$user->remove_role( bp_get_option( 'default_role' ) );
		}
	}

	/**
	 * Update role on new member type change
	 *
	 * @param int     $user_id numeric user id.
	 * @param string  $member_type new member type.
	 * @param boolean $append whether the member type was appended or reset.
	 */
	public function update_roles( $user_id, $member_type, $append ) {

		if ( empty( $member_type ) ) {
			return;
		}

		// We do not modify roles if the user is super admin or the new roles list is empty.
		// Based on feedback, we may want to remove all roles for empty in future.
		if ( $this->role_updating || is_super_admin( $user_id ) ) {
			return;// do not change any role for super admins.
		}

		$this->member_type_updating = true;

		if ( is_multisite() ) {
			$this->update_roles_for_multisite( $user_id, $member_type );
			$this->member_type_updating = false;
			return;
		}

		$this->update_role_for_current_site( $user_id, $member_type, bpmtp_get_member_type_associated_roles( $member_type ) );
		$this->member_type_updating = false;
	}

	/**
	 * Update roles for multisite.
	 *
	 * @param int    $user_id user id.
	 * @param string $member_type member type.
	 */
	public function update_roles_for_multisite( $user_id, $member_type ) {
		$is_network_active = bpmtp_member_types_pro()->is_network_active();

		// If not network active.
		if ( ! $is_network_active ) {
			if ( is_main_site() ) {
				$this->update_role_for_current_site( $user_id, $member_type, bpmtp_get_member_type_associated_roles( $member_type ) );
			}

			return;
		}
		// if we are here, the plugin is network active.
		$preference = bpmtp_get_network_option( 'allow_site_role' );

		// we don't have to do anything on the sub sites.
		if ( 'none' === $preference ) {
			// update for the main site and return.
			$this->update_role_for_current_site( $user_id, $member_type, bpmtp_get_member_type_associated_roles( $member_type ) );

			return;
		}

		// find all blogs of the user.
		$sites = get_blogs_of_user( $user_id );
		if ( empty( $sites ) ) {
			return;
		}

		$site_ids = array_keys( $sites );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			if ( is_main_site( $site_id ) ) {
				$roles = bpmtp_get_member_type_associated_roles( $member_type );
			} else {
				$roles = bpmtp_get_member_type_associated_roles_for_multisite( $member_type );
			}

			$this->update_role_for_current_site( $user_id, $member_type, $roles );
			restore_current_blog();
		}

	}

	/**
	 * Update the roles of the give user for the current blog/site.
	 *
	 * @param int    $user_id user id.
	 * @param string $member_type member type.
	 */
	private function update_role_for_current_site( $user_id, $member_type, $roles ) {

		if ( empty( $roles ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		// remove all roles.
		$user->set_role( '' );

		// Now add the one/more roles to the user.
		foreach ( $roles as $role ) {
			$user->add_role( $role );
		}
	}

	/**
	 * On role add, update member types.
	 *
	 * @param int    $user_id user id.
	 * @param string $role role.
	 */
	public function update_member_types_on_role_add( $user_id, $role ) {
		// Do not update member type based on role if it is just the user creation step
		// from BuddyPress front end form.
		if ( $this->member_type_updating ) {
			return;
		}

		// We only support changing member type of a user based on main site role.
		if ( is_multisite() && ! is_main_site() ) {
			return;
		}

		// Get member types associated with this role.
		$member_types = bpmtp_get_option( 'role_' . $role . '_member_types' );

		// validate too?
		if ( empty( $member_types ) ) {
			return; // Do not set new types.
		}
		$this->role_updating = true;

		// remove all member types?
		if ( bp_get_member_type( $user_id ) ) {
			bp_set_member_type( $user_id, '' );
		}

		// reset old member type.
		foreach ( $member_types as $member_type ) {
			// Append all member types.
			bp_set_member_type( $user_id, $member_type, true );
		}

		$this->role_updating = false;
	}

	/**
	 * Update member types when role is updated.
	 * Note: Not used in current version, we do plan to use it when syncing from roles to member types.
	 *
	 * @param int    $user_id numeric user id.
	 * @param string $role new role.
	 * @param array  $old_roles old roles.
	 */
	public function update_member_types( $user_id, $role, $old_roles ) {

		// Do not update member type based on role if it is just the user creation step
		// from BuddyPress front end form.
		if ( $this->member_type_updating ) {
			return;
		}

		// Is this a valid role update for us?
		if ( ! $this->is_role_update_valid( $role, $old_roles ) ) {
			return;
		}

		// We only support changing member type of a user based on main site role.
		if ( is_multisite() && ! is_main_site() ) {
			return;
		}

		// Get member types associated with this role.
		$member_types = bpmtp_get_option( 'role_' . $role . '_member_types' );

		// validate too?
		if ( empty( $member_types ) ) {
			return; // Do not set new types.
		}
		$this->role_updating = true;

		// remove all member types?
		if ( bp_get_member_type( $user_id ) ) {
			bp_set_member_type( $user_id, '' );
		}

		// reset old member type.
		foreach ( $member_types as $member_type ) {
			// Append all member types.
			bp_set_member_type( $user_id, $member_type, true );
		}

		$this->role_updating = false;
	}

	/**
	 * Set the role of the user on given blog based on his/her member type.
	 *
	 * @param int    $user_id user id.
	 * @param string $role current role.
	 * @param int    $blog_id blog id.
	 */
	public function update_roles_on_user_add( $user_id, $role, $blog_id ) {
		// We do not change for super admin.
		if ( is_super_admin( $user_id ) ) {
			return;
		}

		if ( $this->role_updating ) {
			return;
		}

		$this->member_type_updating = true;


		$member_types = bp_get_member_type( $user_id, false );
		switch_to_blog( $blog_id );

		foreach ( $member_types as $member_type ) {
			$this->update_role_for_current_site( $user_id, $member_type, bpmtp_get_member_type_associated_roles_for_multisite( $member_type ) );
		}

		restore_current_blog();
		$this->member_type_updating = false;

	}

	/**
	 * Register the metabox for the PMPro plugin membership association to the member type.
	 */
	public function register_metabox() {
		add_meta_box( 'bp-member-type-roles', __( 'Associated Roles', 'buddypress-member-types-pro' ), array(
			$this,
			'render_metabox',
		), bpmtp_get_post_type() );
	}

	/**
	 * Render metabox.
	 *
	 * @param WP_Post $post post object.
	 */
	public function render_metabox( $post ) {
		$meta           = get_post_custom( $post->ID );
		$selected_roles = isset( $meta['_bp_member_type_roles'] ) ? $meta['_bp_member_type_roles'][0] : array();
		$selected_roles = maybe_unserialize( $selected_roles );

		$available_roles = get_editable_roles(); // wp_roles()->roles;

		?>
        <ul class="bpmtp-selectable-roles-list">
			<?php foreach ( $available_roles as $key => $role ): ?>
                <li>
                    <label>
                        <input type="checkbox"
                               value="<?php echo $key; ?>" <?php checked( true, in_array( $key, $selected_roles ) ); ?>
                               name="_bp_member_type_roles[]"><?php echo $role['name']; ?>
                    </label>
                </li>
			<?php endforeach; ?>
        </ul>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'The user will be assigned the associated role(s) when their member type is updated.', 'buddypress-member-types-pro' ); ?></p>
        <style type="text/css">
            .bpmtp-selectable-roles-list {
                max-height: 150px;
                overflow: auto;
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

		$roles = isset( $_POST['_bp_member_type_roles'] ) ? $_POST['_bp_member_type_roles'] : array();

		$roles = $this->get_validated_roles( $roles );

		if ( $roles ) {
			update_post_meta( $post_id, '_bp_member_type_roles', $roles );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_roles' );
		}
	}


	/**
	 * Check roles and make sure only allowed roles are valid.
	 *
	 * @param array $roles array of roles.
	 *
	 * @return array
	 */
	private function get_validated_roles( $roles ) {

		if ( empty( $roles ) ) {
			return $roles;
		}

		$all_roles = get_editable_roles();

		$udatable_roles = array();

		foreach ( $roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$udatable_roles[] = $role;
			}
		}

		return $udatable_roles;
	}

	/**
	 * Is it a valid role update for us to trigger actions?
	 *
	 * @param string $current_role current role.
	 * @param array  $old_roles old roles.
	 *
	 * @return bool
	 */
	private function is_role_update_valid( $current_role, $old_roles ) {
		if ( is_user_logged_in() ) {
			return true;
		}

		// if old roles are given, we do not care.
		if ( ! empty( $old_roles ) ) {
			return true;
		}

		// if we are here, $old_roles is empty.
		// If it was the case, and it is BuddyPres registration/Activation action
		// It is the action fired by WordPress wp_insert_user() and we should avoid it.
		if ( did_action( 'bp_signup_validate' ) && ! doing_action( 'bp_core_activated_user' ) ) {
			return false;
		} elseif ( bp_is_activation_page() ) {
			return false;
		}

		return true;
	}
}

new BPMTP_Roles_Mapping_Helper();
