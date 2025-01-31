<?php

/**
 * Shortcodes
 *
 * @package BuddyBoss Child
 */

/**
 * Shortcode to Display Posts by Category and Tag
 *
 * Usage: [posts_by_category_and_tag term_id="123"]
 */
function display_posts_by_category_and_tag_shortcode($atts)
{
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'term_id' => '', // Passed category term_id
    ), $atts);

    // Debugging with BugFu
    BugFu::log($atts);

    // Ensure term_id is valid
    if (empty($atts['term_id'])) {
        return 'No downloads found for this category.';
    }

    // Query downloads with 'dlm_download_category' = term_id
    $query_args = array(
        'post_type'      => 'dlm_download', // Download Monitor post type
        'tax_query'      => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'dlm_download_category', // Taxonomy for downloads
                'field'    => 'term_id',
                'terms'    => $atts['term_id'],       // Term ID passed to the shortcode
            ),
            array(
                'taxonomy' => 'dlm_download_tag',      // Taxonomy for tags
                'field'    => 'term_id',
                'terms'    => 217,                      // Exclude tag with ID 217
                'operator' => 'NOT IN',                // Exclude posts with this tag
            ),
        ),
        'posts_per_page' => -1,                           // Retrieve all matching posts
        'orderby'        => 'menu_order',
        'order'          => 'ASC',                       // or 'DESC'
    );

    $query = new WP_Query($query_args);

    // Check if any posts were found
    if (!$query->have_posts()) {
        return 'No downloads found for this category.';
    }

    // Group downloads by tags
    $grouped_downloads = array();
    $has_tags = false;

    foreach ($query->posts as $post) {
        $tags = get_the_terms($post->ID, 'dlm_download_tag'); // Get tags for the post

        if (!empty($tags) && !is_wp_error($tags)) {
            $tag_name = $tags[0]->name; // Group by first tag
            $has_tags = true;
        } else {
            $tag_name = 'Untagged';
        }

        $grouped_downloads[$tag_name][] = $post;
    }

    // Start output buffering
    ob_start();
?>
    <div class="downloads-by-category">
        <?php
        // Display untagged downloads first
        if (isset($grouped_downloads['Untagged'])) : ?>
            <ul class="downloads-list">
                <?php foreach ($grouped_downloads['Untagged'] as $post) : ?>
                    <li class="download-item">
                        <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                            <?php
                            // Display the large featured image
                            if (has_post_thumbnail($post->ID)) {
                                echo get_the_post_thumbnail($post->ID, 'large', array('class' => 'download-thumbnail-large'));
                            }
                            ?>
                            <span class="download-title"><?php echo esc_html($post->post_title); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php
        // Display tagged downloads in accordions
        foreach ($grouped_downloads as $tag_name => $posts) :
            if ($tag_name === 'Untagged') {
                continue; // Skip 'Untagged' group as we already displayed it
            }
        ?>
            <div class="accordion-item">
                <h5 class="accordion-trigger" style="cursor: pointer; margin: 0;"
                    onclick="toggleAccordion(this)">
                    <?php echo esc_html($tag_name); ?>
                    <span style="font-family: 'Nunito Sans'; display: block; font-size: .8rem; text-transform: lowercase;">(Click to expand)</span>
                </h5>
                <div class="accordion-body" style="display: none;">
                    <ul class="downloads-list">
                        <?php foreach ($posts as $post) : ?>
                            <li class="download-item">
                                <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                                    <?php
                                    if (has_post_thumbnail($post->ID)) {
                                        echo get_the_post_thumbnail($post->ID, 'large', array('class' => 'download-thumbnail-large'));
                                    }
                                    ?>
                                    <span class="download-title"><?php echo esc_html($post->post_title); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Initialize Masonry (ensure this runs on page load)
        var $grid = $('#resource-loop').masonry();

        // Simple accordion toggle functionality
        function toggleAccordion(trigger) {
            var content = trigger.nextElementSibling;

            if (content.style.display === "none" || content.style.display === "") {
                content.style.display = "block";
            } else {
                content.style.display = "none";
            }

            // Trigger Masonry re-layout after accordion toggles
            setTimeout(function() {
                $grid.masonry('layout');
            }, 300); // Add slight delay to ensure content is fully visible
        }
    </script>
<?php
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('posts_by_category_and_tag', 'display_posts_by_category_and_tag_shortcode');

/**
 * Shortcode to Display Category Image
 *
 * Usage: [category_image term_id="123"]
 */
function display_category_image_shortcode($atts)
{
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'term_id' => '', // Term ID for the category
    ), $atts);

    // Check if Pods is installed
    if (!class_exists('Pods')) {
        return '<img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Blank Image" style="max-width:100%; height:auto;">';
    }

    // Get the term
    $term = get_term($atts['term_id'], 'dlm_download_category');

    // Validate the term
    if (!$term || is_wp_error($term)) {
        return '<img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Blank Image" style="max-width:100%; height:auto;">';
    }

    // Get the category image using Pods
    $image_id = get_term_meta($atts['term_id'], 'dlm_download_category_image', true);
    BugFu::log($image_id);

    // Validate the image ID
    if (empty($image_id)) {
        return '';
    }

    // Get the image URL
    $image_data = wp_get_attachment_image_src($image_id, 'large'); // Specify the desired size
    $image_url = $image_data ? $image_data[0] : '';

    if (!$image_url) {
        return '';
    }

    // Output the image
    ob_start();
?>
    <div class="category-image">
        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>" style="width:100%; height:auto;">
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('category_image', 'display_category_image_shortcode');
