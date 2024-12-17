<?php

namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

use Jet_WC_Product_Table\Traits\Product_By_Slug;

/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
class Product_Variations_Tab extends Base_Integration {

	use Product_By_Slug;

	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	public function get_id() {
		return 'product_variations_tab';
	}

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	public function get_name() {
		return __( 'Product Variations Tab', 'jet-wc-product-table' );
	}

	/**
	 * Description of current integration type for settings page
	 *
	 * @return [type] [description]
	 */
	public function get_description() {
		return __( 'Add product variations table as a new tab on the single product page.', 'jet-wc-product-table' );
	}

	/**
	 * Check if integration page is currently displaying
	 *
	 * @return boolean [description]
	 */
	public function is_integration_page_now() {

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;

		$product = $this->ensure_product_by_slug( $product );

		return $product && is_object( $product ) && $product->is_type( 'variable' );
	}

	public function apply() {
		add_filter( 'woocommerce_product_tabs', [
			$this,
			'add_variation_tab',
		] );
	}

	public function add_variation_tab( $tabs = [] ) {

		// Define a new tab
		$tabs['jet_wc_varitions'] = [
			'title'    => apply_filters(
				'jet-wc-product-table/components/shop-integration/variation-tab/title',
				__( 'Variations', 'jet-wc-product-table' )
			),
			'priority' => 99,
			'callback' => [
				$this,
				'get_integration_table',
			],
		];

		return $tabs;
	}

	/**
	 * Get a table for the current integration
	 *
	 * @return void
	 */
	public function get_integration_table() {

		if ( $this->preset ) {
			$settings = Plugin::instance()->presets->get_preset_data_for_display( $this->preset );
		} else {
			$settings = Plugin::instance()->settings->get();
		}

		// Create a new Table instance with the given attributes.
		$table = new Table( [
			'query'           => [
				'query_type'     => 'variations',
				'variation_type' => 'current_product',
			],
			'columns'         => $settings['columns'] ?? null,
			'settings'        => $settings['settings'] ?? [],
			'filters_enabled' => $settings['filters_enabled'] ?? null,
			'filters'         => $settings['filters'] ?? null,
		] );

		ob_start();
		$table->render();

		// Return the rendered table HTML.
		echo ob_get_clean(); // phpcs:ignore
	}
}
