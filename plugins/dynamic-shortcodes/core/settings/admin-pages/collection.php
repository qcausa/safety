<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Settings\AdminPages;

use DynamicShortcodes\Plugin;

class Collection {

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$examples = Plugin::instance()->collection_manager->get_data();

		if ( empty( $examples ) ) {
			echo '<p>' . esc_html__( 'No examples.', 'dynamic-shortcodes' ) . '</p>';
			return;
		}

		$placeholder_colors = [
			'#0073aa', // Blue
			'#4CAF50', // Green
			'#D32F2F', // Red
			'#9C27B0', // Purple
			'#FF9800', // Orange
			'#00BCD4', // Cyan
			'#E91E63', // Pink
			'#9E9E9E', // Grey
			'#607D8B',  // Slate Blue
		];

		$search_tags = Plugin::instance()->collection_manager->get_search_tags();

		echo '<div class="wrap dynamic-shortcodes-demo">';

		$title = esc_html( get_admin_page_title() );
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<h1>$title</h1>";

		echo '<div class="content-wrapper">';
		echo '<div class="dynamic-shortcodes-demo-tab">';
		echo '<div class="tag-search-container">';
		echo '<input type="text" id="tagSearch" placeholder="Search by tag...">';
		echo '<div class="quick-search-tags">';
		foreach ( $search_tags as $tag => $size ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<button class='quick-search-tag tag-{$size}' data-tag='{$tag}'>{$tag}</button>";
		}
		echo '</div>';

		echo '</div>';

		echo '<div class="dynamic-shortcodes-demo">';

		echo '<table>';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'What', 'dynamic-shortcodes' ) . '</th>';
		echo '<th>' . esc_html__( 'Dynamic Shortcode', 'dynamic-shortcodes' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		foreach ( $examples as $example ) {
			$tags_string = implode( ', ', $example['tags'] );
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<tr data-tags='{$tags_string}'>";
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<td class="what">' . $example['description'] . '</td>';

			$displayed_shortcode = htmlspecialchars( $example['shortcode'], ENT_QUOTES, 'UTF-8' );
			$notes               = '';

			if ( ! empty( $example['placeholders'] ) ) {
				$counter = 0;
				$notes  .= '<table class="placeholders-table" style="margin-top: 20px;"><tbody>';
				foreach ( $example['placeholders'] as $placeholder => $description ) {
					$color                   = $placeholder_colors[ $counter % count( $placeholder_colors ) ];
					$highlighted_placeholder = "<span class='placeholder' style='color: $color;'>" . esc_html( $placeholder ) . '</span>';
					$displayed_shortcode     = str_replace( $placeholder, $highlighted_placeholder, $displayed_shortcode );

					$notes .= "<tr><td style='text-align: right; font-weight: bold; color: $color;'>"
								  . esc_html( $placeholder ) . '</td>'
								  . '<td>' . esc_html( $description ) . '</td></tr>';
					++$counter;
				}
				$notes .= '</tbody></table>';
			}
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<td class="code"><code>' . $displayed_shortcode . '</code>';
			echo '<button class="copy-button">' . esc_html__( 'Copy', 'dynamic-shortcodes' ) . '</button>';

			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $notes;
			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}
}
