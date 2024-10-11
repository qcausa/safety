<?php

namespace WPDeveloper\BetterDocs\REST;

use Error;
use WP_Query;
use WP_REST_Response;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class Docs extends BaseAPI {
    public function permission_check(): bool {
        return true;
    }

    public function register() {
        $this->get( 'search', [$this, 'search_posts'] );
        $this->get( 'search-insert', [$this, 'search_insert'] );
        $this->get( 'get-terms', [$this, 'get_terms_name_and_slug'] );
        $this->get( 'months-with-posts', [$this, 'get_months_with_posts'] );
        $this->register_field( 'docs', 'year_month', [
            'get_callback' => [$this, 'year_month']
        ] );

        $this->register_field( 'docs', 'password', [
            'get_callback' => [$this, 'get_post_password']
        ] );

        add_filter( 'rest_docs_query', [$this, 'filter_docs_query'], 10, 2 );
    }

    /**
     * Retrieves the months and years that have posts of the type 'docs' and formats them.
     *
     * This function queries the WordPress database for all unique months and years
     * in which 'docs' post type posts have been published. The results are then
     * formatted into an array of associative arrays, where each entry contains an
     * 'id' and a 'name'.
     *
     * The 'id' is a string formatted as 'month-year' (e.g., 'may-2024') to provide
     * a unique identifier that is easy to work with in JavaScript and HTML. The 'name'
     * is a more human-readable string formatted as 'Month Year' (e.g., 'May 2024') to
     * display to users.
     *
     * @return WP_REST_Response A response containing the formatted months and years.
     */
    public function get_months_with_posts() {
        global $wpdb;

        // Query to get distinct year and month from posts of type 'docs'
        $results = $wpdb->get_results(
            "SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month
            FROM $wpdb->posts
            WHERE post_type = 'docs'
            ORDER BY post_date DESC"
        );

        $formatted_months = [];

        foreach ( $results as $result ) {
            $year  = $result->year;
            $month = $result->month;

            // Create a DateTime object to format the month
            $date         = \DateTime::createFromFormat( '!m', $month );
            $month_name   = $date->format( 'F' ); // Full month name
            $month_number = $date->format( 'm' ); // Month number with leading zero

            // Format the months and years into wp rest api structure like wp-json/wp/v2/doc_category
            $formatted_months[] = [
                'id'   => "$year-$month_number", // e.g., '2024-05'
                'name' => "$month_name $year" // e.g., 'May 2024'
            ];
        }

        return rest_ensure_response( $formatted_months );
    }

    /**
     * Callback function to retrieve 'year_month' field value.
     *
     * @param object $post The REST API response object.
     * @return string The formatted date (e.g., '2024-05').
     */
    public function year_month( $post ) {
        $date_string = isset( $post->post_date ) ? $post->post_date : '';

        $date = new \DateTime( $date_string );

        // Format the date to 'Y-m' (e.g., '2024-05')
        $formatted_date = $date->format( 'Y-m' );

        return $formatted_date;
    }

    /**
     * Filter the docs query by year_month parameters.
     *
     * @param array $args The query arguments.
     * @param WP_REST_Request $request The current REST API request.
     * @return array Modified query arguments.
     */
    public function filter_docs_query( $args, $request ) {
        // Filter by year_month
        if ( isset( $request['year_month'] ) ) {
            $formatted_date = $request['year_month'];

            // Parse the formatted_date to year and month
            $year  = substr( $formatted_date, 0, 4 );
            $month = substr( $formatted_date, 5, 2 );

            // Add date query arguments
            $args['date_query'] = [
                [
                    'year'  => $year,
                    'month' => $month
                ]
            ];
        }

        return $args;
    }


    public function get_post_password( $object, $field_name, $request ) {
        if ( current_user_can( 'edit_docs' ) ) {
            return isset( $object['password'] ) ? $object['password'] : '';
        } else {
            return '';
        }
    }

    public function search_posts( $request ) {
        $search_query   = sanitize_text_field($request->get_param('s'));
        $doc_category   = sanitize_text_field($request->get_param('doc_category'));
        $number         = (int) $request->get_param('per_page') ? (int) $request->get_param('per_page') : 5;
        $posts          = array();

        // Common query args
        $common_args = [
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'orderby'          => 'relevance',
        ];

        if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
            $common_args['suppress_filters'] = false;
            $common_args['lang']             = ICL_LANGUAGE_CODE;
        }

        if ( $search_query ) {
            $common_args['s'] = $search_query;
            $common_args['posts_per_page'] = -1;
        } else {
            $common_args['posts_per_page'] = $number;
        }

        // Docs-specific query
        $docs_args = array_merge( $common_args, [
            'post_type'       => 'docs'
        ]);

        if ( ! $search_query ) {
            $docs_args['meta_key']       = '_betterdocs_meta_views';
            $docs_args['orderby']        = 'meta_value_num';
            $docs_args['order']          = 'DESC';
        }

        // Taxonomy filter for docs
        if ( $doc_category ) {
            $docs_args['tax_query'] = [
                [
                    'taxonomy'         => 'doc_category',
                    'field'            => 'slug',
                    'terms'            => $doc_category,
                    'operator'         => 'AND',
                    'include_children' => true,
                ],
            ];
        }

        // FAQ-specific query
        $faq_args = array_merge( $common_args, [
            'post_type'      => 'betterdocs_faq',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        // Run individual queries
        $docs_query = betterdocs()->query->get_posts( $docs_args );
        $faq_query  = new WP_Query( $faq_args );

        // Process docs posts
        if ( $docs_query->have_posts() ) {
            while ( $docs_query->have_posts() ) {
                $docs_query->the_post();

                $taxonomies = array();
                $terms = get_the_terms(get_the_ID(), 'doc_category');
                if ($terms && !is_wp_error($terms)) {
                    $taxonomies = wp_list_pluck($terms, 'name');
                }

                $posts[] = array(
                    'title'      => get_the_title(),
                    'post_type'  => get_post_type(),
                    'permalink'  => get_the_permalink(),
                    'taxonomies' => implode(', ', $taxonomies),
                );
            }
            wp_reset_postdata();
        }

        // Process FAQ posts with content
        if ( $faq_query->have_posts() ) {
            while ( $faq_query->have_posts() ) {
                $faq_query->the_post();

                $terms = get_the_terms(get_the_ID(), 'betterdocs_faq_category');
                $taxonomies = array();
                if ($terms && !is_wp_error($terms)) {
                    $taxonomies = wp_list_pluck($terms, 'name');
                }

                $posts[] = array(
                    'title'      => get_the_title(),
                    'content'    => get_the_content(),  // Include post content for FAQ posts
                    'post_type'  => get_post_type(),
                    'permalink'  => get_the_permalink(),
                    'taxonomies' => implode(', ', $taxonomies),
                );
            }
            wp_reset_postdata();
        }

        return $posts;
    }



    public function search_insert( $request ) {
        $search_input = sanitize_text_field($request->get_param('s'));
        $no_result = sanitize_text_field($request->get_param('no_result'));

        return betterdocs()->query->insert_search_keyword($search_input, $no_result);
    }


    public function get_terms_name_and_slug( $request ) {
        // Retrieve all terms for the specified taxonomy, including empty ones
        $terms = get_terms([
            'taxonomy' => $request->get_param('taxonomy'),
            'hide_empty' => false,
            'fields' => 'all',
        ]);

        // Initialize an empty array to hold the term data
        $term_data = [];

        // Loop through each term and extract the name and slug
        $term_data = array_map(function($term) {
            return [
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => $term->parent,
            ];
        }, $terms);


        // Return the array of term data
        return $term_data;
    }

    public function get_faq_categories( $request ) {}

}
