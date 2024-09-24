<?php

namespace WPDeveloper\BetterDocs\REST;

use WPDeveloper\BetterDocs\Core\BaseAPI;

class DocCategories extends BaseAPI {
    public function permission_check(): bool {
        return current_user_can( 'edit_docs' );
    }

    public function register() {
        $this->get( 'doc-categories', [$this, 'get_response'] );
    }

    public function get_response() {
        global $wpdb;

        $terms_query = betterdocs()->query->terms_query( [
            'hide_empty' => false,
            'taxonomy'   => 'doc_category',
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'doc_category_order',
            'order'      => 'ASC'
        ] );

        $terms    = get_terms( $terms_query );
        $response = [];

        foreach ( $terms as $term ) {
            $query_args = betterdocs()->query->docs_query_args( [
                'post_type'          => 'docs',
                'posts_per_page'     => '-1',
                'post_status'        => 'any',
                'term_id'            => $term->term_id,
                'term_slug'          => $term->slug,
                'nested_subcategory' => false,
                'orderby'            => 'betterdocs_order'
            ] );

            $posts                    = betterdocs()->query->get_posts( $query_args, true );
            $response[$term->term_id] = [];

            if ( ! $posts->have_posts() ) {
                wp_reset_query();
            }
            while ( $posts->have_posts() ):
                $posts->the_post();
                $data = $this->get_doc_data( get_the_ID() );
                array_push( $response[$term->term_id], $data );
            endwhile;

            wp_reset_postdata();
            wp_reset_query();
        }

        /**
         * Uncategories Docs
         */
        $_post__not_in_query = $wpdb->prepare(
            "SELECT ID as post_id from $wpdb->posts WHERE post_type = %s AND post_status != 'trash' AND post_status != 'auto-draft' AND ID NOT IN ( SELECT object_id as post_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s ) )",
            'docs',
            'doc_category'
        );

        $_post__not_in = $wpdb->get_col( $_post__not_in_query );

        if( ! empty( $_post__not_in ) ) {
            $uncategorized_docs = [];
            $_uncategorized_docs_query = new \WP_Query([
                'post_type' => 'docs',
                'post_status' => 'any',
                'post__in' => $_post__not_in
            ]);

            if ( ! $_uncategorized_docs_query->have_posts() ) {
                wp_reset_query();
            }

            while ( $_uncategorized_docs_query->have_posts() ):
                $_uncategorized_docs_query->the_post();
                $data = $this->get_doc_data( get_the_ID() );
                array_push( $uncategorized_docs, $data );
            endwhile;

            wp_reset_postdata();
            wp_reset_query();

            $response['uncategorized'] = $uncategorized_docs;
        }

        return $response;
    }

    /**
     * Get Doc Data Based On Doc ID
     *
     * @return void
     */
    public function get_doc_data( $id ) {
        $post_data = get_post( $id );
        $data      = [
            'author'         => (int) $post_data->post_author,
            'author_info'    => [
                'author_nicename' => get_the_author_meta( 'nicename', $post_data->post_author ),
                'author_url'      => get_author_posts_url( $post_data->post_author )
            ],
            'author_list'    =>
            get_users( [
                'fields' => [
                    'ID',
                    'user_login',
                    'display_name'
                ]
            ] )
            ,
            'unique_id'      => uniqid( 'doc' ),
            'id'             => $post_data->ID,
            'title'          => $post_data->post_title,
            'slug'           => get_post_field( 'post_name', $id ),
            'link'           => get_permalink( $id ),
            'status'         => get_post_status(),
            'date'           => $post_data->post_date,
            'date_gmt'       => $post_data->post_date_gmt,
            'doc_category'   => wp_get_post_terms( $id, 'doc_category', ["fields" => "ids"] ),
            'doc_tag'        => wp_get_post_terms( $id, 'doc_tag', ["fields" => "ids"] ),
            'password'       => $post_data->post_password,
            'comment_status' => $post_data->comment_status
        ];

        if ( taxonomy_exists( 'knowledge_base' ) ) {
            $data['knowledge_base'] = wp_get_post_terms( $id, 'knowledge_base', ["fields" => "ids"] );
        }

        return $data;
    }
}
