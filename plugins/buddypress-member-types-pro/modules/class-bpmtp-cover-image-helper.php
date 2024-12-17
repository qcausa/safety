<?php
/**
 * Member Types Pro - Member cover image module.
 *
 * @package BuddyPress_Member_Types_pro
 *
 * @contributor: Ravi Sharma.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Members Type custom cover image support.
 */
class BPMTP_Cover_Image_Helper {

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
		add_filter( 'bp_before_members_cover_image_settings_parse_args', array( $this, 'modify_cover_image_url' ), 20 );
		add_filter( 'bp_before_members_cover_image_settings_parse_args', array( $this, 'modify_cover_image_url' ), 20 );
	}

	/**
	 * Add admin setting to override member cover image with default member type cover image
	 *
	 * @param Press_Themes\PT_Settings\Page $page page object.
	 */
	public function add_setting( $page ) {

		$panel = $page->get_panel( 'misc-settings' );

		$section = $panel->add_section( 'cover-image-section', _x( 'Default cover image', 'Admin settings section title', 'buddypress-member-types-pro' ) );

		$section->add_field(
			array(
				'name'    => 'override_member_cover_image',
				'label'   => __( 'Override member cover image', 'buddypress-member-types-pro' ),
				'type'    => 'checkbox',
				'default' => 0,
				'desc'    => __( 'if enabled member cover image will be override by member type cover image if user has not uploaded', 'buddypress-member-types-pro' ),
			)
		);
	}

	/**
	 * Register the metabox for associate cover image with each member type.
	 */
	public function register_meta_box() {
		add_meta_box(
			'bp-member-type-cover-image',
			__( 'Associated cover image', 'buddypress-member-types-pro' ),
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
	 * @param WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$cover_image_url = get_post_meta( $post->ID, '_bp_member_type_associated_cover_image_url', true );

		$cover_image_style = '';

		if ( ! $cover_image_url ) {
			$cover_image_style = 'style=display:none;';
		}

		wp_enqueue_media();
		wp_enqueue_script( 'bpmtp-admin-cover-image-helper' );
		?>
        <p class="buddypress-member-types-pro-help bpmtp-cover-image-status">
	        <?php $cover_image_support_active = bpmtp_get_option( 'override_member_cover_image', 0 ); ?>
            <?php if( $cover_image_support_active ) :?>
             <span class="bpmtp-cover-image-enabled">
                 <?php _e( 'The custom cover image support is: <strong>ENABLED</strong>', 'buddypress-member-types-pro' );?>
             </span>
            <span class="bpmtp-cover-image-setting-link">
                <?php printf( __( 'You can disable it from <a href="%s">settings</a> page', 'buddypress-member-types-pro'), admin_url('options-general.php?page=bpmtp-admin#misc-settings') );?>
            </span>
            <?php else : ?>
            <span class="bpmtp-cover-image-enabled">
                <?php _e( 'The custom cover image support is: <strong>DISABLED</strong>', 'buddypress-member-types-pro' );?>
            </span>
            <span class="bpmtp-cover-image-setting-link">
                <?php printf( __( 'You can enable it from <a href="%s">settings</a> page', 'buddypress-member-types-pro'), admin_url('options-general.php?page=bpmtp-admin#misc-settings') );?>
            </span>

            <?php endif;?>
            </p>
        <p>
            <img id="bpmtp-associated-cover-image" src="<?php echo esc_url( $cover_image_url ) ?>" <?php echo $cover_image_style; ?>/>
            <a href="#" id="bpmtp-associated-cover-image-delete-btn" <?php echo $cover_image_style; ?>><?php _e( 'Remove image', 'buddypress-member-types-pro' ); ?></a>

            <input type="hidden" name="_bp_member_type_associated_cover_image_url"
                   id="bpmtp-associated-cover-image-url"
                   value="<?php echo esc_url( $cover_image_url ) ?>"/>
            <?php
            $dimensions = bp_attachments_get_cover_image_dimensions();
            $dimensions = ( empty( $dimensions ) ) ? '' : '[' . join( 'x', $dimensions ) . ']';
            printf( '<input type="button" class="button bpmtp-associated-cover-image-upload-button" value="%1$s" data-btn-title="%2$s" data-uploader-title="%2$s" />', __('Upload cover image', 'buddypress-member-types-pro') . $dimensions, 'Select' );
            ?>

        </p>
    	<?php
	}

	/**
	 * Save the default member type cover image
	 *
	 * @param int $post_id numeric post id of the post containing member type details.
	 */
	public function save_details( $post_id ) {
		//verify nonce
		if ( ! wp_verify_nonce( $_POST['_buddypress-member-types-pro-nonce'], 'buddypress-member-types-pro-edit-member-type' ) ) {
			return;
		}


		$cover_image_url = isset( $_POST['_bp_member_type_associated_cover_image_url'] ) ? $_POST['_bp_member_type_associated_cover_image_url'] : '';

		if ( $cover_image_url ) {
			update_post_meta( $post_id, '_bp_member_type_associated_cover_image_url', $cover_image_url );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_associated_cover_image_url' );
		}
	}

	/**
	 * Load Js
	 */
	public function load_js() {

		wp_register_script(
			'bpmtp-admin-cover-image-helper',
			bpmtp_member_types_pro()->get_url() . 'admin/assets/js/bpmtp-admin-cover-image-helper.js',
			array( 'jquery' )
		);

	}

	/**
	 * Modify cover image url based on member type default
	 *
	 * @param array $settings Cover image settings.
	 *
	 * @return array
	 */
	public function modify_cover_image_url( $settings = array() ) {

		if ( ! bpmtp_get_option( 'override_member_cover_image', 0 ) || ! bp_is_user() ) {
			return $settings;
		}

		$user_id              = bp_displayed_user_id();
		$user_member_types    = bp_get_member_type( $user_id, false );

		if (  empty( $user_member_types ) ) {
			return $settings;
		}

		$active_member_types = bpmtp_get_active_member_type_entries();

		$active_member_types_associated_cover_images = wp_list_pluck( $active_member_types, 'associated_cover_image_url', 'member_type' );

		foreach ( $user_member_types as $member_type ) {

			if ( empty( $active_member_types_associated_cover_images[ $member_type ] ) ) {
				continue;
			}

			$cover_image = $active_member_types_associated_cover_images[ $member_type ];

			$settings['default_cover'] = apply_filters( 'bpmtp_member_type_cover_image_url', $cover_image, $member_type, $user_member_types, $active_member_types_associated_cover_images );
			break;
		}

		// Removed code here.
		return $settings;
	}
}

new BPMTP_Cover_Image_Helper();
