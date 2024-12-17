<?php
namespace Jet_WC_Product_Table\Components\Shortcodes;

use Jet_WC_Product_Table\Plugin;

// Prevent direct access to the file.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortocodes Controller
 */
class Controller {

	public function __construct() {
		$this->register_shortcode( new Types\Table_Shortcode() );
	}

	public function register_shortcode( $shortcode ) {
		add_shortcode( $shortcode->tag(), [ $shortcode, 'render' ] );
	}
}
