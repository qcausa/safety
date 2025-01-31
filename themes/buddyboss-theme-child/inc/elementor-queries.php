<?php

/**
 * Elementor Custom Queries
 *
 * @package BuddyBoss Child
 */

/**
 * Elementor Query: Featured Posts
 */
add_action('elementor/query/featured_posts', function ($query) {
    // Add the "featured" meta query to the query
    $meta_query = array(
        array(
            'key'     => '_featured', // Meta key for featured status (adjust this if your site uses a different meta key)
            'value'   => 'yes',        // Value that indicates the post is featured
            'compare' => '='
        )
    );

    // Merge any existing meta query with the new condition
    $existing_meta_query = $query->get('meta_query');

    if (!empty($existing_meta_query)) {
        $meta_query = array_merge($existing_meta_query, $meta_query);
    }

    $query->set('meta_query', $meta_query);

    // Optional: Order by date or other criteria
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
});

/**
 * Elementor Query: BuddyPress Activity
 */
function custom_elementor_query_buddypress_activity($query)
{
    global $wpdb;

    // Fetch BuddyPress activity data
    $activity_table = $wpdb->prefix . 'bp_activity';
    $results = $wpdb->get_results("
        SELECT id, user_id, content, date_recorded
        FROM {$activity_table}
        WHERE type = 'last_activity'
        ORDER BY date_recorded DESC
        LIMIT 10
    ");

    // If no results, stop here
    if (empty($results)) {
        $query->set('post__in', array(0));
        return;
    }

    // Directly override query results with custom posts
    add_filter('posts_results', function ($posts, $query_instance) use ($results) {
        if ($query_instance->get('post_type') !== 'bp_activity_post') {
            return $posts;
        }

        // Inject fake posts directly
        $fake_posts = array();
        foreach ($results as $index => $activity) {
            $post = new stdClass();
            $post->ID = $index + 1000; // Ensure unique ID
            $post->post_author = $activity->user_id;
            $post->post_date = $activity->date_recorded;
            $post->post_title = 'Activity by User ' . $activity->user_id;
            $post->post_content = $activity->content;
            $post->post_status = 'any';
            $post->post_type = 'bp_activity_post';
            $post->guid = home_url('/?post_type=bp_activity_post&p=' . ($index + 1000));
            $post->post_name = sanitize_title('activity-' . $index);

            $fake_posts[] = new WP_Post($post);
        }

        return $fake_posts;
    }, 10, 2);

    // Set post type to prevent normal WP_Query
    $query->set('post_type', 'bp_activity_post');
    $query->set('post__in', array()); // Empty to bypass wp_posts
}
add_action('elementor/query/bp_activity_query', 'custom_elementor_query_buddypress_activity');
