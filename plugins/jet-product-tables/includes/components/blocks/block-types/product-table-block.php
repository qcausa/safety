<?php

namespace Jet_WC_Product_Table\Components\Blocks\Block_Types;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

use Jet_WC_Product_Table\Traits\Table_Render_By_Attributes;

/**
 * Class Product_Table_Block
 *
 * This class is responsible for registering and handling the WooCommerce Product Table block
 * in the block editor (Gutenberg). It sets up the block's attributes, editor script, and render
 * callback function.
 */
class Product_Table_Block {

	use Table_Render_By_Attributes;

	/**
	 * Constructor.
	 *
	 * Registers the block and its associated assets when the class is instantiated.
	 */
	public function __construct() {
		// Hook into the 'init' action to register the block
		add_action( 'init', [
			$this,
			'register_block',
		] );

		// Hook into the 'enqueue_block_editor_assets' action to register block editor assets.
		add_action( 'enqueue_block_editor_assets', [
			$this,
			'register_assets',
		] );
	}

	/**
	 * Registers the block with its attributes and settings.
	 *
	 * This function defines the block type and its attributes, sets the editor script,
	 * and specifies the render callback function to be used for rendering the block on the frontend.
	 */
	public function register_block() {
		register_block_type( 'jet-woocommerce-product-table/product-table-block', [
			'editor_script'   => 'jet-wc-product-table-block-editor',
			'render_callback' => [ $this, 'render_callback' ],
			'attributes'      => [
				'preset' => [
					'type' => 'string',
				],
				'columns' => [
					'type' => 'array',
				],
				'query'   => [
					'type'    => 'object',
					'default' => [
						'status'            => 'publish',
						'type'              => '',
						'exclude'           => '',
						'include'           => '',
						'limit'             => 10,
						'page'              => 1,
						'order'             => 'DESC',
						'orderby'           => 'date',
						'parent'            => '',
						'parentExclude'     => '',
						'offset'            => 0,
						'paginate'          => false,
						'returnFormat'      => 'objects',
						'sku'               => '',
						'name'              => '',
						'stock_status'      => '',
						'virtual'           => false,
						'downloadable'      => false,
						'tag'               => '',
						'tagID'             => '',
						'category'          => '',
						'categoryID'        => '',
						'weight'            => '',
						'length'            => '',
						'width'             => '',
						'height'            => '',
						'price'             => '',
						'regular_price'     => '',
						'sale_price'        => '',
						'total_sales'       => 0,
						'featured'          => false,
						'sold_individually' => false,
						'manage_stock'      => false,
						'reviews_allowed'   => false,
						'backorders'        => '',
						'visibility'        => '',
						'stock_quantity'    => 0,
						'tax_status'        => '',
						'tax_class'         => '',
						'shipping_class'    => '',
						'download_limit'    => 0,
						'download_expiry'   => 0,
						'average_rating'    => 0,
						'review_count'      => 0,
						'date_created'      => '',
						'date_modified'     => '',
						'date_on_sale_from' => '',
						'date_on_sale_to'   => '',
					],
				],
				'settings' => [
					'type' => 'object',
				],
				'filters' => [
					'type' => 'array',
				],
				'filters_enabled' => [
					'type' => 'boolean',
				],
				'block_preview' => [
					'type'    => 'string',
					'default' => '',
				],
			],
			'example'         => [
				'attributes' => [
					'block_preview' => 'product-table-block-preview.png',
				],
			],
		]);
	}

	/**
	 * Register block editor assets.
	 *
	 * This function enqueues the JavaScript file required for the block editor.
	 * It also localizes script data for use in the block editor.
	 */
	public function register_assets() {

		wp_enqueue_script(
			'jet-wc-product-table-block-editor',
			JET_WC_PT_URL . 'assets/js/blocks/product-table/index.js',
			[
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-i18n',
			],
			JET_WC_PT_VERSION,
			true
		);

		// Enqueue localized data for the block editor script
		Plugin::instance()->settings->enqueue_data( 'jet-wc-product-table-block-editor' );
	}
}
