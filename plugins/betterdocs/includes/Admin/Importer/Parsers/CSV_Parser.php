<?php

namespace WPDeveloper\BetterDocs\Admin\Importer\Parsers;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WordPress extended RSS file parser implementations
 * Originally made by WordPress part of WordPress/Importer.
 * https://plugins.trac.wordpress.org/browser/wordpress-importer/trunk/parsers/class-wxr-parser-regex.php
 *
 * What was done (by Elementor):
 * Reformat of the code.
 * Changed text domain.
 * Changed methods visibility.
 */

/**
 * WXR Parser that uses regular expressions. Fallback for installs without an XML parser.
 */
class CSV_Parser {

    /**
     * Sort function for sorting CSV file data to keep Term and Author on top.
     *
     * @param array $a The first element to compare.
     * @param array $b The second element to compare.
     *
     * @return int Returns an integer less than, equal to, or greater than zero if the first
     *             argument is considered to be respectively less than, equal to, or greater
     *             than the second.
     */
    public function csvSort( $a, $b ) {
        $order = ['Term', 'Author', 'Docs'];

        $keyA = array_search( $a[0], $order );
        $keyB = array_search( $b[0], $order );

        return $keyA - $keyB;
    }

    public function parse( $file ) {
        $data = [
            'terms'   => [],
            'posts'   => [],
            'authors' => []
        ];

        $csv_data = [];

        // Read the entire file content and normalize line endings
        $fileContent = file_get_contents( $file );
        if ( $fileContent === false ) {
            return $data; // Return empty data if file reading fails
        }

        // Normalize line endings to Unix format
        $fileContent = str_replace( "\r\n", "\n", $fileContent );
        $fileContent = str_replace( "\r", "\n", $fileContent );

        // Use a temporary memory file to read the normalized content
        $handle = fopen( 'php://temp', 'r+' );
        fwrite( $handle, $fileContent );
        rewind( $handle );

        if ( $handle !== false ) {
            while ( ( $row = fgetcsv( $handle, 1000, "," ) ) !== false ) {
                $csv_data[] = $row;
            }
            fclose( $handle );
        }

        // Remove the CSV headers
        $headers = array_shift( $csv_data );

        // return data from sample csv file
        if ( $headers['0'] == 'Docs Title' ) {
            // update Docs Slug to post_name to check if post exists
            $replacementMap = [
                'Docs Slug' => 'post_name'
            ];

            $headers = array_map( function ( $item ) use ( $replacementMap ) {
                return isset( $replacementMap[$item] ) ? $replacementMap[$item] : $item;
            }, $headers );

            $data['type'] = 'sample/csv';

            foreach ( $csv_data as $row ) {
                error_log( print_r( $row, 1 ) );
                if ( count( $headers ) != count( $row ) ) {
                    $missingElements = array_fill( count( $row ), count( $headers ) - count( $row ), "" );
                    $row             = array_merge( $row, $missingElements );
                }
                $data['posts'][] = array_combine( $headers, $row );
            }

            return $data;
        }

        usort( $csv_data, [$this, 'csvSort'] );

        // Process CSV data and insert into WordPress
        foreach ( $csv_data as $row ) {
            $type = $row[0];

            if ( $type === 'Term' ) {
                // Extract the first 12 elements from the headers
                $term_headers = array_slice( $headers, 28, 11 );

                // Extract the first 12 elements from the row
                $term_row = array_slice( $row, 28, 11 );
                $term_row = array_pad( $term_row, count( $term_headers ), '' );

                if ( count( $term_headers ) !== count( $term_row ) ) {
                    return $data;
                }

                $term_data   = array_combine( $term_headers, $term_row );
                $taxonomy    = $term_data['Taxonomy'];
                $term_id     = $term_data['Term ID'];
                $name        = $term_data['Term name'];
                $slug        = $term_data['Term slug'];
                $term_group  = $term_data['Term group'];
                $description = $term_data['Term description'];
                $parent      = $term_data['Term parent'];
                $_docs_order = $term_data['Assigned Docs'];

                $doc_category_knowledge_base = $term_data['Assigned KBs'];
                $doc_category_knowledge_base = explode( ",", $doc_category_knowledge_base );
                $doc_category_knowledge_base = rest_sanitize_array( $doc_category_knowledge_base );

                $doc_category_order = $term_data['Doc Category order'];
                $kb_order           = $term_data['KB order'];

                $term_args = [
                    'term_id'       => $term_id,
                    'term_taxonomy' => $taxonomy,
                    'slug'          => $slug,
                    'term_parent'   => $parent,
                    'term_name'     => $name,
                    'description'   => $description,
                    'term_group'    => $term_group,
                    'termmeta'      => []
                ];

                switch ( $taxonomy ) {
                    case 'doc_category':
                        if ( $_docs_order ) {
                            $term_args['termmeta'][] = [
                                'key'   => '_docs_order',
                                'value' => $_docs_order
                            ];
                        }

                        if ( $doc_category_knowledge_base ) {
                            $term_args['termmeta'][] = [
                                'key'   => 'doc_category_knowledge_base',
                                'value' => $doc_category_knowledge_base
                            ];
                        }

                        if ( $doc_category_order ) {
                            $term_args['termmeta'][] = [
                                'key'   => 'doc_category_order',
                                'value' => $doc_category_order
                            ];
                        }
                        break;

                    case 'knowledge_base':
                        if ( $kb_order ) {
                            $term_args['termmeta'][] = [
                                'key'   => 'kb_order',
                                'value' => $kb_order
                            ];
                        }
                        break;
                }

                $data['terms'][] = $term_args;
            } elseif ( $type === 'Author' ) {
                // Extract the first 12 elements from the headers
                $author_headers = array_slice( $headers, 22, 6 );

                // Extract the first 12 elements from the row
                $author_row = array_slice( $row, 22, 6 );
                $author_row = array_pad( $author_row, count( $author_headers ), '' );

                $author_data = array_combine( $author_headers, $author_row );

                $author_id           = $author_data['Author id'];
                $author_login        = $author_data['Author login'];
                $author_email        = $author_data['Author email'];
                $author_display_name = $author_data['Author display name'];
                $author_first_name   = $author_data['Author first name'];
                $author_last_name    = $author_data['Author last name'];

                $author_args = [
                    'author_id'           => $author_id,
                    'author_login'        => $author_login,
                    'author_email'        => $author_email,
                    'author_display_name' => $author_display_name,
                    'author_first_name'   => $author_first_name,
                    'author_last_name'    => $author_last_name
                ];

                $data['authors'][$author_login] = $author_args;
            } elseif ( $type === 'Docs' ) {
                // Extract elements 18-36 from the headers
                $post_headers = array_slice( $headers, 1, 21 );

                // Extract elements 18-36 from the row
                $post_row = array_slice( $row, 1, 21 );

                // Fill up the remaining elements with empty values
                $post_row = array_pad( $post_row, count( $post_headers ), '' );

                // Ensure data has at least the required elements
                if ( count( $post_headers ) !== count( $post_row ) ) {
                    return $data;
                }

                $post_data = array_combine( $post_headers, $post_row );

                $post_args = [
                    'post_id'           => isset( $post_data['Docs ID'] ) ? $post_data['Docs ID'] : '',
                    'post_type'         => 'docs',
                    'post_author'       => isset( $post_data['Docs author'] ) ? $post_data['Docs author'] : '',
                    'post_content'      => isset( $post_data['Docs content'] ) ? $post_data['Docs content'] : '',
                    'post_title'        => isset( $post_data['Docs title'] ) ? $post_data['Docs title'] : '',
                    'post_name'         => isset( $post_data['Docs slug'] ) ? $post_data['Docs slug'] : '',
                    'post_excerpt'      => isset( $post_data['Docs excerpt'] ) ? $post_data['Docs excerpt'] : '',
                    'status'            => isset( $post_data['Docs status'] ) ? $post_data['Docs status'] : 'publish',
                    'post_password'     => isset( $post_data['Docs password'] ) ? $post_data['Docs password'] : '',
                    'post_parent'       => isset( $post_data['Docs parent'] ) ? $post_data['Docs parent'] : '',
                    'menu_order'        => isset( $post_data['Docs menu order'] ) ? $post_data['Docs menu order'] : '',
                    'post_date'         => isset( $post_data['Docs date'] ) ? $post_data['Docs date'] : '',
                    'post_date_gmt'     => isset( $post_data['Docs date gmt'] ) ? $post_data['Docs date gmt'] : '',
                    'post_modified'     => isset( $post_data['Docs modified date'] ) ? $post_data['Docs modified date'] : '',
                    'post_modified_gmt' => isset( $post_data['Docs modified date gmt'] ) ? $post_data['Docs modified date gmt'] : '',
                    'terms'             => [],
                    'postmeta'          => []
                ];

                // Check if 'Doc Categories' is set and has data
                if ( isset( $post_data['Doc Categories'] ) && $data['terms'] ) {
                    $termsDocCategories = $this->searchTermsByIds( $data['terms'], $post_data['Doc Categories'] );
                } else {
                    $termsDocCategories = [];
                }

                // Check if 'Doc Tags' is set and has data
                if ( isset( $post_data['Doc Tags'] ) && $data['terms'] ) {
                    $termsDocTags = $this->searchTermsByIds( $data['terms'], $post_data['Doc Tags'] );
                } else {
                    $termsDocTags = [];
                }

                // Check if 'Knowledge Bases' is set and has data
                if ( isset( $post_data['Knowledge Bases'] ) && $data['terms'] ) {
                    $termsKnowledgeBases = $this->searchTermsByIds( $data['terms'], $post_data['Knowledge Bases'] );
                } else {
                    $termsKnowledgeBases = [];
                }

                // Combine the results if needed
                $combinedTerms = array_merge( $termsDocCategories, $termsDocTags, $termsKnowledgeBases );

                $post_args['terms'] = $combinedTerms;

                $data['posts'][] = $post_args;
                if ( isset( $post_data['Docs attachement url'] ) && $post_data['Docs attachement url'] !== '' ) {
                    $attachment_args = [
                        'post_type'      => 'attachment',
                        'post_author'    => isset( $post_data['Docs author'] ) ? $post_data['Docs author'] : '',
                        'post_id'        => isset( $post_data['Docs attachement ID'] ) ? $post_data['Docs attachement ID'] : '',
                        'status'         => 'inherit',
                        'post_content'   => '',
                        'post_excerpt'   => '',
                        'guid'           => '',
                        'post_title'     => pathinfo( $post_data['Docs attachement url'], PATHINFO_FILENAME ),
                        'post_name'      => pathinfo( $post_data['Docs attachement url'], PATHINFO_FILENAME ),
                        'post_parent'    => isset( $post_data['Docs ID'] ) ? $post_data['Docs ID'] : '',
                        'attachment_url' => $post_data['Docs attachement url']
                    ];
                    $data['posts'][] = $attachment_args;
                }
            }
        }

        return $data;
    }

    public function searchTermsByIds( $terms, $termIds ) {
        // Convert the comma-separated term IDs to an array
        $termIdsArray = explode( ',', $termIds );

        // Initialize the result array
        $result = [];

        // Iterate through each term_id in the array
        foreach ( $termIdsArray as $termId ) {
            // Find the corresponding term in the terms array
            $foundTerm = array_filter( $terms, function ( $term ) use ( $termId ) {
                return $term['term_id'] == $termId;
            } );

            // If the term is found, add it to the result array
            if ( ! empty( $foundTerm ) ) {
                $foundTerm = reset( $foundTerm );
                $result[]  = [
                    'name'   => $foundTerm['term_name'],
                    'slug'   => $foundTerm['slug'],
                    'domain' => $foundTerm['term_taxonomy']
                ];
            }
        }

        return $result;
    }
}
