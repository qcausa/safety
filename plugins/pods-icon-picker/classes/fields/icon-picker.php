<?php
/**
 * @package Pods\Fields
 */
class PodsField_Icon_Picker extends PodsField {

    /**
     * {@inheritdoc}
     */
    public static $group = 'Media';

    /**
     * {@inheritdoc}
     */
    public static $type = 'icon_picker';

    /**
     * {@inheritdoc}
     */
    public static $label = 'Icon Picker';

    /**
     * {@inheritdoc}
     */
    public static $prepare = '%s';

    /**
     * Setup for field group and label.
     */
    public function setup() {
        static::$group = __( 'Media', 'pods' );
        static::$label = __( 'Icon Picker', 'pods' );

        // Enqueue assets
        //add_action( 'admin_enqueue_scripts', [ $this, 'load_assets' ] );
    }

    /**
     * Field options for configuration.
     */
    public function options() {
        $options = array(
            static::$type . '_placeholder' => array(
                'label'   => __( 'Placeholder', 'pods' ),
                'default' => __( 'Click to select an icon', 'pods' ),
                'type'    => 'text',
            ),
            static::$type . '_icon_library' => array(
                'label'   => __( 'Icon Library', 'pods' ),
                'type'    => 'pick',
                'data'    => array(
                    'FontAwesome' => 'Font Awesome',
                    'Dashicons'   => 'WordPress Dashicons',
                    'Custom'      => __( 'Custom Classes', 'pods' ),
                ),
                'default' => 'FontAwesome',
            ),
        );

        return $options;
    }

    /**
     * Database schema for this field.
     *
     * @param mixed $options Field options.
     * @return string
     */
    public function schema( $options = null ) {
        return 'VARCHAR(255)';
    }

    /**
     * Load Font Awesome Icon Picker assets.
     *
     * @param string $hook The current page hook.
     */
    // public function load_assets( $hook ) {
	// 	BugFu::log("load_assets");
    //     // Load assets only on Pods admin pages
    //     if ( strpos( $hook, 'pods' ) !== false ) {
	// 		BugFu::log("load_assets: in if");
    //         // Font Awesome Picker CSS & JS
    //         wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' );
    //         wp_enqueue_style( 'fontawesome-picker', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css' );

    //         wp_enqueue_script( 'fontawesome-picker', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js', [ 'jquery' ], null, true );

    //         // Custom script for initializing the picker
    //         wp_enqueue_script( 'pods-icon-picker-init', plugin_dir_url( __FILE__ ) . '../../js/icon-picker-init.js', [ 'jquery', 'fontawesome-picker' ], null, true );
    //     }
    // }

    /**
     * Render the input for the field.
     *
     * @param string $name Field name.
     * @param mixed  $value Field value.
     * @param mixed  $options Field options.
     * @param mixed  $pod Pod object.
     * @param int    $id Pod ID.
     */
    public function input( $name, $value = null, $options = null, $pod = null, $id = null ) {
        // Get placeholder text
        $placeholder = pods_v( static::$type . '_placeholder', $options, __( 'Click to select an icon', 'pods' ) );

        // Render the input field with icon picker
        ?>
        <div class="icon-picker-wrapper">
            <input type="text"
                   id="<?php echo esc_attr( $name ); ?>"
                   name="<?php echo esc_attr( $name ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                   class="icon-picker-input"
                   placeholder="<?php echo esc_attr( $placeholder ); ?>" />
            <span class="icon-preview">
                <i class="<?php echo esc_attr( $value ); ?>"></i>
            </span>
        </div>
        <?php

		 // Enqueue assets for Font Awesome Picker
		 wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' );
		 wp_enqueue_style( 'fontawesome-picker', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css' );

		 wp_enqueue_script( 'fontawesome-picker', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js', [ 'jquery' ], null, true );
		 BUgFu::log(PODS_ICON_URL);
		 wp_enqueue_script( 'pods-icon-picker-script', PODS_ICON_URL . 'js/icon-picker.js', [ 'jquery', 'fontawesome-picker' ], null, true );
    }

    /**
     * Validate the field value.
     *
     * @param mixed $value Field value.
     * @param string $name Field name.
     * @param mixed $options Field options.
     * @param mixed $fields Fields data.
     * @param mixed $pod Pod object.
     * @param int $id Pod ID.
     * @param mixed $params Additional parameters.
     * @return bool|string
     */
    public function validate( $value, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null ) {
        if ( empty( $value ) && $this->is_required( $options ) ) {
            return sprintf( __( 'The %s field is required.', 'pods' ), $name );
        }

        return true;
    }
}
