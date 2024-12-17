<?php
namespace Jet_WC_Product_Table;

// Prevent direct access to the file.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main plugin class.
 *
 * This class initializes the plugin, including its components like the
 * columns controller and the shortcode handler.
 */
class Plugin {

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	/**
	 * Columns Controller.
	 *
	 * This property stores the instance of the columns controller which
	 * manages all the column types for the plugin.
	 *
	 * @var \Jet_WC_Product_Table\Components\Columns\Controller
	 */
	public $columns_controller;

	/**
	 * Filters Controller.
	 *
	 * This property stores the instance of the filters controller
	 *
	 * @var \Jet_WC_Product_Table\Components\Filters\Controller
	 */
	public $filters_controller;

	/**
	 * Plugin settings instance
	 *
	 * @var \Jet_WC_Product_Table\Settings
	 */
	public $settings;

	/**
	 * Plugin presets instance
	 *
	 * @var \Jet_WC_Product_Table\Presets
	 */
	public $presets;

	/**
	 * Instance of integration manager.
	 * Responsible for tables integration into default shop layouts.
	 *
	 * @var \Jet_WC_Product_Table\Components\Shop_Integration\Controller
	 */
	public $integration_controller;

	/**
	 * Instance of assets manager.
	 *
	 * @var \Jet_WC_Product_Table\Assets
	 */
	public $assets;

	/**
	 * Instance of styles manager.
	 *
	 * @var \Jet_WC_Product_Table\Components\Style_Manager\Controller
	 */
	public $styles_manager;

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}

	/**
	 * Register autoloader.
	 */
	private function register_autoloader() {
		require JET_WC_PT_PATH . 'includes/autoloader.php';
		Autoloader::run();
	}

	/**
	 * Constructor.
	 *
	 * The constructor is private to prevent creating multiple instances
	 * of the singleton.
	 */
	private function __construct() {

		$this->register_autoloader();

		add_action( 'after_setup_theme', [ $this, 'init_components' ] );
	}

	/**
	 * Initializes plugin components.
	 *
	 * This method sets up the columns controller and initializes the shortcode
	 * handler with it, so that all plugin functionality is ready to use.
	 */
	public function init_components() {

		$this->columns_controller     = new Components\Columns\Controller();
		$this->filters_controller     = new Components\Filters\Controller();
		$this->integration_controller = new Components\Shop_Integration\Controller();
		$this->presets                = new Presets();
		$this->settings               = new Settings();
		$this->assets                 = new Assets();
		$this->styles_manager         = new Components\Style_Manager\Controller();

		new \Jet_WC_Product_Table\Components\Blocks\Block_Controller();
		new \Jet_WC_Product_Table\Components\Shortcodes\Controller();

		add_filter( 'plugin_action_links_' . JET_WC_PT_PLUGIN_BASE, function ( $actions ) {

			$actions[] = sprintf(
				'<a href="%1$s"><b>%2$s</b></a>',
				Plugin::instance()->settings->settings_page_url(),
				esc_html__( 'Product Tables Settings', 'jet-wc-product-table' )
			);

			$actions[] = sprintf(
				'<a href="https://crocoblock.com/knowledge-base/features/jetproducttables-dashboard-settings-overview/">%1$s</a>',
				esc_html__( 'Quick Start Guide', 'jet-wc-product-table' )
			);

			return $actions;
		} );
	}
}

// Initialize the plugin instance.
Plugin::instance();
