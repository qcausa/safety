<?php

namespace Jet_WC_Product_Table\Components\Blocks;

class Block_Controller {
	/**
	 * Constructor method to initialize the block registration.
	 */
	public function __construct() {
		add_action( 'init', [
			$this,
			'register_blocks',
		], 0 );
	}

	public function register_blocks() {
		new \Jet_WC_Product_Table\Components\Blocks\Block_Types\Product_Table_Block();
	}
}
