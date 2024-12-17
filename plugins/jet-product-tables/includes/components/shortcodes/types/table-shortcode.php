<?php
namespace Jet_WC_Product_Table\Components\Shortcodes\Types;

use Jet_WC_Product_Table\Traits\Table_Render_By_Attributes;

/**
 * Class to handle the shortcode functionality for displaying a WooCommerce product table.
 */
class Table_Shortcode {

	use Table_Render_By_Attributes;

	public function tag() {
		return 'jet_woocommerce_product_table';
	}

	/**
	 * Renders the WooCommerce product table based on the shortcode usage.
	 *
	 * @param array $atts Attributes passed to the shortcode.
	 *
	 * @return string The HTML content of the product table.
	 */
	public function render( $atts = [] ) {

		if ( ! empty( $atts['attributes'] ) ) {
			$atts = $atts['attributes'];
		} elseif ( isset( $atts[0] ) ) {
			$atts = str_replace( 'attributes=', '', $atts[0] );
		} else {
			$atts = '';
		}

		$atts        = str_replace( [ '%{', '}%' ], [ '[', ']' ], $atts );
		$parsed_atts = ! empty( $atts ) ? json_decode( $atts, true ) : [];

		return $this->render_callback( $parsed_atts );
	}
}
