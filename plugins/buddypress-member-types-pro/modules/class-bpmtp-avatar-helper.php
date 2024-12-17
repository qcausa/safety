<?php
/**
 * Member Types Pro - Member avatar module.
 *
 * @package BuddyPress_Member_Types_pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Members Type custom avatar support.
 */
class BPMTP_Avatar_Helper {

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
		// admin settings.
		add_action( 'bpmtp_admin_settings_page', array( $this, 'add_setting' ) );
		// add admin meta box.
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		// save the preference
		// update on member type change.
		add_action( 'buddypress_member_types_pro_details_saved', array( $this, 'save_details' ) );
		add_action( 'bpmtp_post_type_admin_enqueue_scripts', array( $this, 'load_js' ) );

		add_filter( 'bp_core_fetch_avatar_no_grav', array( $this, 'disable_gravatar' ) );
		add_filter( 'bp_core_default_avatar_user', array( $this, 'modify_avatar_url' ), 101, 2 );
	}

	/**
	 * Add admin setting to override member avatar with default member type avatar
	 *
	 * @param Press_Themes\PT_Settings\Page $page page object.
	 */
	public function add_setting( $page ) {

		$panel = $page->get_panel( 'misc-settings' );

		$section = $panel->add_section( 'avatar-section', _x( 'Default Avatar', 'Admin settings section title', 'buddypress-member-types-pro' ) );

		$section->add_field(
			array(
				'name'    => 'override_member_avatar',
				'label'   => __( 'Override member avatar', 'buddypress-member-types-pro' ),
				'type'    => 'checkbox',
				'default' => 0,
				'desc'    => __( 'if enabled member avatar will be override by member type avatar if user has not uploaded', 'buddypress-member-types-pro' ),
			)
		);
	}

	/**
	 * Register the metabox for associate avatar with each member type.
	 */
	public function register_meta_box() {
		add_meta_box(
			'bp-member-type-avatar',
			__( 'Associated avatar', 'buddypress-member-types-pro' ),
			array(
				$this,
				'render_meta_box',
			),
			bpmtp_get_post_type(),
			'side'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param WP_Post $post currently editing member type pobp_core_default_avatar_userst object.
	 */
	public function render_meta_box( $post ) {
		$avatar_thumb_url = get_post_meta( $post->ID, '_bp_member_type_associated_avatar_thumb_url', true );

		$avatar_full_url = get_post_meta( $post->ID, '_bp_member_type_associated_avatar_full_url', true );

		$thumb_style = '';
		$full_style  = '';

		if ( ! $avatar_thumb_url ) {
			$thumb_style = 'style=display:none;';
		}

		if ( ! $avatar_full_url ) {
			$full_style = 'style=display:none;';
		}

		wp_enqueue_media();
		wp_enqueue_script( 'bpmtp-admin-avatar-helper' );
		?>
		<p class="buddypress-member-types-pro-help bpmtp-avatar-status">
			<?php $avatar_support_active = bpmtp_get_option( 'override_member_avatar', 0 );?>
			<?php if( $avatar_support_active ) :?>
			 <span class="bpmtp-avatar-enabled">
				 <?php _e( 'The custom avatar support is: <strong>ENABLED</strong>', 'buddypress-member-types-pro' );?>
			 </span>
			<span class="bpmtp-avatar-setting-link">
				<?php printf( __( 'You can disable it from <a href="%s">settings</a> page', 'buddypress-member-types-pro'), admin_url('options-general.php?page=bpmtp-admin#misc-settings') );?>
			</span>
			<?php else : ?>
			<span class="bpmtp-avatar-enabled">
				<?php _e( 'The custom avatar support is: <strong>DISABLED</strong>', 'buddypress-member-types-pro' );?>
			</span>
			<span class="bpmtp-avatar-setting-link">
				<?php printf( __( 'You can enable it from <a href="%s">settings</a> page', 'buddypress-member-types-pro'), admin_url('options-general.php?page=bpmtp-admin#misc-settings') );?>
			</span>

			<?php endif;?>
			</p>
		<p>
			<img id="bpmtp-associated-avatar-thumb-image" src="<?php echo esc_url( $avatar_thumb_url ) ?>" <?php echo $thumb_style; ?>/>
			<a href="#" id="bpmtp-associated-avatar-thumb-delete-btn" <?php echo $thumb_style; ?>><?php _e( 'Remove thumbnail', 'buddypress-member-types-pro' ); ?></a>

			<input type="hidden" name="_bp_member_type_associated_avatar_thumb_url"
				   id="bpmtp-associated-avatar-thumb-url"
				   value="<?php echo esc_url( $avatar_thumb_url ) ?>"/>
			<?php
			$dim_thumb = '['.BP_AVATAR_THUMB_WIDTH . 'x'. BP_AVATAR_THUMB_HEIGHT.']';
			$dim_full = '['.BP_AVATAR_FULL_WIDTH . 'x'. BP_AVATAR_FULL_HEIGHT.']';
			printf( '<input type="button" class="button bpmtp-associated-avatar-upload-button bpmtp-associated-avatar-thumb-upload-button" value="%1$s" data-btn-title="%2$s" data-uploader-title="%2$s" />', __('Upload thumbnail image', 'buddypress-member-types-pro').$dim_thumb, 'Select' );
			?>

			<img id="bpmtp-associated-avatar-full-image" src="<?php echo esc_url( $avatar_full_url ) ?>" <?php echo $full_style; ?>/>
			<a href="#" id="bpmtp-associated-avatar-full-delete-btn" <?php echo $full_style; ?>><?php _e( 'Remove full', 'buddypress-member-types-pro' ); ?></a>

			<input type="hidden" name="_bp_member_type_associated_avatar_full_url" id="bpmtp-associated-avatar-full-url"
				   value="<?php echo esc_url( $avatar_full_url ) ?>"/>

			<?php
			printf( '<input type="button" class="button bpmtp-associated-avatar-upload-button bpmtp-associated-avatar-full-upload-button" value="%1$s" data-btn-title="%2$s" data-uploader-title="%2$s" />', __('Upload full image', 'buddypress-member-types-pro') . $dim_full, 'Select' );
			?>

		</p>
    	<?php
	}

	/**
	 * Save the subscription association
	 *
	 * @param int $post_id numeric post id of the post containing member type details.
	 */
	public function save_details( $post_id ) {

		$avatar_thumb_url = isset( $_POST['_bp_member_type_associated_avatar_thumb_url'] ) ? $_POST['_bp_member_type_associated_avatar_thumb_url'] : '';
		$avatar_full_url  = isset( $_POST['_bp_member_type_associated_avatar_full_url'] ) ? $_POST['_bp_member_type_associated_avatar_full_url'] : '';

		if ( $avatar_thumb_url ) {
			update_post_meta( $post_id, '_bp_member_type_associated_avatar_thumb_url', $avatar_thumb_url );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_associated_avatar_thumb_url' );
		}

		if ( $avatar_full_url ) {
			update_post_meta( $post_id, '_bp_member_type_associated_avatar_full_url', $avatar_full_url );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_associated_avatar_full_url' );
		}
	}

	/**
	 * Load Js
	 */
	public function load_js() {

		wp_register_script(
			'bpmtp-admin-avatar-helper',
			bpmtp_member_types_pro()->get_url() . 'admin/assets/js/bpmtp-admin-avatar-helper.js',
			array( 'jquery' )
		);

	}

	/**
	 * Disable gravatar if override member avatar enabled
	 *
	 * @param bool $no_grav no gravatar.
	 *
	 * @return bool
	 */
	public function disable_gravatar( $no_grav ) {
		if ( bpmtp_get_option( 'override_member_avatar', 0 ) ) {
			return true;
		}

		return $no_grav;
	}

	/**
	 * Modify avatar url based on member type default avatar_bp_member_type_associated$avatar_full_url_avatar_thumb_url
	 *
	 * @param string $avatar_url Avatar url.
	 * @param array  $params Parameters.
	 *
	 * @return string
	 */
	public function modify_avatar_url( $avatar_url, $params ) {

		if ( ! bpmtp_get_option( 'override_member_avatar', 0 ) ) {
			return $avatar_url;
		}

		if ( empty( $params['object'] ) || $params['object'] != 'user' || empty( $params['item_id'] ) ) {
			return $avatar_url;
		}

		$type = 'full' === $params['type'] ? 'full' : 'thumb';

		$active_types           = bpmtp_get_active_member_type_entries();
		$associated_avatar_urls = wp_list_pluck( $active_types, 'associated_avatar_urls', 'member_type' );

		$user_member_types = bp_get_member_type( $params['item_id'], false );

		if ( empty( $user_member_types ) ) {
			return $avatar_url;
		}

		$associated_avatar_url = '';
		foreach ( $user_member_types as $member_type ) {

		    if ( ! isset( $associated_avatar_urls[ $member_type ] ) || empty( $associated_avatar_urls[ $member_type ][$type] ) ) {
		        continue;
            }

			$associated_avatar_url = $associated_avatar_urls[ $member_type ][$type];

			if ( $associated_avatar_url ) {
				break;
			}
		}

		if ( empty( $associated_avatar_url ) ) {
			return $avatar_url;
		}

		// Removed code here.
		return $associated_avatar_url;
	}
}

new BPMTP_Avatar_Helper();
