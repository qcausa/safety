<?php

namespace Jet_WC_Product_Table\Components\Columns\Column_Types;

use Jet_WC_Product_Table\Plugin;

/**
 * Represents a column type for displaying product actions in the product table.
 * This class extends the Base_Column class, implementing the required methods
 * to handle the display of product actions.
 */
class Product_Actions_Column extends Base_Column {
	/**
	 * Returns the unique identifier of the product actions column.
	 *
	 * @return string The unique identifier for the product actions column.
	 */
	public function get_id() {
		return 'product-actions';
	}

	/**
	 * Returns the display name of the product actions column.
	 * This name can be localized, allowing it to be translated into different languages.
	 *
	 * @return string The display name for the product actions column.
	 */
	public function get_name() {
		return __( 'Buttons', 'jet-wc-product-table' );
	}

	/**
	 * Process success and errors for the add to cart handler of variable products
	 */
	public function global_init() {

		// phpcs:ignore
		if ( empty( $_REQUEST['jet_wc_cart'] ) ) {
			return;
		}

		add_action( 'woocommerce_add_to_cart_redirect', 'wp_send_json_success' );

		add_action( 'wp_loaded', function () {

			$errors = wc_print_notices( true );

			if ( $errors ) {
				wp_send_json_error( $errors );
			} else {
				wp_send_json_error( __( 'Internal error, please try again', 'jet-wc-product-table' ) );
			}
		}, 21 );
	}

	/**
	 * Renders the product name for a given product.
	 * This method extracts and returns the name of the product, which will be displayed in the product table.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @param array      $attrs Additional attributes or data that might affect rendering. Not used in this implementation but can be utilized for extending functionality.
	 *
	 * @return string
	 */
	protected function render( $product, $attrs = [] ) {

		$content = '';
		$variation_type = $attrs['variations'] ?? 'dropdown';

		if ( ! $product->is_type( 'variable' ) || 'dropdown' !== $variation_type ) {
			$content .= '<div class="jet-wc-product-table-actions">';
		}

		$show_qty = ! empty( $attrs['quantities'] ) ? $attrs['quantities'] : false;
		$show_qty = filter_var( $show_qty, FILTER_VALIDATE_BOOLEAN );

		$quantity_allowed = apply_filters(
			'jet-wc-product-table/components/columns/action/qty-allowed',
			[ 'simple' ],
			$product,
			$attrs
		);

		$is_qty_allowed = false;

		foreach ( $quantity_allowed as $product_type ) {
			if ( $product->is_type( $product_type ) ) {
				$is_qty_allowed = true;
			}
		}

		// Render quantity picker if enabled
		if ( $show_qty && $is_qty_allowed ) {
			$content .= woocommerce_quantity_input( [ 'classes' => [ 'input-text', 'qty', 'text', 'jet-wc-product-qty' ] ], $product, false );
		}

		// Handle variable product variations display
		if ( $product->is_type( 'variable' ) && 'dropdown' === $variation_type ) {

			$available_variations = $product->get_available_variations();
			$attributes           = $product->get_variation_attributes();
			$selected_attributes  = $product->get_default_attributes();
			$attribute_keys       = array_keys( $attributes );
			$variations_json      = wp_json_encode( $available_variations );
			$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

			if ( empty( $available_variations ) && false !== $available_variations ) {
				$content .= '<p class="stock out-of-stock">' . esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'jet-wc-product-table' ) ) ) . '</p>';
			} else {

				$content .= sprintf(
					'<div class="jet-wc-product-table-variation" data-product_id="%1$s" data-product_variations="%2$s">',
					absint( $product->get_id() ),
					$variations_attr
				);

				$content .= '<div class="jet-wc-product-table-variation__attrs">';

				foreach ( $attributes as $attribute_name => $options ) {

					ob_start();

					wc_dropdown_variation_attribute_options( [
						'options'          => $options,
						'attribute'        => $attribute_name,
						'product'          => $product,
						'show_option_none' => wc_attribute_label( $attribute_name ),
						'class'            => 'jet-wc-product-table-variation-select',
					] );

					$content .= ob_get_clean();

				}

				$content .= '</div>';

				$content .= '<div class="jet-wc-product-table-variation__controls">';

				$btn_args = [
					'quantity'              => 1,
					'class'                 => implode(
						' ',
						array_filter( [
							'button',
							wc_wp_theme_get_element_class_name( 'button' ), // escaped in the template.
							'product_type_' . $product->get_type(),
							$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
							$product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
							'jet-wc-product-table-button-disabled',
						] )
					),
					'aria-describedby_text' => $product->add_to_cart_aria_describedby(),
					'attributes'            => array(
						'data-jet_wc_cart' => true,
						'data-add_to_cart' => $product->get_id(),
						'data-product_sku' => $product->get_sku(),
						'aria-label'       => $product->add_to_cart_description(),
						'rel'              => 'nofollow',
					),
				];

				// Render quantity picker if enabled
				if ( $show_qty ) {
					$content .= woocommerce_quantity_input( [ 'classes' => [ 'input-text', 'qty', 'text', 'jet-wc-product-qty' ] ], $product, false );
				}

				$content .= apply_filters(
					'woocommerce_loop_add_to_cart_link', // WPCS: XSS ok.
					sprintf(
						'<a href="%1$s" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%2$s" data-quantity="%3$s" class="%4$s" %5$s>%6$s</a>',
						esc_url( $product->add_to_cart_url() ),
						esc_attr( $product->get_id() ),
						esc_attr( isset( $btn_args['quantity'] ) ? $btn_args['quantity'] : 1 ),
						esc_attr( isset( $btn_args['class'] ) ? $btn_args['class'] : 'button' ),
						isset( $btn_args['attributes'] ) ? wc_implode_html_attributes( $btn_args['attributes'] ) : '',
						esc_html( $product->single_add_to_cart_text() )
					),
					$product,
					$btn_args
				);

				$content .= sprintf(
					'<a href="%1$s" style="display:none;" class="added_to_cart wc-forward" title="%2$s">%2$s</a>',
					apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
					esc_html__( 'View cart', 'jet-wc-product-table' )
				);

				$content .= '<div class="jet-wc-product-table-variation-price"> &nbsp;</div>';

				$content .= '</div>';

			}
		} else {

			ob_start();
			woocommerce_template_loop_add_to_cart();
			$content .= ob_get_clean();

		}

		if ( ! $product->is_type( 'variable' ) || 'dropdown' !== $variation_type ) {
			$content .= '</div>';
		}

		return $content;
	}

	/**
	 * Enqueue column assets.
	 *
	 * @param  array $attrs Column attributes where assets shoud be enqueued.
	 * @return bool
	 */
	public function enqueue_column_assets( $attrs = [] ) { // phpcs:ignore

		$show_qty       = ! empty( $attrs['quantities'] ) ? $attrs['quantities'] : false;
		$show_qty       = filter_var( $show_qty, FILTER_VALIDATE_BOOLEAN );
		$variation_type = ! empty( $attrs['variations'] ) ? $attrs['variations'] : 'dropdown';

		if ( $show_qty || 'dropdown' === $variation_type ) {
			Plugin::instance()->assets->enqueue_script( 'jet-wc-product-actions' );
		}

		return true;
	}

	/**
	 * Provides additional settings specific to the Product Actions column.
	 *
	 * @return array Additional settings.
	 */
	public function additional_settings() {
		return [
			'quantities' => [
				'label'       => __( 'Quantities', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'description' => 'Show a quantity picker for each product.',
				'default'     => false,
			],
			'variations' => [
				'label'       => __( 'Variations', 'jet-wc-product-table' ),
				'type'        => 'select',
				'description' => 'How to display the options for variable products.',
				'default'     => 'dropdown',
				'options'     => [
					[
						'value' => 'dropdown',
						'label' => 'Select attributes and Add to Cart button',
					],
					[
						'value' => 'default-button',
						'label' => 'Default Read More button linking to the product page (works only for variable products)',
					],
				],
			],
		];
	}
}
