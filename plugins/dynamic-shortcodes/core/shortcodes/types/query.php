<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;

class Query extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [ 'query' ];
	}

	public function evaluate() {
		$this->init_keyargs( [], [], [], true );
		$this->arity_check( 1, 1 );
		$this->ensure_all_privileges();

		$type = $this->get_arg( 0, 'string' );

		$args = [];

		if ( $type === 'woo-orders' || $type === 'woo-products' ) {
			$args['return'] = 'ids';
		} else {
			$args['fields'] = 'ids';
		}

		foreach ( $this->keyargs as $k => $v ) {
			if ( ! BaseShortcode::is_keyarg_special( $k ) ) {
				$k = str_replace( '-', '_', $k );
				if ( $v === true ) {
					// true is the empty keyarg:
					$v = null;
				} else {
					$v = $this->unit_interpreter->evaluate_value( $v, $this->interpreter_env );
				}
				$args[ $k ] = $v;
			}
		}
		try {
			switch ( $type ) {
				case 'users':
					$res = get_users( $args );
					break;
				case 'posts':
					$res = get_posts( $args );
					break;
				case 'terms':
					$res = get_terms( $args );
					break;
				case 'woo-products':
					$this->ensure_plugin_dependencies( [ 'woocommerce' ] );
					$res = wc_get_products( $args );
					break;
				case 'woo-orders':
					$this->ensure_plugin_dependencies( [ 'woocommerce' ] );
					$res = wc_get_orders( $args );
					break;
				default:
					$this->evaluation_error(
						sprintf(
							// translators: %s is a user provided string.
							esc_html__( 'The query `%s` is not recognized/supported', 'dynamic-shortcodes' ),
							esc_html( $type )
						)
					);
			}
		} catch ( \Exception $e ) {
			$this->evaluation_error( $e->getMessage() );
		}
		return $res;
	}
}
