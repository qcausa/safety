<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class for Edit Member Type screen
 */
class BPMTP_Member_Types_Pro_Admin_Edit_Screen_Helper {

	/**
	 * Singleton instance
	 *
	 * @var BPMTP_Member_Types_Pro_Admin_Edit_Screen_Helper
	 */
	private static $instance = null;

	/**
	 * Member type Post type name.
	 *
	 * @var string
	 */
	private $post_type = '';

	/**
	 * Constructor
	 */
	private function __construct() {

		$this->post_type = bpmtp_get_post_type();
		$this->setup();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return BPMTP_Member_Types_Pro_Admin_Edit_Screen_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup
	 */
	private function setup() {
		// save post.
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ), 1 );
		add_filter( 'post_updated_messages', array( $this, 'filter_update_messages' ) );
	}

	/**
	 * Register meta boxes
	 */
	public function register_metabox() {

		add_meta_box( 'bp-member-type-box', __( 'Member type details', 'buddypress-member-types-pro' ), array(
			$this,
			'member_type_info_metabox',
		), $this->post_type );

		add_meta_box( 'bp-member-type-box-status', __( 'Member Type Status', 'buddypress-member-types-pro' ), array(
			$this,
			'status_metabox',
		), $this->post_type, 'side', 'high' );


		add_meta_box( 'bp-member-type-box-redirects', __( 'Redirection setting', 'buddypress-member-types-pro' ), array(
			$this,
			'redirect_metabox',
		), $this->post_type );
	}

	/**
	 * Collect member type details
	 *
	 * @param WP_Post $post Post object.
	 */
	public function member_type_info_metabox( $post ) {

		$meta = get_post_custom( $post->ID );

		$name = isset( $meta['_bp_member_type_name'] ) ? $meta['_bp_member_type_name'][0] : '';

		$label_name          = isset( $meta['_bp_member_type_label_name'] ) ? $meta['_bp_member_type_label_name'][0] : '';
		$label_singular_name = isset( $meta['_bp_member_type_label_singular_name'] ) ? $meta['_bp_member_type_label_singular_name'][0] : '';

		// enabled by default.
		$enable_directory = isset( $meta['_bp_member_type_enable_directory'] ) ? $meta['_bp_member_type_enable_directory'][0] : 1;

		$directory_slug = isset( $meta['_bp_member_type_directory_slug'] ) ? $meta['_bp_member_type_directory_slug'][0] : '';

		?>
        <div id="buddypress-member-types-pro-form-general" class="buddypress-member-types-pro-form">
            <label>
                <span><?php _e( '<span>Member Type Name:</span>(unique slug, used to identify the member type, It is also called name, e.g student, staff, teacher etc):', 'buddypress-member-types-pro' ); ?></span>
                <input type="text" name="bp-member-type[name]" placeholder="Unique key to identify this member type"
                       value="<?php echo esc_attr( $name ); ?>"/>
            </label>

            <p class='buddypress-member-types-pro-help'> <?php _e( 'Plugins will use this <strong>member type name </strong> to identify the member type. Please avoid changing it. If you change the unique name, you will lose the information about members having this member type.', 'buddypress-member-types-pro' ); ?>
            <br />
	            <?php _e( '<strong>Note:</strong> Use lowercases. Avoid using dash/hyphen(-), white space to avoid issues on directory listing.', 'buddypress-member-types-pro' ); ?>
            </p>


            <label>
                <span> <span><?php _e( 'Plural Label:', 'buddypress-member-types-pro' ); ?></span></span>
                <input type="text" name="bp-member-type[label_name]"
                       placeholder="<?php _e( 'Plural name e.g. Students', 'buddypress-member-types-pro' ); ?>"
                       value="<?php echo esc_attr( $label_name ); ?>"/>
            </label>

            <label>
                <span> <span><?php _e( 'Singular Label:', 'buddypress-member-types-pro' ); ?></span></span>
                <input type="text" name="bp-member-type[label_singular_name]"
                       placeholder="<?php _e( 'Singular name, e.g. Student', 'buddypress-member-types-pro' ); ?>"
                       value="<?php echo esc_attr( $label_singular_name ); ?>"/>
            </label>

            <p>
                <label>
                    <input type='checkbox' name='bp-member-type[enable_directory]'
                           value='1' <?php checked( $enable_directory, 1 ); ?> />
                    <strong><?php _e( 'Enable Directory?', 'buddypress-member-types-pro' ); ?></strong>
                </label>
            </p>
            <p class='buddypress-member-types-pro-help'><?php _e( 'By enabling directory, you can see a list of all members having this member type by appending member type name or directory slug(if specified).Only applies to BuddyPress 2.3+)', 'buddypress-member-types-pro' ); ?></p>

            <p>
				<span> 
					<strong><?php _e( 'Directory Slug:', 'buddypress-member-types-pro' ); ?></strong>
				</span>
                <input type='text' name='bp-member-type[directory_slug]' value='<?php echo $directory_slug; ?>'/>
            </p>
            <p class='buddypress-member-types-pro-help'><?php _e( 'If you have enabled directory, It will be used to append to your memeber directory url to list all members having this member type( Only applies to BuddyPress 2.3+)', 'buddypress-member-types-pro' ); ?></p>

        </div>
		<?php wp_nonce_field( 'buddypress-member-types-pro-edit-member-type', '_buddypress-member-types-pro-nonce' ); ?>
		<?php //adding css below as we only need little code and loading a separate css file does not seem a good fit here ?>
        <style type="text/css">
            .buddypress-member-types-pro-form {

            }

            .buddypress-member-types-pro-form label {
                display: block;
                margin-top: 20px;
            }

            .buddypress-member-types-pro-form span {
                display: block;
            }

            .buddypress-member-types-pro-form span span,
            .buddypress-member-types-pro-form strong {
                font-weight: bold;

            }

            .buddypress-member-types-pro-form input[type='text'] {
                display: block;
                min-width: 420px;
                padding: 10px;
                font-weight: bold;
                color: #666;
            }

            p.buddypress-member-types-pro-help {
                background: #F5F5DC;
                padding: 5px;
                margin-top: 5px;
                color: #737373;
            }

            .buddypress-member-types-pro-form input[type='text']::-webkit-input-placeholder { /* Chrome/Opera/Safari */
                font-weight: normal;
                color: #999;
            }
            .buddypress-member-types-pro-form input[type='text']::-moz-placeholder { /* Firefox 19+ */
                font-weight: normal;
                color: #999;
            }
            .buddypress-member-types-pro-form input[type='text']:-ms-input-placeholder { /* IE 10+ */
                font-weight: normal;
                color: #999;
            }
            .buddypress-member-types-pro-form input[type='text']:-moz-placeholder { /* Firefox 18- */
                font-weight: normal;
                color: #999;
            }

            #bp-member-type-avatar .bpmtp-associated-avatar-upload-button {
                margin-bottom: 20px;
            }
            #bp-member-type-avatar p {
                text-align: center;
            }
            #bp-member-type-avatar img{
                max-width: 100%;
            }
            #bp-member-type-avatar p.bpmtp-avatar-status {
                text-align: left;
            }

            #bp-member-type-cover-image .bpmtp-associated-cover-image-upload-button {
                margin-bottom: 20px;
            }
            #bp-member-type-cover-image p {
                text-align: center;
            }
            #bp-member-type-cover-image img{
                max-width: 100%;
            }
            #bp-member-type-cover-image p.bpmtp-cover-image-status {
                text-align: left;
            }
        </style>
		<?php
	}

	/**
	 * Generate Member Type status Meta box
	 *
	 * @param WP_Post $post
	 */
	public function status_metabox( $post ) {

		$meta      = get_post_custom( $post->ID );
		$is_active = isset( $meta['_bp_member_type_is_active'] ) ? $meta['_bp_member_type_is_active'][0] : 1;
		$is_member_excluded_from_dir = isset( $meta['_bp_member_type_is_member_excluded'] ) ? $meta['_bp_member_type_is_member_excluded'][0] : 0;
		$is_dir_tab_listable = isset( $meta['_bp_member_type_is_dir_tab_listable'] ) ? $meta['_bp_member_type_is_dir_tab_listable'][0] : 1;

		?>
        <p>
            <label><input type='checkbox' name='bp-member-type[is_active]'
                         value='1' <?php checked( $is_active, 1 ); ?> ><?php _e( 'Is active?', 'buddypress-member-types-pro' ); ?>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'Only active member types will be registered. You can set a member type to inactive to disable it.', 'buddypress-member-types-pro' ); ?></p>

        <p>
            <label><input type='checkbox' name='bp-member-type[is_dir_tab_listable]'
                         value='1' <?php checked( $is_dir_tab_listable, 1 ); ?> ><?php _e( 'List in directory?', 'buddypress-member-types-pro' ); ?>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'A New tab will be added in directory with the member type.', 'buddypress-member-types-pro' ); ?></p>

        <p>
            <label><input type='checkbox' name='bp-member-type[is_member_excluded_from_dir]'
                          value='1' <?php checked( $is_member_excluded_from_dir, 1 ); ?> ><?php _e( 'Exclude members with this type from directory?', 'buddypress-member-types-pro' ); ?>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'The members having this type will be excluded from directory listing.', 'buddypress-member-types-pro' ); ?></p>

		<?php

	}

	/**
	 * Generate Member Type redirect meta box.
	 *
	 * @param WP_Post $post
	 */
	public function redirect_metabox( $post ) {

		$meta                  = get_post_custom( $post->ID );
		$login_redirect_url    = isset( $meta['_bp_member_type_login_redirect_url'] ) ? $meta['_bp_member_type_login_redirect_url'][0] : '';
		$first_login_redirect_url    = isset( $meta['_bp_member_type_first_login_redirect_url'] ) ? $meta['_bp_member_type_first_login_redirect_url'][0] : '';
		$activation_redirect_url = isset( $meta['_bp_member_type_activation_redirect_url'] ) ? $meta['_bp_member_type_activation_redirect_url'][0] : '';

		$default_tab_criteria = isset( $meta['_bp_member_type_profile_tab_criteria'] ) ? $meta['_bp_member_type_profile_tab_criteria'][0] : '';
		$default_tab_slug     = isset( $meta['_bp_member_type_default_profile_tab_slug'] ) ? $meta['_bp_member_type_default_profile_tab_slug'][0] : '';
        $url = "https://buddydev.com/docs/buddypress-member-types-pro/conditional-login-registration-redirect-buddypress-member-types/";
		?>
        <div id="buddypress-member-types-pro-form-redirect" class="buddypress-member-types-pro-form">
            <p class='buddypress-member-types-pro-help'>
                <?php _e( 'BuddyPress Member Types Pro allows you to setup dynamic login, account activation redirect.', 'buddypress-member-types-pro' ); ?>
            <br />
	            <?php _e( 'You can use tokens such as [site_url], [user_profile_url] etc in the url.', 'buddypress-member-types-pro' ); ?>
	            <?php printf( __( 'For more details, please <a href="%s">view documentation</a>.', 'buddypress-member-types-pro' ), $url ); ?>

            </p>

            <p>
            <label for="_bp_member_type_first_login_redirect_url">
                <span><?php _e( 'On First Login, Redirect to:', 'buddypress-member-types-pro');?></span>
                <input type='text' name='bp-member-type[first_login_redirect_url]' id="_bp_member_type_first_login_redirect_url" value='<?php echo $first_login_redirect_url; ?>' />
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'If you specify a url, the users belonging to this member will be redirected on their first login to the above url.', 'buddypress-member-types-pro' ); ?></p>

            <p>
            <label for="_bp_member_type_login_redirect_url">
                <span><?php _e( 'On Login, Redirect to:', 'buddypress-member-types-pro');?></span>
                <input type='text' name='bp-member-type[login_redirect_url]' id="_bp_member_type_login_redirect_url" value='<?php echo $login_redirect_url; ?>' />
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'If you specify a url, the users belonging to this member will be redirected on their login to the above url. If first login redirect is setup above, That will be used for the very first login of user.', 'buddypress-member-types-pro' ); ?></p>

        <p>
            <label for="_bp_member_type_register_redirect_url">
                <span><?php _e( 'On Account activation, Redirect to:', 'buddypress-member-types-pro');?></span>
                <input type='text' id="_bp_member_type_activation_redirect_url" name='bp-member-type[activation_redirect_url]' value='<?php echo $activation_redirect_url; ?>' />
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'If you specify a url, the users belonging to this member will be redirected on their login to the above url.', 'buddypress-member-types-pro' ); ?></p>

        <p>
            <label for="_bp_member_type_default_tab_preference">
                <span><?php _e( 'Set default landing tab for user profile:', 'buddypress-member-types-pro');?></span>
                <select id="_bp_member_type_default_tab_preference" name='bp-member-type[default_profile_tab_criteria]'>
                    <option value="" <?php selected( $default_tab_criteria, '', true );?>><?php _e( 'No', 'buddypress-member-types-pro' );?></option>
                    <option value="loggedin" <?php selected( $default_tab_criteria, 'loggedin', true );?>><?php _e( "Yes, if visitor has this member type", 'buddypress-member-types-pro' );?></option>
                    <option value="displayed" <?php selected( $default_tab_criteria, 'displayed', true );?>><?php _e( "Yes, if displayed user has this member type", 'buddypress-member-types-pro' );?></option>
                    <option value="flexible" <?php selected( $default_tab_criteria, 'flexible', true );?>><?php _e( "Yes, If user is logged  and they have this member type, If not logged and displayed user has this member type", 'buddypress-member-types-pro' );?></option>
                </select>
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'Select the criteria for setting default tab when visiting the profile.', 'buddypress-member-types-pro' ); ?></p>

        <p>
            <label for="_bp_member_type_default_profile_tab_slug">
                <span><?php _e( 'Default profile tab slug:', 'buddypress-member-types-pro');?></span>
                <input type='text' id="_bp_member_type_default_profile_tab_slug" name='bp-member-type[default_profile_tab_slug]' value='<?php echo esc_attr( $default_tab_slug );?>' />
            </label>
        </p>
        <p class='buddypress-member-types-pro-help'> <?php _e( 'If you specify a url, the users belonging to this member will be redirected on their login to the above url.', 'buddypress-member-types-pro' ); ?></p>
        </div>
		<?php

	}

	/**
	 * Save all data as post meta
	 *
	 * @param int $post_id
	 *
	 * @return null
	 */
	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post = get_post( $post_id );

		if ( $post->post_type != $this->post_type ) {
			return;
		}

		if ( ! isset( $_POST['_buddypress-member-types-pro-nonce'] ) ) {
			return;//most probably the new member type screen
		}

		//verify nonce
		if ( ! wp_verify_nonce( $_POST['_buddypress-member-types-pro-nonce'], 'buddypress-member-types-pro-edit-member-type' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}


		//save data

		$data = isset( $_POST['bp-member-type'] ) ? $_POST['bp-member-type'] : array();

		if ( empty( $data ) ) {
			return;
		}

		$post_title = wp_kses( $_POST['post_title'], wp_kses_allowed_html( 'strip' ) );
		//for unique id
		$name = isset( $data['name'] ) ? sanitize_key( $data['name'] ) : sanitize_key( $post_title );
		//for label
		$label_name    = isset( $data['label_name'] ) ? wp_kses( $data['label_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;
		$singular_name = isset( $data['label_singular_name'] ) ? wp_kses( $data['label_singular_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;

		$is_active = isset( $data['is_active'] ) ? absint( $data['is_active'] ) : 0;//default inactive

		$enable_directory = isset( $data['enable_directory'] ) ? absint( $data['enable_directory'] ) : 0;//default inactive
		$directory_slug   = isset( $data['directory_slug'] ) ? sanitize_key( $data['directory_slug'] ) : '';//default inactive

        $dir_listable = isset( $data['is_dir_tab_listable'] ) ? absint( $data['is_dir_tab_listable'] ) : 0;
        $member_excluded_from_dir = isset( $data['is_member_excluded_from_dir'] ) ? absint( $data['is_member_excluded_from_dir'] ) : 0;


        update_post_meta( $post_id, '_bp_member_type_is_dir_tab_listable', $dir_listable );
        update_post_meta( $post_id, '_bp_member_type_is_member_excluded', $member_excluded_from_dir );

		update_post_meta( $post_id, '_bp_member_type_is_active', $is_active );

		update_post_meta( $post_id, '_bp_member_type_name', $name );
		update_post_meta( $post_id, '_bp_member_type_label_name', $label_name );
		update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_name );

		update_post_meta( $post_id, '_bp_member_type_enable_directory', $enable_directory );

		//for directory slug

		if ( $directory_slug ) {
			update_post_meta( $post_id, '_bp_member_type_directory_slug', $directory_slug );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_directory_slug' );
		}

		// redirection settings.
		$login_redirect_url = isset( $data['login_redirect_url'] ) ? sanitize_text_field( trim( $data['login_redirect_url'] ) ) : '';
		update_post_meta( $post_id, '_bp_member_type_login_redirect_url', $login_redirect_url );

		$first_login_redirect_url = isset( $data['first_login_redirect_url'] ) ? sanitize_text_field( trim( $data['first_login_redirect_url'] ) ) : '';
		update_post_meta( $post_id, '_bp_member_type_first_login_redirect_url', $first_login_redirect_url );

		$activation_redirect_url = isset( $data['activation_redirect_url'] ) ? sanitize_text_field( trim( $data['activation_redirect_url'] ) ) : '';
		update_post_meta( $post_id, '_bp_member_type_activation_redirect_url', $activation_redirect_url );

		// update tab preference
        $tab_pref = isset( $data['default_profile_tab_criteria'] ) ? trim( $data['default_profile_tab_criteria'] ) : '';
        // validate.
        if ( $tab_pref && ! in_array( $tab_pref, array('loggedin', 'displayed', 'flexible' ) ) ) {
            $tab_pref = '';
        }

		update_post_meta( $post_id, '_bp_member_type_profile_tab_criteria', $tab_pref );

		$default_profile_tab_slug = isset( $data['default_profile_tab_slug'] ) ? sanitize_text_field( trim( $data['default_profile_tab_slug'] ) ) : '';
		update_post_meta( $post_id, '_bp_member_type_default_profile_tab_slug', $default_profile_tab_slug );

		do_action( 'buddypress_member_types_pro_details_saved', $post_id );
	}

	public function filter_update_messages( $messages ) {

		global $post, $post_ID;

		$update_message = $messages['post'];//make a copy of the post update message

		$update_message[1] = sprintf( __( 'Member type updated.', 'buddypress-member-types-pro' ) );

		$update_message[4] = __( 'Member type updated.', 'buddypress-member-types-pro' );

		$update_message[6] = sprintf( __( 'Member type published. ', 'buddypress-member-types-pro' ) );

		$update_message[7] = __( 'Member type  saved.', 'buddypress-member-types-pro' );

		$messages[ $this->post_type ] = $update_message;

		return $messages;
	}

}

BPMTP_Member_Types_Pro_Admin_Edit_Screen_Helper::get_instance();
