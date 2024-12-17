<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

/**
 * Represents a column type for displaying product images in the product table.
 * Extends the Base_Column class and implements its abstract methods to handle product images.
 */
class Product_Image_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product image column.
	 *
	 * @return string The unique identifier for the product image column.
	 */
	public function get_id() {
		return 'product-image';
	}

	/**
	 * Returns the display name of the product image column.
	 * Uses localization to ensure the name can be translated into different languages.
	 *
	 * @return string The display name for the product image column.
	 */
	public function get_name() {
		return __( 'Product Image', 'jet-wc-product-table' );
	}

	/**
	 * Renders the product image for a given product.
	 * This method should be implemented to output the HTML for displaying the product's image.
	 * The current implementation is a placeholder and needs to be completed based on the project's requirements.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering.
	 *
	 * @return string The rendered HTML of the product image.
	 */
	protected function render( $product, $attrs = [] ) {
		$image_size = $attrs['image_size'] ?? 'thumbnail';
		$custom_size = $attrs['custom_size'] ?? '';
		$linked = $attrs['linked'] ?? true;

		if ( ! empty( $custom_size ) ) {
			$dimensions = explode( 'x', $custom_size );
			$image_html = wp_get_attachment_image( $product->get_image_id(), $dimensions );

			if ( empty( $image_html ) ) {
				$image_url = wc_placeholder_img_src();
				$image_html = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $product->get_name() ) . '" width="' . esc_attr( $dimensions[0] ) . '" height="' . esc_attr( $dimensions[1] ) . '"/>';
			}
		} else {
			$image_html = woocommerce_get_product_thumbnail( $image_size );
		}

		if ( $linked ) {
			$image_html = '<a href="' . get_permalink( $product->get_id() ) . '">' . $image_html . '</a>';
		}

		return $image_html;
	}

	/**
	 * Get additional column settings.
	 *
	 * @return array
	 */
	public function additional_settings(): array {
		return [
			'image_size' => [
				'label' => 'Image Size',
				'type' => 'select',
				'description' => 'Choose the image size to display.',
				'default' => 'thumbnail',
				'options' => [
					[
						'value' => 'thumbnail',
						'label' => 'Thumbnail',
					],
					[
						'value' => 'medium',
						'label' => 'Medium',
					],
					[
						'value' => 'large',
						'label' => 'Large',
					],
				],
			],
			'linked' => [
				'label' => __( 'Linked', 'jet-wc-product-table' ),
				'type' => 'toggle',
				'description' => 'Toggle to link the product image to the product page.',
				'default' => true,
			],
			'custom_size' => [
				'label' => 'Custom Size',
				'type' => 'text',
				'description' => 'Enter a custom image size in WxH format (e.g., 100x100).',
				'default' => '',
			],
		];
	}
}
