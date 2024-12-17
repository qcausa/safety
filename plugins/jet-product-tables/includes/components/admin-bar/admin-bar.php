<?php
namespace Jet_WC_Product_Table\Components\Admin_Bar;

use Jet_WC_Product_Table\Plugin;

// Prevent direct access to the file.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin bar manager
 */
class Admin_Bar {

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

	protected $items = [];

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
	 * Constructor.
	 *
	 * The constructor is private to prevent creating multiple instances
	 * of the singleton.
	 */
	private function __construct() {

		if ( is_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );

		add_action( 'admin_bar_menu', array( $this, 'register_menu_items' ), 99 );
	}

	/**
	 * Register all menu items which was stacked into $this->items dusring page render.
	 *
	 * @param  object $wp_admin_bar Admin bar manager instance.
	 * @return void
	 */
	public function register_menu_items( $wp_admin_bar ) {

		if ( empty( $this->items ) ) {
			return;
		}

		$wp_admin_bar->add_menu( [
			'id'     => 'jet-wc-product-table',
			'parent' => null,
			'group'  => null,
			'title'  => esc_html__( 'Product Table', 'jet-wc-product-table' ),
			'href'   => false,
		] );

		foreach ( $this->items as $id => $item ) {
			$wp_admin_bar->add_menu( [
				'id'     => 'jet-wc-product-table-' . $id,
				'parent' => 'jet-wc-product-table',
				'group'  => null,
				'title'  => $item['title'],
				'href'   => $item['href'],
				'meta'   => [
					'target' => '_blank',
				],
			] );
		}
	}

	/**
	 * Resgiter admin bar item
	 *
	 * @param string $id    Item ID.
	 * @param string $href  Item URL.
	 * @param string $title Item display name.
	 */
	public function add_item( $id = '', $href = '', $title = '' ) {
		$this->items[ $id ] = [
			'href' => $href,
			'title' => $title,
		];
	}

	/**
	 * Register preset
	 *
	 * @param  string $preset_id   Preset ID.
	 * @param  string $preset_name Preset display name.
	 * @return void
	 */
	public function register_preset( $preset_id = '', $preset_name = '' ) {

		$this->add_item(
			'preset_' . $preset_id,
			Plugin::instance()->settings->settings_page_url() . '#preset_manager__' . $preset_id,
			esc_html__( 'Preset: ', 'jet-wc-product-table' ) . $preset_name
		);
	}

	/**
	 * Register Integration
	 *
	 * @param  string $id   Integration ID.
	 * @param  string $name Integration display  name.
	 * @return void
	 */
	public function register_integration( $id = '', $name = '' ) {

		$this->add_item(
			'integration_' . $id,
			Plugin::instance()->settings->settings_page_url() . '#integration',
			esc_html__( 'Active Integration: ', 'jet-wc-product-table' ) . $name
		);
	}

	/**
	 * Register all table related items
	 *
	 * @return void
	 */
	public function register_table() {

		$this->add_item(
			'general_settings',
			Plugin::instance()->settings->settings_page_url(),
			esc_html__( 'General Settings', 'jet-wc-product-table' )
		);
	}
}
