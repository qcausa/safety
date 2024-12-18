<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class WooCommerce extends Post {

	const PREDEFINED_FIELDS = [
		[
			'args' => [
				'title',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'ID',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'featured-image-id',
			],
			'force_result_types' => [ 'media-id' ],
		],
		[
			'args' => [
				'date',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'author',
			],
			'force_result_types' => [ 'user-id' ],
		],
		[
			'args' => [
				'permalink',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'type',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'status',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'date-created',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'date-modified',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'catalog-visibility',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'short-description',
			],
			'force_result_types' => [ 'string' ],
		],
		[
			'args' => [
				'price',
			],
			'force_result_types' => [ 'int' ],
		],
		[
			'args' => [
				'regular-price',
			],
			'force_result_types' => [ 'int' ],
		],
		[
			'args' => [
				'sale-price',
			],
			'force_result_types' => [ 'int' ],
		],
		[
			'args' => [
				'price-html',
			],
			'force_result_types' => [],
		],
		[
			'args' => [
				'date-on-sale-from',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'date-on-sale-to',
			],
			'force_result_types' => [ 'date' ],
		],
		[
			'args' => [
				'tax-status',
			],
		],
		[
			'args' => [
				'tax-class',
			],
		],
		[
			'args' => [
				'stock-quantity',
			],
		],
		[
			'args' => [
				'stock-status',
			],
		],
		[
			'args' => [
				'manage-stock',
			],
		],
		[
			'args' => [
				'shipping-class-id',
			],
		],
		[
			'args' => [
				'image-id',
			],
			'force_result_types' => [ 'media-id' ],
		],
		[
			'args' => [
				'gallery-image-ids',
			],
		],
		[
			'args' => [
				'category-ids',
			],
			'force_result_types' => [ 'array-term-id' ],
		],
		[
			'args' => [
				'tag-ids',
			],
		],
		[
			'args' => [
				'reviews-allowed',
			],
		],
		[
			'args' => [
				'rating-counts',
			],
		],
		[
			'args' => [
				'average-rating',
			],
		],
		[
			'args' => [
				'attributes',
			],
		],
		[
			'args' => [
				'variations',
			],
		],
		[
			'args' => [
				'upsells',
			],
		],
		[
			'args' => [
				'cross-sells',
			],
		],
	];

	protected static function get_contextual_fields_list() {
		return array_merge( parent::get_contextual_fields_list(), array_keys( static::get_current_woo_attributes() ) );
	}

	protected static function get_current_woo_attributes() {
		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return [];
		}

		$product = wc_get_product( $product_id );
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return [];
		}

		$attributes     = $product->get_attributes();
		$attribute_keys = [];

		if ( ! is_array( $attributes ) ) {
			return [];
		}

		foreach ( $attributes as $attribute ) {
			$data                            = $attribute->get_data();
			$attribute_keys[ $data['name'] ] = '';
		}

		return $attribute_keys;
	}
}
