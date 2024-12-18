<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Plugin;

class Demo extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'demo',
		];
	}

	/**
	 * Get Type error message
	 *
	 * @param string $type
	 * @return string
	 */
	public static function get_type_error_msg( $type ) {
		// translators: %1$s is the shortcode name
		$content        = sprintf( esc_html__( 'Not examples for %1$s. These are all the available demos:', 'dynamic-shortcodes' ), $type );
		$types_accepted = Plugin::instance()->demo_manager->get_types_accepted();

		$content .= '<ul>';
		foreach ( $types_accepted as $type ) {
			$content .= '<li>{demo:' . $type . '}</li>';
		}
		$content .= '</ul>';

		return $content;
	}

	public function evaluate() {
		$this->should_not_sanitize();
		$this->arity_check( 0, 1 );
		$this->init_keyargs( [] );
		$type = $this->get_arg( 0, 'string' );

		// Not visible if not administrator
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'dynamic-shortcodes-demo' );
		wp_enqueue_style( 'dynamic-shortcodes-demo' );

		if ( 'all' === $type ) {
			return Plugin::instance()->demo_manager->render_all_types();
		}

		$types_accepted = Plugin::instance()->demo_manager->get_types_accepted();
		if ( ! in_array( $type, $types_accepted, true ) ) {
			$msg = self::get_type_error_msg( $type );
			$this->evaluation_error( $msg );
		}
		$demo_manager  = Plugin::instance()->demo_manager;
		$check_depends = $demo_manager->check_plugin_depends( $type );
		if ( $check_depends ) {
			// translators: %1$s is a plugin name.
			$msg = esc_html__( 'This demo requires the plugin %1$s.', 'dynamic-shortcodes' );
			return sprintf( $msg, '<strong>' . $check_depends . '</strong>' );
		}
		return $demo_manager->render( $type );
	}
}
