<?php
/**
 * BuddyPress Member Types Pro, Learndash group module.
 *
 * @package    buddypress-member-types-pro
 * @subpackage modules
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Members Type to BuddyPress groups association.
 */
class BPMTP_Learndash_Groups_Helper {

	/**
	 * Flag to keep a check if we are already updating groups.
	 *
     * @var bool
	 */
	private $updating_groups = false;

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

		add_action( 'bpmtp_set_member_type', array( $this, 'update_groups' ), 10, 3 );
		/**
		 * Remove associated groups when member type is removed.
		 * It will not fire if member type is changed.
		 */
		add_action( 'bp_remove_member_type', array( $this, 'remove_groups' ), 10, 2 );

		add_action( 'bpmtp_post_type_admin_enqueue_scripts', array( $this, 'load_js' ) );

		add_action( 'wp_ajax_bpmtp_get_ld_groups_list', array( $this, 'group_auto_suggest_handler' ) );

	}

	/**
	 * Register the metabox for the learndash groups section.
	 */
	public function register_metabox() {
		add_meta_box( 'bp-member-type-ld-groups', __( 'Learndash Groups', 'buddypress-member-types-pro' ), array(
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
		$selected_groups = isset( $meta['_bp_member_type_ld_groups'] ) ? $meta['_bp_member_type_ld_groups'][0] : array();
		$selected_groups = maybe_unserialize( $selected_groups );

		$sync         = isset( $meta['_bp_member_type_ld_groups_sync'] ) ? $meta['_bp_member_type_ld_groups_sync'][0] : 0;
		$sync_removal = isset( $meta['_bp_member_type_ld_groups_remove_on_member_type_change'] ) ? $meta['_bp_member_type_ld_groups_remove_on_member_type_change'][0] : 0;

		// redo it.
		if ( ! empty( $selected_groups ) ) {
			$groups = new WP_Query( array(
				'post__in' => $selected_groups,
				'per_page'  => - 1,
				'post_type' => 'groups',
			) );

		} else {
			$groups = null;
		}

		?>
		<ul id="bpmtp-selected-ld-groups-list">
			<?php if( $groups && $groups->have_posts() ):?>
				<?php while ( $groups->have_posts() ): $groups->the_post(); ?>
					<li class="bpmtp-ld-group-entry" id="bpmtp-ld-group-<?php echo esc_attr( get_the_ID() );?>">
						<input type="hidden" value="<?php echo esc_attr(get_the_ID() );?>" name="_bp_member_type_ld_groups[]" />
						<a class="bpmtp-remove-ld-group" href="#">X</a>
						<a href="<?php the_permalink();?>"><?php the_title();?> </a>
					</li>
				<?php endwhile; ?>
				<?php wp_reset_query(); ?>
		<?php endif;?>
		</ul>
		<h3><?php _e( 'Select Group', 'buddypress-member-types-pro' );?></h3>
		<p>
			<input type="text" placeholder="<?php _e( 'Type group name.', 'buddypress-member-types-pro' );?>" id="bpmtp-ld-group-selector" />
		</p>
		<p class='buddypress-member-types-pro-help'>
			<?php _e( 'The user will be added to these learndash groups when his/her member type is updated.', 'buddypress-member-types-pro' ); ?>
		</p>
        <p>
            <label>
                <input type="checkbox" value="1" name="_bp_member_type_ld_groups_remove_on_member_type_change" <?php checked( 1, $sync_removal );?>>
                <strong><?php _e( 'Remove groups on user removal from this member type.', 'buddypress-member-types-pro' ); ?></strong>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'>
			<?php _e( 'If enabled, When this member type is removed from user, user will be removed from the above selected Learndash groups.', 'buddypress-member-types-pro' ); ?>
        </p>

        <p>
            <label>
                <input type="checkbox" value="1" name="_bp_member_type_ld_groups_sync" <?php checked( 1, $sync );?>>
                <strong><?php _e( 'Remove Extra Groups.', 'buddypress-member-types-pro' ); ?></strong>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'>
			<?php _e( 'If enabled, When users member type changes, they will be removed from all other Learndash groups except the above list.', 'buddypress-member-types-pro' ); ?>
        </p>

		<style type="text/css">
			.bpmtp-remove-ld-group {
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

		$groups = isset( $_POST['_bp_member_type_ld_groups'] ) ? $_POST['_bp_member_type_ld_groups'] : false;

		if ( $groups ) {
			$groups = array_unique( $groups );
			// should we validate the groups?
			// Let us trust site admins.
			update_post_meta( $post_id, '_bp_member_type_ld_groups', $groups );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_ld_groups' );
		}

		$sync = isset( $_POST['_bp_member_type_ld_groups_sync'] ) ? 1 : 0;
		update_post_meta( $post_id, '_bp_member_type_ld_groups_sync', $sync );

		$sync_removal = isset( $_POST['_bp_member_type_ld_groups_remove_on_member_type_change'] ) ? 1 : 0;
		update_post_meta( $post_id, '_bp_member_type_ld_groups_remove_on_member_type_change', $sync_removal );
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

		if ( $this->updating_groups ) {
			return;
		}
		$this->updating_groups = true;

		$mt_object = $active_types[ $member_type ];

		$new_groups = get_post_meta( $mt_object->post_id, '_bp_member_type_ld_groups', true );

		if ( empty( $new_groups ) ) {
			$new_groups = array();
		}

		$existing_groups = learndash_get_users_group_ids( $user_id );

		if ( empty( $existing_groups ) ) {
			$existing_groups = array();
		}

		$force_sync = get_post_meta( $mt_object->post_id, '_bp_member_type_ld_groups_sync', true );

		if ( empty( $force_sync ) && ! $new_groups ) {
			return;// do not remove user from all groups.
		}

		if ( $force_sync ) {
			$removable_groups = array_diff( $existing_groups, $new_groups );
		} else {
			$removable_groups = array();
		}

		foreach ( $removable_groups as $group_id ) {
			ld_update_group_access( $user_id, $group_id, true );
		}

		$addable_group = array_diff( $new_groups, $existing_groups );

		foreach ( $addable_group as $group_id ) {
			ld_update_group_access( $user_id, $group_id, false );
		}

		$this->updating_groups = false;

	}

	/**
	 * Removes users from the groups of the removed member types.
	 *
	 * @param int    $user_id user id.
	 * @param string $member_type member type being removed.
	 */
	public function remove_groups( $user_id, $member_type ) {

		$active_types = bpmtp_get_active_member_type_entries();

		if ( empty( $member_type ) || empty( $active_types ) || empty( $active_types[ $member_type ] ) ) {
			return;
		}

		$mt_object = $active_types[ $member_type ];

		$sync_removal = get_post_meta( $mt_object->post_id, '_bp_member_type_ld_groups_remove_on_member_type_change', true );

		if ( ! $sync_removal ) {
			return;
		}

		$group_ids = get_post_meta( $mt_object->post_id, '_bp_member_type_ld_groups', true );

		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {
			ld_update_group_access( $user_id, $group_id, true );
		}
	}

	/**
	 * Load Js
	 */
	public function load_js() {

		wp_register_script( 'bpmtp-admin-ld-groups-helper', bpmtp_member_types_pro()->get_url() . 'admin/assets/js/bpmtp-admin-ld-groups-helper.js', array(
			'jquery',
			'jquery-ui-autocomplete',
		) );
		wp_enqueue_script( 'bpmtp-admin-ld-groups-helper' );
	}

	/**
	 * Group response builder
	 */
	public function group_auto_suggest_handler() {

		$search_term = isset( $_POST['q'] ) ? $_POST['q'] : '';
		$excluded = isset( $_POST['included'] )? wp_parse_id_list( $_POST['included'] ) : array();

		$args = array(
			'post_type' => 'groups', // learndash uses 'groups' as the post type name.
			's'         => $search_term,
		);

		if ( $excluded ) {
			$args['post__not_in'] = $excluded;
		}

		$groups      = new WP_Query( $args );

		$list = array();
		while ( $groups->have_posts() ) {
			$groups->the_post();

			$list[] = array(
				'label' => get_the_title(),
				'url'   => get_the_permalink(),
				'id'    => get_the_ID(),
			);
		}

		echo json_encode( $list );
		exit( 0 );

	}
}

new BPMTP_Learndash_Groups_Helper();
