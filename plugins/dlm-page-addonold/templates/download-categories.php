<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

global $dlm_page_addon;
$template_handler = new DLM_Template_Handler();

// Group downloads by tags
$grouped_downloads = array();
$ungrouped_downloads = array();
$has_tags = false; // Flag to check if tags exist

foreach ( $downloads as $download ) {
    // Fetch tags for the current download
    $tags = wp_get_post_terms( $download->post->ID, 'dlm_download_tag' ); // Use 'dlm_download_tag' for custom taxonomy

    if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
        $has_tags = true; // At least one tag is found
        foreach ( $tags as $tag ) {
            // Group by tag ID
            $grouped_downloads[ $tag->term_id ]['name'] = $tag->name;
            $grouped_downloads[ $tag->term_id ]['downloads'][] = $download;
        }
    } else {
        // No tags, add to ungrouped
        $ungrouped_downloads[] = $download;
    }
}

// Sort the grouped downloads by tag name (alphanumeric/numeric order)
uksort( $grouped_downloads, function( $tag_id_a, $tag_id_b ) use ( $grouped_downloads ) {
    $name_a = $grouped_downloads[ $tag_id_a ]['name'];
    $name_b = $grouped_downloads[ $tag_id_b ]['name'];
    return strnatcmp( $name_a, $name_b ); // Natural order string comparison
});
?>


<div class="download_category download_group">
    <h3>
        <a href="<?php echo $dlm_page_addon->get_category_link( $category ); ?>">
            <?php echo esc_html( $category->name ); ?>
            <?php if ( $category->count ) : ?>
                (<?php echo esc_html( $category->count ); ?>)
            <?php endif; ?>
        </a>
    </h3>

    <?php if ( $has_tags ) : // If at least one tag is detected, group downloads by tags ?>
        <?php foreach ( $grouped_downloads as $tag_id => $tag_data ) : ?>
            <h4 style="margin:0px;padding: 0.5em 1em;">
                <?php echo esc_html( $tag_data['name'] ); ?>
            </h4>
            <ol style="padding: 0.5em 2em 0.5em;">
                <?php foreach ( $tag_data['downloads'] as $download ) : ?>
                    <li>
                        <?php
                        $template_handler->get_template_part(
                            'content-download',
                            $format,
                            $dlm_page_addon->plugin_path() . 'templates/',
                            array( 'dlm_download' => $download, 'direct_download' => $direct_download )
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endforeach; ?>

        <?php if ( ! empty( $ungrouped_downloads ) ) : ?>
            <h4 style="margin:0px;padding: 0.5em 1em;"><?php _e( 'Other Downloads', 'text-domain' ); ?></h4>
            <ol style="padding: 0.5em 2em 0.5em;">
                <?php foreach ( $ungrouped_downloads as $download ) : ?>
                    <li>
                        <?php
                        $template_handler->get_template_part(
                            'content-download',
                            $format,
                            $dlm_page_addon->plugin_path() . 'templates/',
                            array( 'dlm_download' => $download, 'direct_download' => $direct_download )
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

    <?php else : // If no tags are detected, list all downloads normally ?>
        <ol style="padding: 0.5em 2em 0.5em;">
            <?php foreach ( $downloads as $download ) : ?>
                <li>
                    <?php
                    $template_handler->get_template_part(
                        'content-download',
                        $format,
                        $dlm_page_addon->plugin_path() . 'templates/',
                        array( 'dlm_download' => $download, 'direct_download' => $direct_download )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
