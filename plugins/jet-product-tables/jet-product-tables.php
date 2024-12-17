<?php
/**
 * Plugin Name: JetProductTables
 * Plugin URI:  https://crocoblock.com/plugins/jetproducttables/
 * Description: Create a custom tables to output WooCommerce products. Choose between different layouts and filtering options.
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * Text Domain: jet-wc-product-table
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 *
 * @package JetProductTables
 */

// Prevent direct access to the file, ensuring the file is only accessed through WordPress mechanisms.
if ( ! defined( 'WPINC' ) ) {
	die(); // Exit if accessed directly.
}

// Register an action with WordPress to initialize the plugin once all plugins are loaded.
add_action( 'plugins_loaded', 'jet_wc_product_table_init', 99 );

/**
 * Initializes the Jet WooCommerce Product Table plugin.
 * This function defines several constants for use throughout the plugin and requires the main plugin class file.
 */
function jet_wc_product_table_init() {

	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'jet_wc_product_table_admin_notice_missing_woocommerce' );
		return;
	}

	// Define the current version of the plugin.
	define( 'JET_WC_PT_VERSION', '1.0.0' );

	// Define a constant for the plugin file path.
	define( 'JET_WC_PT__FILE__', __FILE__ );

	// Define a base for the plugin, used for generating links to the plugin's pages.
	define( 'JET_WC_PT_PLUGIN_BASE', plugin_basename( JET_WC_PT__FILE__ ) );

	// Define the absolute path to the plugin's directory.
	define( 'JET_WC_PT_PATH', plugin_dir_path( JET_WC_PT__FILE__ ) );

	// Define the URL to the plugin's directory, useful for loading assets like JavaScript and CSS files.
	define( 'JET_WC_PT_URL', plugins_url( '/', JET_WC_PT__FILE__ ) );

	// Include the main plugin file that initializes the plugin's classes and functionality.
	require JET_WC_PT_PATH . 'includes/plugin.php';
}

/**
 * Show WooCommerce missing admin notice
 */
function jet_wc_product_table_admin_notice_missing_woocommerce() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p><strong>Jet WooCommerce Product Table</strong> <?php
			printf(
				esc_html__( 'requires %s to be installed and activated.', 'jet-wc-product-table' ),
				'<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '" target="_blank"><strong>WooCommerce</strong></a>'
			);
		?></p>
	</div>
	<?php
}
