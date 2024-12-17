<?php
namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
class Product_Variations_Content extends Product_Variations_Tab {

	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	public function get_id() {
		return 'product_variations_content';
	}

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	public function get_name() {
		return __( 'Product Variations in Body', 'jet-wc-product-table' );
	}

	/**
	 * Description of current integration type for settings page
	 *
	 * @return [type] [description]
	 */
	public function get_description() {
		return __( 'Add product variations table into the body of the single product page, before the tabs.', 'jet-wc-product-table' );
	}

	public function apply() {
		add_filter( 'woocommerce_after_single_product_summary', [ $this, 'get_integration_table' ], 0 );
	}
}
