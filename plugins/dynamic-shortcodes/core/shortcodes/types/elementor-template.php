<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Elementor\Loop_Dynamic_CSS;

class ElementorTemplate extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'elementor-template',
		];
	}

	public function evaluate() {
		$this->should_not_sanitize();
		$this->arity_check( 1 );
		$this->init_keyargs( [] );
		$post_id     = get_the_ID();
		$template_id = $this->get_arg( 0, 'numeric' );
		$document    = \Elementor\Plugin::instance()->documents->get( $template_id );
		if ( ! $document ) {
			$this->evaluation_error( 'template not found' );
		}
		if ( post_password_required( $template_id ) ) {
			return '';
		}
		if ( ! method_exists( $document, 'print_content' ) ) {
			$msg = esc_html__( 'This template is currently not supported. Loop Item and Section template are known to work.', 'dynamic-shortcodes' );
			$this->evaluation_error( $msg );
		}
		ob_start();
		Loop_Dynamic_CSS::print_dynamic_css( $post_id, $template_id );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<div class='e-loop-item-$post_id'>";
		$document->print_content();
		echo '</div>';
		return ob_get_clean();
	}
}
