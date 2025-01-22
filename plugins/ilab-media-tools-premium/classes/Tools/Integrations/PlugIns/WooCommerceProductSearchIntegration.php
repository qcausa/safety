<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class WooCommerceProductSearchIntegration {
	public function __construct() {
		add_action('wp_loaded', function() {
			global $wp_filter;

			$imageDownsize = $wp_filter['image_downsize'];
			foreach($imageDownsize->callbacks as $priority => $hooks) {
				foreach($hooks as $hook => $hookData) {
					if (strpos($hook, 'WooCommerce_Product_Search_Thumbnail') === 0) {
						$imageDownsize->remove_filter($hook, $hookData['function'], $priority);
						return;
					}
				}
			}
		});
	}
}
