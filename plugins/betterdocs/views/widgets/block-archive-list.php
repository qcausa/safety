<?php
	$current_category = get_queried_object();

if ( $current_category != null && $layout == 'layout-1' ) :

	?>
			<div class='betterdocs-content-area block-archive-list <?php echo esc_attr( $blockId ); ?>'>
				<div class="betterdocs-content-inner-area">
							<div class="betterdocs-entry-title">
							<?php
								echo wp_sprintf(
									'<%1$s class="betterdocs-entry-heading">%2$s</%1$s>',
									$title_tag, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$current_category->name //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								);
								echo wp_sprintf( '<p>%s</p>', wp_kses_post( $current_category->description ) );
							?>
							</div>
							<div class="betterdocs-entry-body betterdocs-taxonomy-doc-category">
							<?php $view_object->get( 'widgets/archive-list' ); ?>
							</div>
				</div>
			</div>
		<?php
	elseif ( $current_category != null && $layout == 'layout-2' ) :
		do_action( 'archive_handbook_list' );
	elseif ( $current_category != null && $layout == 'layout-3' ) :
		$post_query = new WP_Query( $query_args );
		echo '<div class="' . $blockId . ' doc-category-layout-7">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				betterdocs()->views->get(
					'template-parts/archive-doc-list',
					[
						'current_category' => $current_category,
						'post_query'       => $post_query
					]
				);

		if ( $pagination ) {
			$total_pages = ceil( ( isset( $post_query->found_posts ) ? $post_query->found_posts : 0 ) / 10 );
			betterdocs()->views->get(
				'template-parts/pagination',
				[
					'total_pages'  => $total_pages,
					'link'         => get_term_link( $current_category, 'doc_category' ),
					'current_page' => $page,
					'template'     => 'doc_category'
				]
			);
		}
		echo '</div>';
	endif;
	?>
