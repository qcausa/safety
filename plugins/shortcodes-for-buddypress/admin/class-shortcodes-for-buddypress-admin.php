<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Shortcodes_For_Buddypress
 * @subpackage Shortcodes_For_Buddypress/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shortcodes_For_Buddypress
 * @subpackage Shortcodes_For_Buddypress/admin
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Shortcodes_For_Buddypress_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	private $plugin_settings_tabs;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook ) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shortcodes_For_Buddypress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shortcodes_For_Buddypress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$shortcode_style = filter_input( INPUT_GET, 'page' ) ? filter_input( INPUT_GET, 'page' ) : '';
		if ( 'wb-plugins_page_shortcodes-for-buddypress' === $hook || 'wbcomplugins' === $shortcode_style || 'wbcom-plugins-page' === $shortcode_style || 'wbcom-support-page' === $shortcode_style ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shortcodes-for-buddypress-admin.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook ) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shortcodes_For_Buddypress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shortcodes_For_Buddypress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( 'settings_page_bpsh-shortcodes' === $hook ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shortcodes-for-buddypress-admin.js', array( 'jquery' ), $this->version, false );
		}

	}

	public function wbcom_hide_all_admin_notices_from_setting_page() {
		$wbcom_pages_array  = array( 'wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'shortcodes-for-buddypress' );
		$wbcom_setting_page = filter_input( INPUT_GET, 'page' ) ? filter_input( INPUT_GET, 'page' ) : '';

		if ( in_array( $wbcom_setting_page, $wbcom_pages_array, true ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Register a submenu to handle group types
	 *
	 * @since    1.0.0
	 */
	public function buddypress_shortcodes_add_submenu_page() {
		if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page( esc_html__( 'WB Plugins', 'shortcodes-for-buddypress' ), esc_html__( 'WB Plugins', 'shortcodes-for-buddypress' ), 'manage_options', 'wbcomplugins', array( $this, 'buddypress_shortcodes_admin_settings_page' ), 'dashicons-lightbulb', 59 );
			add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'shortcodes-for-buddypress' ), esc_html__( 'General', 'shortcodes-for-buddypress' ), 'manage_options', 'wbcomplugins' );
		}
		add_submenu_page( 'wbcomplugins', esc_html__( 'BP Shortcodes', 'shortcodes-for-buddypress' ), esc_html__( 'BP Shortcodes', 'shortcodes-for-buddypress' ), 'manage_options', $this->plugin_name, array( $this, 'buddypress_shortcodes_admin_settings_page' ) );
	}

	/**
	 * Actions performed to create a submenu page content
	 *
	 * @since 2.0.0
	 */
	public function buddypress_shortcodes_admin_settings_page() {
		$tab = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? filter_input( INPUT_GET, 'tab' ) : 'buddypress-shortcode-welcome';
		?>
		<div class="wrap">
			<div class="wbcom-bb-plugins-offer-wrapper">
				<div id="wb_admin_logo">
					<a href="https://wbcomdesigns.com/downloads/buddypress-community-bundle/?utm_source=pluginoffernotice&utm_medium=community_banner" target="_blank">
						<img src="<?php echo esc_url( SHORTCODES_FOR_BUDDYPRESS_PLUGIN_URL ) . 'admin/wbcom/assets/imgs/wbcom-offer-notice.png'; ?>">
					</a>
				</div>
			</div>
			<div class="wbcom-wrap wbcom-plugin-wrapper">
				<div class="bupr-header">
				<div class="wbcom_admin_header-wrapper">
					<div id="wb_admin_plugin_name">
					<?php esc_html_e( 'BuddyPress Shortcodes', 'shortcodes-for-buddypress' ); ?>
						<span>
						<?php
						/* translators: %s: */
						printf( esc_html__( 'Version %s', 'shortcodes-for-buddypress' ), esc_attr( SHORTCODES_FOR_BUDDYPRESS_VERSION ) );
						?>
						</span>
					</div>
				<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
				</div>
				</div>
				<div class="wbcom-admin-settings-page">
			<?php
			$this->buddypress_shortcodes_plugin_settings_tabs();
			settings_fields( $tab );
			do_settings_sections( $tab );
			?>
				</div>
			</div>
		</div>

			<?php
	}

	/**
	 * Actions performed to create tabs on the sub menu page
	 *
	 * @since 1.0.0
	 */
	public function buddypress_shortcodes_plugin_settings_tabs() {
		$current_tab = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? filter_input( INPUT_GET, 'tab' ) : 'buddypress-shortcode-welcome';
		echo '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab === $tab_key ? 'nav-tab-active' : '';
			echo '<li class="' . esc_attr( $tab_key ) . '"><a class="nav-tab ' . esc_attr( $active ) . '" id="' . esc_attr( $tab_key ) . '-tab" href="?page=' . esc_attr( $this->plugin_name ) . '&tab=' . esc_attr( $tab_key ) . '">' . esc_attr( $tab_caption ) . '</a></li>';
		}
		echo '</div></ul></div>';
	}


		/**
		 * Register all settings.
		 *
		 * @since 2.0.0
		 */
	public function buddypress_shortcodes_plugin_settings() {
		$this->plugin_settings_tabs['buddypress-shortcode-welcome'] = esc_html__( 'Welcome', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-welcome', ' ', array( &$this, 'buddypress_shortcodes_welcome_content' ), 'buddypress-shortcode-welcome' );

		$this->plugin_settings_tabs['buddypress-shortcode-general'] = esc_html__( 'General', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-general', ' ', array( &$this, 'buddypress_shortcodes_general_settings_content' ), 'buddypress-shortcode-general' );

		$this->plugin_settings_tabs['buddypress-shortcode-activities'] = esc_html__( 'Activities', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-activities', ' ', array( &$this, 'buddypress_shortcodes_activities_settings_content' ), 'buddypress-shortcode-activities' );

		$this->plugin_settings_tabs['buddypress-shortcode-member'] = esc_html__( 'Members', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-member', ' ', array( &$this, 'buddypress_shortcodes_member_settings_content' ), 'buddypress-shortcode-member' );

		$this->plugin_settings_tabs['buddypress-shortcode-groups'] = esc_html__( 'Groups', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-groups', ' ', array( &$this, 'buddypress_shortcodes_groups_settings_content' ), 'buddypress-shortcode-groups' );

		$this->plugin_settings_tabs['buddypress-shortcode-user-notification'] = esc_html__( 'User Notification', 'shortcodes-for-buddypress' );
		add_settings_section( 'buddypress-shortcode-user-notification', ' ', array( &$this, 'buddypress_shortcodes_user_notification_settings_content' ), 'buddypress-shortcode-user-notification' );
	}

	/**
	 * Welcome Tab Content
	 *
	 * @since 1.0.0
	 */
	public function buddypress_shortcodes_welcome_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-welcome-page.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-welcome-page.php';
		}
	}

		/**
		 * General Tab Content
		 *
		 * @since 1.0.0
		 */
	public function buddypress_shortcodes_general_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-general-settings.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-general-settings.php';
		}
	}

	/**
	 * Activity tab content
	 *
	 * @return void
	 */
	public function buddypress_shortcodes_activities_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-activity-listing.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-activity-listing.php';
		}
	}

	/**
	 * Member tab content
	 *
	 * @return void
	 */
	public function buddypress_shortcodes_member_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-member-listing.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-member-listing.php';
		}
	}

	/**
	 * Group tab content
	 *
	 * @return void
	 */
	public function buddypress_shortcodes_groups_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-groups-listing.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-groups-listing.php';
		}
	}

	/**
	 * User notification tab content
	 *
	 * @return void
	 */
	public function buddypress_shortcodes_user_notification_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/settings/buddypress-shortcode-user-notification-listing.php' ) ) {
			require_once dirname( __FILE__ ) . '/settings/buddypress-shortcode-user-notification-listing.php';
		}
	}

}
