<?php

namespace WPDeveloper\BetterDocs\REST;

use WPDeveloper\BetterDocs\Core\BaseAPI;

class Docs extends BaseAPI {
    public function permission_check(): bool {
        return true;
    }

    public function register() {
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
}
