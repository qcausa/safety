<?php

namespace Jet_WC_Product_Table;

/**
 * Manages plugin settings within WooCommerce settings.
 */
class Settings {

	/**
	 * Option name for storing plugin settings
	 * @var string
	 */
	private $option_name = 'jet_wc_product_table_settings';

	/**
	 * Name of tab in WC settings
	 * @var string
	 */
	private $setting_tab = 'jet_wc_product_table';

	/**
	 * Settings cahce
	 * @var null|array
	 */
	private $settings = null;

	/**
	 * Constructor: Sets up hooks for the plugin settings integration.
	 */
	public function __construct() {

		// Add a new tab to WooCommerce settings.
		add_filter( 'woocommerce_settings_tabs_array', [
			$this,
			'add_settings_tab',
		], 100 );

		// Display the settings for our custom tab.
		add_action( 'woocommerce_settings_tabs_' . $this->setting_tab, [
			$this,
			'settings_page',
		] );

		// Handle settings save via AJAX.
		add_action( 'wp_ajax_' . $this->option_name, [
			$this,
			'save_settings',
		] );
	}

	/**
	 * Get URL of settings page
	 *
	 * @return boolean [description]
	 */
	public function settings_page_url() {
		return esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->setting_tab ) );
	}

	/**
	 * Adds a custom tab to the WooCommerce settings tabs.
	 *
	 * @param array $settings_tabs Existing settings tabs.
	 *
	 * @return array Modified settings tabs with our custom tab added.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->setting_tab ] = __( 'Product Table', 'jet-wc-product-table' );

		return $settings_tabs;
	}

	/**
	 * Enqueues scripts and styles required for the settings page.
	 */
	public function enqueue_assets() {

		$deps = [
			'wp-polyfill',
			'wp-util',
			'wp-core-data',
			'wp-block-library',
			'wp-media-utils',
			'wp-components',
			'wp-element',
			'wp-blocks',
			'wp-block-editor',
			'wp-data',
			'wp-i18n',
			'lodash',
			'wp-api-fetch',
		];

		do_action( 'jet-wc-product-table/settings/assets/before' );

		wp_enqueue_script(
			'jet-wc-product-table-settings',
			JET_WC_PT_URL . 'assets/js/admin/settings.js',
			$deps,
			JET_WC_PT_VERSION,
			true
		);

		wp_enqueue_style( 'wp-components' );

		do_action( 'jet-wc-product-table/settings/assets/after' );

		$this->enqueue_data( 'jet-wc-product-table-settings' );
	}

	/**
	 * Outputs the HTML for the plugin settings page.
	 */
	public function settings_page() {

		// Ensure our scripts and styles are enqueued.
		$this->enqueue_assets();

		// This is where our settings UI will be rendered by React.
		echo '<div id="jet_wc_product_table_settings"></div>';
	}

	/**
	 * Handles saving the settings.
	 *
	 * It's called both when the WooCommerce settings are saved and via AJAX.
	 */
	public function save_settings() {

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to modify these settings.' );
		}

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->option_name ) ) { // phpcs:ignore
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		$updated_settings = [];
		$new_settings     = isset( $_POST['settings'] ) ? $_POST['settings'] : []; // phpcs:ignore
		$updated_settings = $this->sanitize_settings( $new_settings );

		// Save the updated settings.
		update_option( $this->option_name, $updated_settings );

		/**
		 * Do anything after settings save.
		 *
		 * @hooked:
		 * \Jet_WC_Product_Table\Components\Style_Manager\Controller::save_styles()
		 */
		do_action( 'jet-wc-product-table/settings/save', $new_settings, $this );

		wp_send_json_success();
	}

	/**
	 * Sanitize settings data before save into the DB
	 *
	 * @param array $new_settings New settings array to clear.
	 *
	 * @return array
	 */
	public function sanitize_settings( $new_settings = [] ) {

		$sanitized_settings = [];

		// Loop through the default settings to update them with new values.
		foreach ( $this->get_defaults() as $option_name => $option_default_value ) {

			$option_value = isset( $new_settings[ $option_name ] ) ? $new_settings[ $option_name ] : $option_default_value;

			if ( is_bool( $option_default_value ) ) {
				$option_value = filter_var( $option_value, FILTER_VALIDATE_BOOLEAN );
			}

			switch ( $option_name ) {
				case 'columns':
					$option_value = Plugin::instance()->columns_controller->sanitize_columns_data( $option_value );
					break;

				case 'integrations':
					$option_value = Plugin::instance()->integration_controller->sanitize_integrations_data( $option_value );
					break;

				case 'filters':
					$option_value = Plugin::instance()->filters_controller->sanitize_filters_data( $option_value );
					break;

				case 'settings':
					$option_value = [];

					foreach ( $new_settings[ $option_name ] as $inner_key => $inner_value ) {
						if (
							isset( $option_default_value[ $inner_key ] )
							&& is_bool( $option_default_value[ $inner_key ] ) ) {
							$option_value[ $inner_key ] = filter_var( $inner_value, FILTER_VALIDATE_BOOLEAN );
						} elseif ( isset( $option_default_value[ $inner_key ] ) ) {
							$option_value[ $inner_key ] = $inner_value;
						}
					}
					break;
			}

			$sanitized_settings[ $option_name ] = $option_value;
		}

		return $sanitized_settings;
	}

	/**
	 * Returns the default settings.
	 *
	 * @return array Default settings values.
	 */
	public function get_defaults() {
		return apply_filters( 'jet-wc-product-table/settings/defaults', [
			'columns'         => [
				Plugin::instance()->columns_controller->get_column( 'product-id' )->column_as_default_settings(),
				Plugin::instance()->columns_controller->get_column( 'product-name' )->column_as_default_settings(),
				Plugin::instance()->columns_controller->get_column( 'product-image' )->column_as_default_settings(),
				Plugin::instance()->columns_controller->get_column( 'product-sku' )->column_as_default_settings(),
				Plugin::instance()->columns_controller->get_column( 'product-price' )->column_as_default_settings(),
				Plugin::instance()->columns_controller->get_column( 'product-actions' )->column_as_default_settings(),
			],
			'filters_enabled' => false,
			'filters'         => [
				Plugin::instance()->filters_controller->get_filter_type( 'tax_query' )->filter_as_default_settings(),
				Plugin::instance()->filters_controller->get_filter_type( 'search_query' )->filter_as_default_settings(),
			],
			'settings'        => [
				'direction'       => 'horizontal',
				'show_header'     => true,
				'sticky_header'   => false,
				'show_footer'     => false,
				'mobile_layout'   => 'scroll',
				'mobile_columns'  => [],
				'lazy_load'       => false,
				'pager'           => true,
				'pager_position'  => 'after',
				'load_more'       => false,
				'load_more_label' => 'Load more',
			],
			'integrations'    => Plugin::instance()->integration_controller->get_default_settings(),
		], $this );
	}

	/**
	 * Retrieves a specific setting or all settings.
	 *
	 * @param string $setting       The specific setting to retrieve. If empty, retrieves all settings.
	 * @param mixed  $default_value The default value to return if the setting is not found.
	 *
	 * @return mixed The setting value or all settings if no specific setting is provided.
	 */
	public function get( $setting = '', $default_value = '' ) {

		if ( null === $this->settings ) {

			$settings       = get_option( $this->option_name, [] );
			$this->settings = array_merge( $this->get_defaults(), $settings );

			$this->settings['integrations'] = array_merge(
				Plugin::instance()->integration_controller->get_default_settings(),
				$this->settings['integrations']
			);

			// Convert string 'true'/'false' to boolean true/false
			foreach ( $this->settings['settings'] as $key => $value ) {
				// phpcs:ignore
				if ( is_string( $value ) && in_array( $value, [ 'true', 'false' ] ) ) {
					$this->settings['settings'][ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				}
			}

			// Convert 'show_count' and 'hierarchical' to boolean
			foreach ( $this->settings['filters'] as &$filter ) {

				if ( isset( $filter['settings']['show_count'] ) ) {
					$filter['settings']['show_count'] = filter_var( $filter['settings']['show_count'], FILTER_VALIDATE_BOOLEAN );
				}

				if ( isset( $filter['settings']['hierarchical'] ) ) {
					$filter['settings']['hierarchical'] = filter_var( $filter['settings']['hierarchical'], FILTER_VALIDATE_BOOLEAN );
				}
			}
		}

		if ( ! empty( $setting ) ) {
			return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : $default_value;
		} else {
			return $this->settings;
		}
	}


	/**
	 * Checks if the current user has access to modify settings.
	 *
	 * @return bool True if the user can manage options, false otherwise.
	 */
	public function user_has_access() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Enqueues the data for the script.
	 *
	 * @param string $handle The handle of the script to localize the data for.
	 */
	public function enqueue_data( $handle ) {

		$columns = Plugin::instance()->columns_controller->get_columns_for_js();

		foreach ( $columns as &$column ) {

			$column_type = Plugin::instance()->columns_controller->get_column( $column['value'] );

			if ( $column_type ) {
				$column['settings'] = $column_type->get_merged_additional_settings();
			}
		}

		$filters = Plugin::instance()->filters_controller->get_filter_types_for_js();

		foreach ( $filters as &$filter ) {

			$filter_type = Plugin::instance()->filters_controller->get_filter_type( $filter['value'] );

			if ( $filter_type ) {
				$filter['settings'] = $filter_type->get_merged_additional_settings();
			}
		}

		// Fetch product types
		$product_types = wc_get_product_types();

		$product_type_options = [];
		foreach ( $product_types as $type => $label ) {
			$product_type_options[] = [
				'label' => $label,
				'value' => $type,
			];
		}

		// Fetch post statuses
		$post_statuses = get_post_statuses();

		$status_options = [];
		foreach ( $post_statuses as $status => $label ) {
			$status_options[] = [
				'label' => $label,
				'value' => $status,
			];
		}

		/**
		 * Retrieves the visibility options for WooCommerce products.
		 *
		 * @return array An array of visibility options with labels and values.
		 */
		function get_wc_product_visibility_options() {
			return [
				[
					'label' => __( 'Catalog & Search', 'jet-wc-product-table' ),
					'value' => 'visible',
				],
				[
					'label' => __( 'Catalog', 'jet-wc-product-table' ),
					'value' => 'catalog',
				],
				[
					'label' => __( 'Search', 'jet-wc-product-table' ),
					'value' => 'search',
				],
				[
					'label' => __( 'Hidden', 'jet-wc-product-table' ),
					'value' => 'hidden',
				],
			];
		}

		$visibility_options = get_wc_product_visibility_options();

		$data = apply_filters( 'jet-wc-product-table/settings/localize-data', [
			'settings'           => $this->get(),
			'columns'            => $columns,
			'filters'            => $filters,
			'hook'               => $this->get_option_name(),
			'nonce'              => wp_create_nonce( $this->get_option_name() ),
			'presets'            => Plugin::instance()->presets->get_presets_for_js(),
			'integrations'       => Plugin::instance()->integration_controller->get_integrations_for_js(),
			'product_types'      => $product_type_options,
			'status_options'     => $status_options,
			'visibility_options' => $visibility_options,
			'settings_url'       => $this->settings_page_url(),
			'plugin_url'         => JET_WC_PT_URL,
			'l10n'               => [
				'save_button' => __( 'Save Settings', 'jet-wc-product-table' ),
				'add_column'  => __( 'Add Column', 'jet-wc-product-table' ),
			],
		] );

		wp_localize_script( $handle, 'JetWCProductsTableData', $data );
	}

	/**
	 * Getter for option_name
	 *
	 * @return string
	 */
	public function get_option_name() {
		return $this->option_name;
	}
}
