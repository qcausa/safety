<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\Types\Post;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class WooCommerce extends Post {
	public static function get_shortcode_types( $context ) {
		return [
			'woo',
		];
	}

	public function evaluate() {
		$this->ensure_plugin_dependencies( [ 'woocommerce' ] );
		return parent::evaluate();
	}

	protected function is_appropriate_type( $id ) {
		$post_type = get_post_type( $id );
		return $post_type === 'product' || $post_type === 'product_variation';
	}

	const POST_METAS_ALIAS = [
		'ID'      => 'ID',
		'id'      => 'ID',
		'date'    => 'post_date',
		'author'  => 'post_author',
	];

	const WOO_FIELDS_FUNCTIONS = [
		'average-rating' => 'get_average_rating',
		'average_rating' => 'get_average_rating',
		'backorders' => 'get_backorders',
		'backorders-allowed' => 'get_backorders_allowed',
		'backorders_allowed' => 'get_backorders_allowed',
		'catalog-visibility' => 'get_catalog_visibility',
		'catalog_visibility' => 'get_catalog_visibility',
		'category-ids' => 'get_category_ids',
		'category_ids' => 'get_category_ids',
		'cross-sells' => 'get_cross_sell_ids',
		'cross_sells' => 'get_cross_sell_ids',
		'crosssells' => 'get_cross_sell_ids',
		'depth' => 'get_depth',
		'description' => 'get_description',
		'dimensions' => 'get_dimensions',
		'download_expiry' => 'get_download_expiry',
		'downloadable' => 'get_downloadable',
		'downloadable_type' => 'get_downloadable_type',
		'featured' => 'get_featured',
		'gallery-image-ids' => 'get_gallery_image_ids',
		'gallery_image_ids' => 'get_gallery_image_ids',
		'height' => 'get_height',
		'image' => 'get_image',
		'image-id' => 'get_image_id',
		'image_id' => 'get_image_id',
		'is-in-stock' => 'is_in_stock',
		'is-on-sale' => 'is_on_sale',
		'is_in_stock' => 'is_in_stock',
		'is_on_sale' => 'is_on_sale',
		'length' => 'get_length',
		'manage-stock' => 'get_manage_stock',
		'manage_stock' => 'get_manage_stock',
		'name' => 'get_name',
		'price' => 'get_price',
		'price-html' => 'get_price_html',
		'rating-counts' => 'get_rating_counts',
		'rating_counts' => 'get_rating_counts',
		'regular-price' => 'get_regular_price',
		'regular_price' => 'get_regular_price',
		'review-count' => 'get_review_count',
		'reviews-allowed' => 'get_reviews_allowed',
		'reviews_allowed' => 'get_reviews_allowed',
		'sale-price' => 'get_sale_price',
		'sale_price' => 'get_sale_price',
		'shipping-class-id' => 'get_shipping_class_id',
		'shipping_class_id' => 'get_shipping_class_id',
		'short-description' => 'get_short_description',
		'short_description' => 'get_short_description',
		'sku' => 'get_sku',
		'status' => 'get_status',
		'stock-quantity' => 'get_stock_quantity',
		'stock_quantity' => 'get_stock_quantity',
		'stock-status' => 'get_stock_status',
		'stock_status' => 'get_stock_status',
		'tag-ids' => 'get_tag_ids',
		'tag_ids' => 'get_tag_ids',
		'tax-class' => 'get_tax_class',
		'tax-status' => 'get_tax_status',
		'tax_class' => 'get_tax_class',
		'tax_status' => 'get_tax_status',
		'type' => 'get_type',
		'upsells' => 'get_upsell_ids',
		'variations' => 'get_children',
		'weight' => 'get_weight',
		'width' => 'get_width',
	];

	const WOO_FIELDS_CUSTOM_FUNCTIONS = [
		'attributes' => [ 'get_attributes' ],
		'date-created'        => [ 'get_date_from_wcdatetime_object', 'get_date_created' ],
		'date_created'        => [ 'get_date_from_wcdatetime_object', 'get_date_created' ],
		'date-on-sale-from'   => [ 'get_date_from_wcdatetime_object', 'get_date_on_sale_from' ],
		'date_on_sale_from'   => [ 'get_date_from_wcdatetime_object', 'get_date_on_sale_from' ],
		'date-on-sale-to'     => [ 'get_date_from_wcdatetime_object', 'get_date_on_sale_to' ],
		'date_on_sale_to'     => [ 'get_date_from_wcdatetime_object', 'get_date_on_sale_to' ],
		'date-modified'       => [ 'get_date_from_wcdatetime_object', 'get_date_modified' ],
		'date_modified'       => [ 'get_date_from_wcdatetime_object', 'get_date_modified' ],
	];

	protected function handle_special_meta( $meta, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( isset( static::WOO_FIELDS_FUNCTIONS[ $meta ] ) ) {
			$alias = static::WOO_FIELDS_FUNCTIONS[ $meta ];
			return [ call_user_func( [ $product, $alias ] ) ];
		}

		if ( isset( static::WOO_FIELDS_CUSTOM_FUNCTIONS[ $meta ] ) ) {
			$alias = static::WOO_FIELDS_CUSTOM_FUNCTIONS[ $meta ];
			if ( isset( $alias[1] ) ) {
				return [ call_user_func( __CLASS__ . '::' . $alias[0], $product, $alias[1] ) ];
			} else {
				return [ call_user_func( __CLASS__ . '::' . $alias[0], $product ) ];
			}
		}

		return false;
	}

	protected static function get_date_from_wcdatetime_object( $product, $callback ) {
		$date = $product->{$callback}();

		if ( $date instanceof \WC_DateTime ) {
			return $date->format( 'Y-m-d H:i:s' );
		}

		return null;
	}

	protected static function get_attributes( $product ) {
		$attributes     = $product->get_attributes();
		$all_attributes = [];

		foreach ( $attributes as $attribute_name => $attribute ) {
			if ( $attribute->is_taxonomy() ) {
				$terms  = wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'names' ) );
				$values = $terms;
			} else {
				$values = $attribute->get_options();
			}

			$all_attributes[ wc_attribute_label( $attribute_name ) ] = $values;
		}

		return $all_attributes;
	}
}
