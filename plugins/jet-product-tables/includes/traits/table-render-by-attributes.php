<?php
namespace Jet_WC_Product_Table\Traits;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

/**
 * Renders table by attributes array
 */
trait Table_Render_By_Attributes {

	/**
	 * Render callback for the table.
	 *
	 * This function handles the rendering of the table's HTML on the frontend.
	 *
	 * @param array $attributes table attributes.
	 *
	 * @return string Rendered HTML for the table.
	 */
	public function render_callback( $attributes ) {

		$preset = ! empty( $attributes['preset'] ) ? absint( $attributes['preset'] ) : false;
		$preset = ( $preset ) ? Plugin::instance()->presets->get_preset_data_for_display( $preset ) : false;

		if ( $preset ) {
			$columns         = $preset['columns'] ?? null;
			$settings        = $preset['settings'] ?? [];
			$filters_enabled = $preset['filters_enabled'] ?? null;
			$filters         = $preset['filters'] ?? null;
		} else {
			$columns         = $attributes['columns'] ?? null;
			$settings        = $attributes['settings'] ?? [];
			$filters_enabled = $attributes['filters_enabled'] ?? null;
			$filters         = $attributes['filters'] ?? null;
		}

		$query = $attributes['query'] ?? [];
		$query = array_merge( [
			'query_type' => 'products',
		], $query );

		// Create a new Table instance with the given attributes.
		$table = new Table( [
			'query'           => $query,
			'columns'         => $columns,
			'settings'        => $settings,
			'filters_enabled' => $filters_enabled,
			'filters'         => $filters,
		] );

		ob_start();
		$table->render();

		if ( $this->is_editor_requested() ) {
			wp_print_styles( 'jet-wc-product-table-styles' );
		}

		// Return the rendered table HTML.
		return ob_get_clean();
	}

	/**
	 * Check if editor request is currently processed
	 *
	 * @return boolean
	 */
	public function is_editor_requested() {
		// phpcs:ignore
		return ! empty( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'] ? true : false;
	}
}
