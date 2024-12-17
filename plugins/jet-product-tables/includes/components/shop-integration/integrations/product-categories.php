<?php

namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;
/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
class Product_Categories extends Product_Taxonomy {

	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	public function get_id() {
		return 'product_categories';
	}

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	public function get_name() {
		return __( 'Product Categories', 'jet-wc-product-table' );
	}

	/**
	 * Description of current integration type for settings page
	 *
	 * @return [type] [description]
	 */
	public function get_description() {
		return __( 'Show a table instead of the default products grid on the products categories archives.', 'jet-wc-product-table' );
	}

	/**
	 * Check if integration page is currently displaying
	 *
	 * @return boolean [description]
	 */
	public function is_integration_page_now() {
		return function_exists( 'is_product_category' ) && is_product_category();
	}
}
