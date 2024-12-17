<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Class for managing various template/display specific hooks.
 */
class BPMTP_Member_Types_Pro_Hooks {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setup();
	}


	/**
	 * Setup hooks.
	 */
	public function setup() {

        add_action( 'bp_set_member_type', array($this, 'member_types_set' ), 10, 3 );

		// Hide non editable member type fields.
		add_filter( 'bp_before_has_profile_parse_args', array( $this, 'exclude_fields_from_editing' ) );

		// Update member type members count.
		add_action( 'bp_before_directory_members_tabs', array( $this, 'update_member_type_members_count' ) );

		// Exclude member types.
		add_filter( 'bp_after_has_members_parse_args', array( $this, 'exclude_member_types' ) );


		// Filter bp_has_members args for scoping to the member type.
		add_filter( 'bp_after_has_members_parse_args', array( $this, 'modify_members_loop_args' ) );

		add_action( 'bp_members_directory_member_types', array( $this, 'add_directory_tabs' ) );

		// remove member type dropdown from Admin->Users->Edit Profile->Sidebar.
		add_action( 'bp_members_admin_user_metaboxes', array( $this, 'remove_membertype_metabox' ) );

		add_filter( 'manage_users_custom_column', array( $this, 'users_table_populate_type_cell' ), 11, 3 );

		// BP Nouveau crap.
		add_filter( 'bp_nouveau_get_members_directory_nav_items', array( $this, 'add_directory_tabs_nouveau' ) );

		if ( apply_filters( 'bpmtp_sort_member_types_alphabetically', true ) ) {
			add_filter( 'bp_get_member_types', array( $this, 'order_member_types_alphabetically' ) );
		}

	}

    /**
     * Add our own hook for Bp 7.0 compat.
     *
     * @param int    $user_id user id.
     * @param string $member_type member type.
     * @param bool   $append append.
     */
	public function member_types_set( $user_id, $member_type, $append ) {

	    if ( is_array( $member_type ) ) {
	        foreach ( $member_type as $mtype ) {
	            do_action( 'bpmtp_set_member_type', $user_id, $mtype, $append );
            }
        } else {
            do_action( 'bpmtp_set_member_type', $user_id, $member_type, $append );
        }
    }

	/**
	 * Filter 'bp_before_has_profile_parse_args' and exclude the non editable fields
	 *
	 * @param array $r args.
	 *
	 * @return mixed
	 */
	public function exclude_fields_from_editing( $r ) {

		// do not exclude the fields if the profile is being edited by super admin.
		if ( is_super_admin() ) {
			return $r;
		}

		// if we are not on edit profile, no need to restrict.
		if ( ! bp_is_user_profile_edit() && ! $this->is_admin_edit_profile() ) {
			return $r;
		}

		/*
		$user_id = false;

		if ( bp_is_user_profile_edit() ) {
			$user_id = bp_displayed_user_id();
		} elseif ( $this->is_admin_edit_profile() ) {
			$user_id = $this->get_user_id();
		}
		*/

		// Get non editable fields for this user.
		$noneditable_fields = bpmtp_get_non_editable_field_ids();

		if ( empty( $noneditable_fields ) ) {
			return $r;
		}

		$fields = isset( $r['exclude_fields'] ) ? $r['exclude_fields'] : array();

		if ( ! empty( $fields ) && ! is_array( $fields ) ) {
			$fields = explode( ',', $fields );
		}

		$excluded_fields = array_merge( $fields, $noneditable_fields );

		$r['exclude_fields'] = $excluded_fields;

		return $r;
	}


	/**
	 * Updated the member types member counts in efficient way
	 */
	public function update_member_type_members_count() {
		static $did;

		if ( ! is_null( $did ) ) {
			return; // no need to update mutiple times.
		}

		$member_types = buddypress()->members->types;
		$mt_terms     = array_keys( $member_types );

		if ( empty( $mt_terms ) ) {
			return;
		}

		// we have got list of active terms.
		$terms = get_terms( array(
			'taxonomy'   => bp_get_member_type_tax_name(),
			'hide_empty' => false,
			'slug'       => $mt_terms,
		) );

		if ( is_wp_error( $terms ) ) {
			return;
		}

		// key by slug.
		foreach ( $terms as $term ) {
			$terms[ $term->slug ] = $term;
		}

		foreach ( $member_types as $member_type => &$member_type_object ) {

			if ( isset( $terms[ $member_type ] ) ) {
				$member_type_object->count = $terms[ $member_type ]->count;
			} else {
				$member_type_object->count = 0;
			}
		}

		$did = true;
	}


	/**
	 *
	 * Excluded the members belonging to the excluded member types from directory
	 *
	 * @param array $args arguments of bp_has_members().
	 *
	 * @return array
	 */
	public function exclude_member_types( $args ) {

		if ( ! bp_is_members_directory() ) {
			return $args;
		}

		$excluded_member_types = bpmtp_get_dir_excluded_types();

		if ( empty( $excluded_member_types ) ) {
			return $args;
		}

		$already_excluded = $args['member_type__not_in'];

		if ( ! $already_excluded ) {
			$already_excluded = array();
		} elseif ( is_string( $already_excluded ) ) {
			$already_excluded = explode( ',', $already_excluded );
		} elseif ( is_array( $already_excluded ) ) {
			// do nothing?
		}

		$excluded = array_merge( $already_excluded, $excluded_member_types );

		$current_member_type = bp_get_current_member_type();

		// It is member type directory and current meber type is hidden.
		if ( ! $current_member_type || in_array( $current_member_type, $excluded ) ) {
			$args['member_type__not_in'] = $excluded;// $excluded;
		} else {
			$args['member_type__not_in'] = '';
		}

		return $args;
	}


	/**
	 * Filter Members Loop args to allow listing of member types via ajax.
	 *
	 * @param array $args arguments of bp_has_members() .
	 *
	 * @return array
	 */
	public function modify_members_loop_args( $args ) {

		// Let us not filter args in admin.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) || isset( $args['context'] ) ) {
			return $args;
		}

		$scope = isset( $args['scope'] ) ? $args['scope'] : null;
		if ( empty( $scope ) && ! empty( $_POST['scope'] ) ) {
			$scope = trim( wp_unslash( $_POST['scope'] ) );
		}

		if ( empty( $scope ) ) {
			return $args;
		}

		// filter member type.
		if ( ! empty( $scope ) && substr( $scope, 0, 5 ) === 'mtype' ) {
			$args['member_type'] = str_replace( 'mtype', '', $scope );
			$args['member_type__not_in'] = false;
			$excluded_member_types = bpmtp_get_dir_excluded_types();

			if ( in_array( $args['member_type'], $excluded_member_types ) ) {
				$args['member_type'] = false;// reset.
				$args['include']     = array( 0, 0 );
			}

			$args['scope']       = false; // unset.
		}
		return $args;
	}


	/**
	 * Add directory tabs.
	 */
	public function add_directory_tabs() {
		if ( class_exists( 'BP_Nouveau' ) ) {
			return;
		}
		$active_types = bpmtp_get_active_member_type_entries();
		?>
		<?php if ( function_exists( 'bp_get_member_types' ) ) : ?>
			<?php $member_types = bp_get_member_types( array(), 'objects' ); ?>
			<?php foreach ( $member_types as $member_type => $details ) : ?>
				<?php if ( isset( $active_types[ $member_type ] ) && $active_types[ $member_type ]->list_in_directory ) : ?>
                    <li id="members-mtype<?php echo $member_type; ?>">
                        <a href="<?php bp_member_type_directory_permalink( $member_type ); ?>"><?php echo $details->labels['name']; ?>
                            <span><?php echo $details->count; ?></span>
                        </a>
                    </li>
				<?php endif; ?>

			<?php endforeach; ?>
		<?php endif; ?>
		<?php
	}


	/**
	 * Add directory tabs.
	 */
	public function add_directory_tabs_nouveau( $nav_items ) {
		$this->update_member_type_members_count();
		$active_types = bpmtp_get_active_member_type_entries();

		$pos = 129;// selecting a random number which I don't like.

		if ( ! function_exists( 'bp_get_member_types' ) ) {
			return $nav_items;
		}

		$member_types = bp_get_member_types( array(), 'objects' );
		foreach ( $member_types as $member_type => $details ) {
			if ( isset( $active_types[ $member_type ] ) && $active_types[ $member_type ]->list_in_directory ) {

				$nav_items[ 'members-mtype' . $member_type ] = array(
					'component' => 'members',
					'slug'      => 'mtype' . $member_type,
					// slug is used because BP_Core_Nav requires it, but it's the scope.
					'li_class'  => array(),
					'link'      => bp_get_member_type_directory_permalink( $member_type ),
					'text'      => $details->labels['name'],
					'count'     => $details->count,
					'position'  => $pos,
				);

				$pos += 10;
			}
		}

        return $nav_items;
	}

	/**
	 * Remove the member type metabox from the Dashboard->Users->Edit user->extended profile screen
	 * It helps avoid conflict with the field update.
	 */
	public function remove_membertype_metabox() {
		remove_meta_box( 'bp_members_admin_member_type', get_current_screen()->id, 'side' );
	}


	/**
	 * Is this admin edit profile page?
	 *
	 * @global string $pagenow current page hook.
	 * @return boolean
	 */
	public function is_admin_edit_profile() {

		global $pagenow;

		if ( is_admin() && 'users.php' === $pagenow && isset( $_GET['page'] ) && $_GET['page'] == 'bp-profile-edit' ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the user id fro the admin screen
	 *
	 * @return int
	 */
	private function get_user_id() {

		$user_id = get_current_user_id();

		// We'll need a user ID when not on the user admin.
		if ( ! empty( $_GET['user_id'] ) ) {
			$user_id = $_GET['user_id'];
		}

		return intval( $user_id );
	}

	/**
	 * Until BuddyPress Provides support for displaying multiple member types, we are overriding the list.
	 *
	 * @return string Member type as a link to filter all users.
	 */
	public function users_table_populate_type_cell( $retval = '', $column_name = '', $user_id = 0 ) {
		// Only looking for member type column.
		if ( bp_get_member_type_tax_name() !== $column_name ) {
			return $retval;
		}

		$links = array();
		// Get the member types.
		$types = bp_get_member_type( $user_id, false );

		if ( empty( $types ) ) {
			return '';
		}
		// Output the.
		foreach ( $types as $type ) {
			if ( $type_obj = bp_get_member_type_object( $type ) ) {
				$url     = add_query_arg( array( 'bp-member-type' => urlencode( $type ) ) );
				$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $type_obj->labels['singular_name'] ) . '</a>';
			}
		}


		return join( ', ', $links );
	}

	/**
     * Sort member types array.
     *
	 * @param array $types member types array.
	 *
	 * @return array
	 */
	public function order_member_types_alphabetically( $types ) {
		uasort( $types, array( $this, 'compare_alphabetically' ) );
		return $types;
	}

	/**
     * Compare member types by name.
     *
	 * @param stdClass $member_type_1 member type.
	 * @param stdClass $member_type_2 member type.
	 *
	 * @return int
	 */
	private function compare_alphabetically( $member_type_1, $member_type_2 ) {
		return strcmp( $member_type_1->name, $member_type_2->name );
	}

}

new BPMTP_Member_Types_Pro_Hooks();
