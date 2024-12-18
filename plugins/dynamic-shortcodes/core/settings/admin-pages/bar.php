<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Settings\AdminPages;

class Bar {
	public function __construct() {
		if ( ! is_admin() && current_user_can( 'manage_options' ) ) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					$js_path = DYNAMIC_SHORTCODES_URL . 'assets/js/administrator-frontend.js';
					wp_enqueue_script(
						'admin-frontend-js',
						$js_path,
						[ 'wp-util' ],
						DYNAMIC_SHORTCODES_VERSION,
						[
							'in_footer' => true,
						]
					);
				}
			);
			add_action( 'admin_bar_menu', [ $this, 'add_admin_bar' ], 999 );
		}
	}

	public function add_admin_bar( $admin_bar ) {
		$admin_bar->add_node(
			[
				'id' => 'dsh-bar-top',
				'title' => esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' ),
			]
		);
		$admin_bar->add_node(
			[
				'id' => 'dsh-clear-cache-bar-button',
				'title' => esc_html__( 'Clear the Shortcodes Cache for this Page', 'dynamic-shortcodes' ),
				'href' => '#',
				'parent' => 'dsh-bar-top',
			]
		);
		$admin_bar->add_node(
			[
				'id' => 'dsh-power-bar-button',
				'title' => esc_html__( 'Edit Power Shortcodes', 'dynamic-shortcodes' ),
				'href' => admin_url( '/admin.php?page=dynamic-shortcodes-power' ),
				'parent' => 'dsh-bar-top',
			]
		);
		$id       = get_the_ID();
		$demo_url = admin_url( '/admin.php?page=dynamic-shortcodes-demo' );
		if ( $id > 0 ) {
			$demo_url .= '&demos_post_id=' . $id;
		}
		$admin_bar->add_node(
			[
				'id' => 'dsh-demo-bar-button',
				'title' => esc_html__( 'Demo Shortcodes', 'dynamic-shortcodes' ),
				'href' => $demo_url,
				'parent' => 'dsh-bar-top',
			]
		);
	}
}
