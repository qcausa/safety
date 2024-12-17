<?php
// Don't allow direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Implementing member type as select field
 *  Originally based on our Bp Xprofile Member Type field plugin.
 */
class BPMTP_XProfile_Field_Type_MemberTypes extends BP_XProfile_Field_Type {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress-member-types-pro' );
		$this->name     = _x( 'Multi Member Type', 'xprofile field type', 'buddypress-member-types-pro' );

		$this->set_format( '', 'replace' );

		$this->supports_multiple_defaults = false;
		$this->accepts_null_value         = true;
		$this->supports_options           = false;

		do_action( 'bpmtp_xprofile_field_type_member_types', $this );
	}

	/**
	 * Are they valid member types?
	 *
	 * @param mixed $val field value to test.
	 *
	 * @return boolean
	 */
	public function is_valid( $val ) {

		if ( is_array( $val ) ) {
			$val = array_filter( $val );
		}
		// if a registered member type, mark as valid.
		if ( empty( $val )  ) {
			return true;
		}

		$val = (array) maybe_unserialize( $val );

		foreach ( $val as $member_type ) {
			if ( ! bp_get_member_type_object( $member_type ) ) {
				return false;
			}
		}
		return true;

	}

	/**
	 * Format member type value for  display.
	 *
	 * @param string $field_value The member type name(key) value, as saved in the database.
	 *
	 * @return string the member type label
	 */
	public static function display_filter( $field_value, $field_id = '' ) {

		if ( empty( $field_value ) ) {
			return $field_value;
		}

		// the field value given to us is comma separated list.
		$field_value = array_map( 'trim', explode( ',', $field_value ) );

		$member_types = self::get_member_types();

		$value = array();
		foreach ( $field_value as $member_type ) {
			if ( isset( $member_types[ $member_type ] ) ) {
				$value [] = bpmtp_filter_member_type_field_display_data( $member_type, $member_types[ $member_type ], $field_id );
			}
		}

		return join( ', ', $value );

	}

	/**
	 * Admin->Users->Profile Fields.
	 *
	 * @param array $raw_properties
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$this->edit_field_html();
	}

	/**
	 * Admin->Users->profile fields->Add/Edit.
	 *
	 * @param BP_XProfile_Field $current_field
	 * @param string $control_type
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( false === $type ) {
			return;
		}

		$field_display_type = $field_restriction = $selected_types = $allow_edit = $default = $add_dir_link = '';

		$class            = $current_field->type == $type ? '' : 'display: none;';
		$id = $current_field->id;

		if ( $id ) {
			$field_display_type = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_display_type', true );
			$field_restriction  = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_restriction', true );
			$selected_types     = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_selected_types', true );
			$allow_edit         = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_allow_edit', true );
			$default            = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_default_value', true );
			$add_dir_link       = bp_xprofile_get_meta( $id, 'field', 'bpmtp_field_link_to_dir', true );
		}

		if ( ! $field_display_type ) {
			$field_display_type = 'selectbox';
		}
		if ( ! $field_restriction ) {
			$field_restriction = 'all';
		}

		if ( ! $selected_types ) {
			$selected_types = array();
		}

		if ( '' === $allow_edit ) {
			$allow_edit = 1;
		}

		// convert to number.
		if ( ! $add_dir_link ) {
			$add_dir_link = 0;
		}


		$css_class_list_visibility = 'all' === $field_restriction ? 'bpmtp-admin-hidden' : '';
		?>

        <div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box"
             style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">

            <div class="inside">
				<?php // field display type. ?>
                <div class="bpmtp-multi-field-display-type">

                    <p>
                        <strong><?php _e( 'Show field as', 'buddypress-member-types-pro' ); ?></strong>
                        <label for="bpmtp-multi-field-display-type-checkbox">
                            <input type="radio" value="checkbox" name="bpmtp-multi-field-display-type" id="bpmtp-multi-field-display-type-checkbox" <?php checked( $field_display_type, 'checkbox', true );?>><?php _e( 'Checkbox', 'buddypress-member-types-pro' ); ?>
                        </label>
                        <label for="bpmtp-multi-field-display-type-selectbox">
                            <input type="radio" value="selectbox" name="bpmtp-multi-field-display-type" id="bpmtp-multi-field-display-type-selectbox" <?php checked( $field_display_type, 'selectbox', true );?>><?php _e( 'Multi Selectbox', 'buddypress-member-types-pro' ); ?>
                        </label>
                    </p>

                    <p>
                        <strong><?php _e( 'Which member types to list in field option?', 'bpmtp-field-display-type-radio' ); ?></strong>
                        <label>
                            <input class="bpmtp-multi-member-type-list-restriction" type="radio" name="bpmtp-multi-field-restriction" value="all" <?php checked( $field_restriction, 'all', true );?>/><?php _e( 'All', 'buddypress-member-types-pro' ); ?>
                        </label>
                        <label>
                            <input class="bpmtp-multi-member-type-list-restriction" type="radio" name="bpmtp-multi-field-restriction" value="restricted" <?php checked( $field_restriction, 'restricted', true );?>/> <?php _e( 'Only selected', 'buddypress-member-types-pro' );?><br/>
                        </label>
                    </p>
                    <ul id="bpmtp-multi-selected-member-type" class="bpmtp-admin-visible <?php echo $css_class_list_visibility;?>">
						<?php $member_types = self::get_member_types(); ?>
						<?php foreach ( $member_types as $member_type => $label ) : ?>
                            <li>
                                <label>
                                    <input type="checkbox" value="<?php echo $member_type; ?>" name="bpmtp-multi-field-selected-types[]" <?php checked(true, in_array( $member_type, $selected_types ), true );?>><?php echo $label; ?>
                                </label>
                            </li>
						<?php endforeach; ?>

                    </ul>

                    <p>
                        <strong><?php _e( 'Link the profile displayed data to member type directory?', 'buddypress-member-types-pro' ); ?></strong>
                        <label><input name="bpmtp-multi-field-link-to-dir" value="1" type="radio" <?php checked( $add_dir_link, 1, true );?>><?php _e( 'Yes', 'buddypress-member-types-pro' ); ?></label>
                        <label><input name="bpmtp-multi-field-link-to-dir" value="0" type="radio" <?php checked( $add_dir_link, 0, true );?>><?php _e( 'No', 'buddypress-member-types-pro' ); ?></label>
                    </p>

                    <p>
                        <strong><?php _e( 'Allow users to change their member type after registration?', 'buddypress-member-types-pro' ); ?></strong>
                        <label><input name="bpmtp-multi-field-allow-edit" value="1" type="radio" <?php checked( $allow_edit, 1, true );?>><?php _e( 'Yes', 'buddypress-member-types-pro' ); ?></label>
                        <label><input name="bpmtp-multi-field-allow-edit" value="0" type="radio" <?php checked( $allow_edit, 0, true );?>><?php _e( 'No', 'buddypress-member-types-pro' ); ?></label>
                    </p>

                    <p>
                        <label for="bpmtp-multi-field-default-value"><strong><?php _e( 'Default value?', 'buddypress-member-types-pro' ); ?></strong></label>
                        <select name="bpmtp-multi-field-default-value">
                            <option value="" <?php selected( $default, '' );?>><?php _e( 'None', 'buddypress-member-types-pro' );?></option>

			                <?php foreach ( $member_types as $name => $label ) : ?>
                                <option value="<?php echo esc_attr($name);?>" <?php selected( $default, $name );?>><?php echo $label;?></option>
			                <?php endforeach;?>
                        </select>
                    </p>
                </div>
				<?php // show all types or limit to the given types
				?>
				<?php // allow user to change their member type ?>
                <style type="text/css">
                    .bpmtp-admin-hidden{
                        display: none;
                    }
                </style>
            </div>
        </div>
		<?php
	}

	/**
	 * Edit field html.
	 *
	 * @param array $raw_properties array of attributes.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		$this->_edit_field_html( $raw_properties );
		bpmtp_member_types_pro()->set_shown( bp_get_the_profile_field_id() );
	}

	/**
	 * Display as select box
	 *
	 * @param array $raw_properties array of a
	 */
	public function _edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		}

		$display_type = $display_type = bp_xprofile_get_meta( bp_get_the_profile_field_id(), 'field', 'bpmtp_field_display_type', true );
		if ( 'checkbox' === $display_type ) {
			$this->_element_html_checkbox( $raw_properties, $user_id );
		} else {
			$this->_element_html( $raw_properties, $user_id );
		}

	}

	protected function _element_html_checkbox( $raw_properties = array(), $user_id = null ) {
		?>
        <legend>
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
        </legend>

		<?php if ( bp_get_the_profile_field_description() && ! $this->is_admin_field_list() ) : ?>
            <p class="description" tabindex="0"><?php bp_the_profile_field_description(); ?></p>
		<?php endif; ?>

        <div class="checkbox">

			<?php

			/** This action is documented in bp-xprofile/bp-xprofile-classes */
			do_action( bp_get_the_profile_field_errors_action() ); ?>

			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) );
			?>

        </div>
		<?php
	}

	protected function _element_html( $raw_properties = array(), $user_id = null ) {
		?>
        <legend id="<?php bp_the_profile_field_input_name(); ?>-1">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
        </legend>

		<?php
		$r = bp_parse_args( $raw_properties, array(
			'multiple' => 'multiple',
			'id'       => bp_get_the_profile_field_input_name() . '[]',
			'name'     => bp_get_the_profile_field_input_name() . '[]',
		) );

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

        <select <?php echo $this->get_edit_field_html_elements( $r ); ?>>
			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) ); ?>
        </select>

		<?php if ( bp_get_the_profile_field_description() && ! $this->is_admin_field_list() ) : ?>
            <p class="description" id="<?php bp_the_profile_field_input_name(); ?>-3"><?php bp_the_profile_field_description(); ?></p>
		<?php endif; ?>

		<?php if ( ! bp_get_the_profile_field_is_required() ) : ?>

            <a class="clear-value" href="javascript:clear( '<?php echo esc_js( bp_get_the_profile_field_input_name() ); ?>[]' );">
				<?php esc_html_e( 'Clear', 'buddypress-member-types-pro' ); ?>
            </a>

		<?php endif; ?>
		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {

		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		$default = bp_xprofile_get_meta( $this->field_obj->id, 'field', 'bpmtp_field_default_value', true );
		if ( ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			$option_values = (array) $_POST[ 'field_' . $this->field_obj->id ];
			$option_values = array_map( 'sanitize_text_field', $option_values );
		} else {
			if ( $original_option_values === '' && $default ) {
				$option_values = (array) $default;
			} else {
				$option_values = (array) $original_option_values;
			}
		}

		$display_type = bp_xprofile_get_meta( $this->field_obj->id, 'field', 'bpmtp_field_display_type', true );
		// member types list as array.
		$options = self::get_member_types();
		$restriction = bp_xprofile_get_meta( $this->field_obj->id, 'field', 'bpmtp_field_restriction', true );

		if ( 'restricted' === $restriction ) {
			$new_options = array();
			$selected_types = bp_xprofile_get_meta( $this->field_obj->id, 'field', 'bpmtp_field_selected_types', true );

			if ( empty( $selected_types ) ) {
				$selected_types = array();
			}

			foreach ( $options as $key => $label ) {

				if ( in_array( $key, $selected_types ) ) {
					$new_options[ $key ] = $label;
				}
			}

			$options = $new_options;
		}

		if ( 'checkbox' === $display_type ) {
			$this->_edit_options_html_checkbox( $option_values, $options );
		} else {
			$this->_edit_options_html_selectbox( $option_values, $options );
		}

	}

	/**
     * Options as checkbox.
     *
	 * @param $option_values
	 * @param $options
	 */
	protected function _edit_options_html_checkbox( $option_values, $options ) {


		foreach ( $options as $member_type => $label ) {

			$selected = '';
			// Run the allowed option name through the before_save filter, so we'll be sure to get a match.
			$allowed_options = xprofile_sanitize_data_value_before_save( $member_type, false, false );
			// First, check to see whether the user-entered value matches.
			if ( in_array( $allowed_options, (array) $option_values ) ) {
				$selected = ' checked="checked"';
			}

			$new_html = sprintf( '<label for="%3$s"><input %1$s type="checkbox" name="%2$s[]" id="%3$s" value="%4$s">%5$s</label>',
				$selected,
				esc_attr( "field_{$this->field_obj->id}" ),
				esc_attr( "option_{$member_type}" ),
				esc_attr( stripslashes( $member_type ) ),
				esc_html( stripslashes( $label ) )
			);

			echo apply_filters( 'bp_get_the_profile_field_options_member_types', $new_html, $member_type, $this->field_obj->id, $selected );

		}

	}

	/**
	 * @param $option_values
	 * @param $options
	 */
	protected function _edit_options_html_selectbox( $option_values, $options ) {
		$selected = '';
		if ( empty( $option_values ) || in_array( 'none', $option_values ) ) {
			$selected = ' selected="selected"';
		}

		$html = '<option value="" ' . $selected . ' >----' . /* translators: no option picked in select box */
		        '</option>';

		echo $html;

		foreach ( $options as $member_type => $label ) {

			$selected = '';
			// Run the allowed option name through the before_save filter, so we'll be sure to get a match.
			$allowed_options = xprofile_sanitize_data_value_before_save( $member_type, false, false );

			// First, check to see whether the user-entered value matches.
			if ( in_array( $allowed_options, (array) $option_values ) ) {
				$selected = ' selected="selected"';
			}

			echo apply_filters( 'bp_get_the_profile_field_options_member_type', '<option' . $selected . ' value="' . esc_attr( stripslashes( $member_type ) ) . '">' . $label . '</option>', $member_type, $this->field_obj->id, $selected );

		}
	}


	/**
	 * Get member types as associative array
	 *
	 * @staticvar array $member_types
	 * @return array
	 */
	private static function get_member_types() {

		static $member_types = null;

		if ( isset( $member_types ) ) {
			return $member_types;
		}

		$registered_member_types = bp_get_member_types( null, 'object' );

		if ( empty( $registered_member_types ) ) {
			$member_types = $registered_member_types;

			return $member_types;
		}

		foreach ( $registered_member_types as $type_name => $member_type_object ) {
			$member_types[ $type_name ] = $member_type_object->labels['singular_name'];
		}

		return apply_filters( 'bp_xprofile_member_type_field_allowed_types', $member_types, $registered_member_types );
	}


	/**
     * Is admin field list?
     *
	 * @return bool
	 */
	private function is_admin_field_list() {
	    return is_admin() && ! defined( 'DOING_AJAX' ) && isset( $_GET['page'] ) && 'bp-profile-setup' == $_GET['page'];
    }
}
