<aside id="betterdocs-sidebar-right"  class="betterdocs-sidebar betterdocs-full-sidebar-right right-sidebar-toc-wrap">
	<div data-simplebar class="layout3-toc-container right-sidebar-toc-container">
		<?php
			$hierarchy     = betterdocs()->settings->get( 'toc_hierarchy' );
			$list_number   = betterdocs()->settings->get( 'toc_list_number' );
			$supported_tag = betterdocs()->settings->get( 'supported_heading_tag' );
			$htags         = $supported_tag ? implode( ',', $supported_tag ) : '';

			$attributes = betterdocs()->template_helper->get_html_attributes(
				[
					'htags'                 => "{$htags}",
					'hierarchy'             => "{$hierarchy}",
					'list_number'           => "{$list_number}",
					'collapsible_on_mobile' => false
				]
			);

			echo do_shortcode( '[betterdocs_toc ' . $attributes . ']' );

			if ( isset( $social_share ) && $social_share ) {
				echo betterdocs()->views->get( 'templates/parts/social-2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( isset( $feedback ) && $feedback ) {
				$reaction_text = betterdocs()->customizer->defaults->get( 'betterdocs_post_reactions_text_2' );
				// Collect reaction values and icons
				$reactions_data = [
					'happy'       => 'betterdocs_post_reactions_happy',
					'happy_icon'  => 'betterdocs_post_reactions_happy_icon',
					'normal'      => 'betterdocs_post_reactions_normal',
					'normal_icon' => 'betterdocs_post_reactions_normal_icon',
					'sad'         => 'betterdocs_post_reactions_sad',
					'sad_icon'    => 'betterdocs_post_reactions_sad_icon'
				];

				foreach ( $reactions_data as $key => $theme_mod ) {
					$value = betterdocs()->customizer->defaults->get( $theme_mod );
					if ( $value ) {
						$args[ $key ] = $value;
					} else {
						$args[ $key ] = false;
					}
				}

				// Build the attribute string for the shortcode
				$attr = '';
				foreach ( $args as $key => $value ) {
					$attr .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
				}
				echo do_shortcode( '[betterdocs_article_reactions text="' . $reaction_text . '" layout="layout-3"' . $attr . ']' );
			}
			?>
	</div>
</aside>
