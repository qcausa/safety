<div class="betterdocs-author-date">
	<div class="betterdocs-author">
		<?php
			// Get the author's ID
			$author_id = get_post_field( 'post_author', get_the_ID() );

			// Get the author's avatar with a specified size
			$avatar_size   = 40;
			$author_avatar = get_avatar( $author_id, $avatar_size );

			echo '<div class="author-avatar">' . $author_avatar . '</div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span>' . get_the_author_meta( 'display_name', $author_id ) . '</span>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
	<?php
	if ( isset( $updated_date ) && $updated_date == true ) {
		betterdocs()->views->get( 'template-parts/update-date' );
	}
	?>
</div>
