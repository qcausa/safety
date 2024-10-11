<div class="betterdocs-author-date">
    <div class="betterdocs-author">
        <?php
            // Get the author's ID
            $author_id = get_the_author_meta(get_the_ID());

            // Get the author's avatar with a specified size
            $avatar_size = 40;
            $author_avatar = get_avatar($author_id, $avatar_size);

            echo '<div class="author-avatar">' . $author_avatar . '</div>';
            echo '<span>' . get_the_author_meta('display_name', $author_id) . '</span>';
        ?>
    </div>
    <?php betterdocs()->views->get( 'template-parts/update-date' ); ?>
</div>
