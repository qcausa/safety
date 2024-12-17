<?php

namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
abstract class Base_Integration {

	protected $preset = null;

	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	abstract public function get_id();

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	abstract public function get_name();

	abstract public function get_description();

	/**
	 * Renders the content of the column for a given product.
	 * Must be implemented by the subclass to generate the HTML output specific to this column.
	 *
	 * @return string The rendered HTML of the column content.
	 */
	abstract public function apply();

	/**
	 * Defines when integration page is rendered.
	 * Any custom integrations must rewrite this method with own logic
	 *
	 * @return boolean [description]
	 */
	public function is_integration_page_now() {
		return false;
	}

	/**
	 * Add preset to current integration
	 *
	 * @param int $preset Preset ID.
	 */
	public function add_preset( $preset = '' ) {

		$preset = absint( $preset );

		if ( $preset ) {
			$this->preset = $preset;
		}

		return $this;
	}

	/**
	 * Get a table for the current integration
	 *
	 * @return [type] [description]
	 */
	public function get_integration_table() {

		if ( $this->preset ) {
			$settings = Plugin::instance()->presets->get_preset_data_for_display( $this->preset );
		} else {
			$settings = Plugin::instance()->settings->get();
		}

		// Create a new Table instance with the given attributes.
		$table = new Table( [
			'query'           => [ 'query_type' => 'current_query' ],
			'columns'         => $settings['columns'] ?? null,
			'settings'        => $settings['settings'] ?? [],
			'filters_enabled' => $settings['filters_enabled'] ?? null,
			'filters'         => $settings['filters'] ?? null,
		] );

		ob_start();
		$table->render();

		// Return the rendered table HTML.
		return ob_get_clean();
	}
}
