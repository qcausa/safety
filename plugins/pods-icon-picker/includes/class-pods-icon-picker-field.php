<?php
class Pods_Icon_Picker_Field {

    public function __construct() {
        // Register custom field type
        add_filter( 'pods_field_types', [ $this, 'register_field_type' ] );

        // Load assets for the admin
        add_action( 'admin_enqueue_scripts', [ $this, 'load_assets' ] );

        // Render the custom field
        add_filter( 'pods_form_field_icon_picker', [ $this, 'render_icon_picker_field' ], 10, 6 );
    }

    // Register the new field type
    public function register_field_type( $types ) {
        $types['icon_picker'] = __( 'Icon Picker', 'pods-icon-picker' );
        return $types;
    }

    // Enqueue necessary scripts and styles
    public function load_assets( $hook ) {
        // Load only in the admin area for Pods
        if ( strpos( $hook, 'pods' ) !== false ) {
            // Font Awesome CSS for icons
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' );

            // Custom JavaScript for the icon picker preview
            wp_enqueue_script( 'icon-picker-script', plugin_dir_url( __FILE__ ) . 'js/icon-picker.js', [ 'jquery' ], '1.0', true );

            // Custom styles
            wp_enqueue_style( 'icon-picker-style', plugin_dir_url( __FILE__ ) . 'css/icon-picker.css', [], '1.0' );
        }
    }

    // Render the custom field input
    public function render_icon_picker_field( $output, $name, $value, $options, $pod, $id ) {
        ob_start();
        ?>
        <div class="icon-picker-wrapper">
            <input type="text" 
                   id="<?php echo esc_attr( $name ); ?>" 
                   name="<?php echo esc_attr( $name ); ?>" 
                   value="<?php echo esc_attr( $value ); ?>" 
                   class="icon-picker-field" 
                   placeholder="Enter an icon class (e.g., fa-solid fa-home)">
            <span class="icon-preview">
                <i class="<?php echo esc_attr( $value ); ?>"></i>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }
}
