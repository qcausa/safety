<?php

namespace WPDeveloper\BetterDocs\Utils;

use function BetterLinksPro\Dependencies\GuzzleHttp\json_decode;

class Helper extends Base {

    public static function get_plugins( $plugin_basename = null ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        return $plugin_basename == null ? $plugins : isset( $plugins[$plugin_basename] );
    }

    public static function is_plugin_active( $plugin_basename ) {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( $plugin_basename );
    }

    public static function get_tax( $tax = '' ) {
        global $wp_query;

        if ( is_tax( 'knowledge_base' ) ) {
            $_taxes = $wp_query->tax_query->queried_terms;
            if ( array_key_exists( "doc_category", $_taxes ) ) {
                $tax = 'doc_category';
            } else {
                $tax = 'knowledge_base';
            }
        } elseif ( is_tax( 'doc_category' ) ) {
            $tax = 'doc_category';
        } elseif ( is_tax( 'doc_tag' ) ) {
            $tax = 'doc_tag';
        }

        return $tax;
    }

    public function is_templates() {
        global $wp_query;
        $slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopedia' );

        $tax = $this->get_tax();
        if ( is_post_type_archive( 'docs' ) || $tax === 'knowledge_base' || $tax === 'doc_category' || $tax === 'doc_tag' || is_singular( 'docs' ) || is_tax( 'glossaries' ) ) {
            return true;
        }

        if ( isset( $wp_query->query['pagename'] ) && $wp_query->query['pagename'] === $slug ) {
            return true;
        }

        return false;
    }

    public function is_el_templates() {
        $_return_val = betterdocs()->editor->get( 'elementor' )->is_templates();

        if ( $_return_val !== null ) {
            return $_return_val;
        }

        $this->is_templates();
    }

    /**
     * Which tab to show.
     *
     * 1. Drag and Drop UI
     * 2. Post List UI
     *
     * * 1. dnd
     * * 2. classic
     *
     * look into views/admin/docs-ui directory to know more.
     *
     * @return string
     */
    public static function admin_tab() {
        $admin_ui = 'grid';
        if ( isset( $_GET['mode'], $_GET['page'] ) && $_GET['page'] === 'betterdocs-admin' && ! empty( $_GET['mode'] ) ) {
            $admin_ui = $_GET['mode'] === 'grid' ? 'grid' : 'list';
        }

        return $admin_ui;
    }

    public static function is_active( $prev, $current, $class = 'active' ) {
        if ( $current == $prev ) {
            return $class;
        }

        return '';
    }

    public function get_users( $args ) {
        $cache_key = 'betterdocs_cache_admin_user_roles';
        $users     = betterdocs()->database->get_cache( $cache_key );

        if ( false === $users ) {
            $users = get_users( $args );
            betterdocs()->database->set_cache( $cache_key, $users );
        }

        return $users;
    }

    /**
     * Normalize Menu Array
     * Menu creator helper
     *
     * @since 2.5.0
     *
     * @param string $title
     * @param string $slug
     * @param string $cap
     * @param array  $callback
     *
     * @return array
     */
    public static function normalize_menu( $title, $slug, $cap = 'edit_docs', $callback = null, $optional = [] ) {
        $args = [
            'page_title' => $title,
            'menu_title' => $title,
            'capability' => $cap,
            'menu_slug'  => $slug
        ];

        if ( $callback != null ) {
            $args['callback'] = $callback;
        }

        return wp_parse_args( $optional, $args );
    }

    /**
     * Check if the current theme is a block theme.
     *
     * @since x.x.x
     * @return bool
     */
    public function current_theme_is_fse_theme() {
        if ( function_exists( 'wp_is_block_theme' ) ) {
            return (bool) wp_is_block_theme();
        }
        if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
            return (bool) gutenberg_is_fse_theme();
        }

        return false;
    }

    protected static function is_assoc_array( $array ) {
        return array_keys( $array ) !== range( 0, count( $array ) - 1 );
    }

    public static function merge( &$array1, &$array2 ) {
        $merged = $array1;

        foreach ( $array2 as $key => &$value ) {
            if ( is_array( $value ) && self::is_assoc_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
                $merged[$key] = self::merge( $merged[$key], $value );
            } elseif ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
                $merged[$key] = array_merge( $merged[$key], $value );
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function get_custom_excerpt( $content, $numOfWords ) {
        $content      = strip_shortcodes( $content );
        $content      = wp_strip_all_tags( $content );
        $words        = explode( ' ', $content );
        $excerptWords = array_slice( $words, 0, $numOfWords );
        $excerpt      = implode( ' ', $excerptWords );
        if ( count( $words ) > $numOfWords ) {
            $excerpt .= '...';
        }
        return $excerpt;
    }

    public static function get_current_letter_docs( $current_letter, $limit = '' ) {
        global $wpdb;

        // Check if the encyclopedia_prefix parameter is set

        $encyclopeia_suorce = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );
        $enable_glossaries  = betterdocs()->settings->get( 'enable_glossaries', false );

        // if($enable_glossaries && $encyclopeia_suorce === 'glossaries'){
        if ( $enable_glossaries && $encyclopeia_suorce === 'glossaries' ) {
            $query = "
                SELECT
                    t.term_id,
                    t.name AS post_title,
                    t.slug as slug,
                    '' AS post_excerpt,
                    CONCAT('" . get_home_url() . "/glossaries/', t.slug) AS permalink,
                    tt.description AS post_content,
                    JSON_OBJECT(
                        'status', COALESCE(MAX(CASE WHEN m.meta_key = 'status' THEN m.meta_value END), ''),
                        'glossary_term_description', COALESCE(MAX(CASE WHEN m.meta_key = 'glossary_term_description' THEN m.meta_value END), '')
                    ) AS meta_data
                FROM
                    {$wpdb->terms} t
                INNER JOIN
                    {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN
                    {$wpdb->termmeta} m ON t.term_id = m.term_id
                WHERE
                    tt.taxonomy = 'glossaries'
                AND
                    LEFT(t.name, 1) = %s
                GROUP BY
                    t.term_id
                ORDER BY
                    t.name ASC
                $limit
            ";
        } else {
            $query = "
                SELECT ID, post_title, post_excerpt, guid, post_content
                FROM {$wpdb->posts}
                WHERE post_type = 'docs'
                AND post_status = 'publish'
                AND LEFT(post_title, 1) = %s
                ORDER BY post_date DESC
                $limit
            ";
        }

        $current_letter_docs = $wpdb->get_results( $wpdb->prepare( $query, $current_letter ), ARRAY_A );

        return $current_letter_docs;
    }

    public static function docs_sort_by_letter($limit = 10) {
        global $wpdb;
        $enable_non_latin = betterdocs()->settings->get('encyclopedia_enable_non_latin');
        $script   = betterdocs()->settings->get('encyclopedia_non_latin_option');
        $letters = Helper::get_character_range($enable_non_latin, $script);

        $docs_by_letter = array();
        $encyclopeia_suorce  = betterdocs()->settings->get('encyclopedia_source', 'docs');
        $enable_glossaries  = betterdocs()->settings->get('enable_glossaries', false);


        foreach ($letters as $letter) {

            $posts = self::get_current_letter_docs($letter, "LIMIT $limit");

            if (is_array($posts) && !empty($posts)) {
                foreach ($posts as $post) {

                    $description = \json_decode($post['meta_data'], true);
                    $glossary_term_description = $description['glossary_term_description'] ?? '';


                    // Remove any <p> tags or other unwanted HTML tags
                    $glossary_term_description = strip_tags($glossary_term_description);
                    $post_excerpt = strip_tags($post['post_excerpt'] ?? '');

                    // Prepare post data
                    $post_data = array(
                        'id'            => $post['ID'] ?? '',
                        'post_title'    => $post['post_title'] ?? '',
                        'post_excerpt'  => !empty($post_excerpt)
                                            ? $post_excerpt
                                            : (!empty($glossary_term_description)
                                                ? self::get_custom_excerpt($glossary_term_description, 15)
                                                : self::get_custom_excerpt(strip_tags($post['post_content'] ?? ''), 15)),
                    );


                    if($enable_glossaries && $encyclopeia_suorce === 'glossaries'){
                        $post_data['permalink'] = isset($post['slug']) ? get_term_link($post['slug'], 'glossaries') : '';
                    }
                    else{
                        $post_data['permalink'] = isset($post['ID']) ? get_the_permalink($post['ID']) : '';
                    }

                    $docs_by_letter[$letter][] = $post_data;
                }
            }
        }

        return $docs_by_letter;
    }

    public static function get_glossaries() {
        global $wpdb;

        $query = "
            SELECT t.name
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'glossaries'
            ORDER BY t.name ASC
        ";

        $glossaries = $wpdb->get_col( $query );

        return $glossaries;
    }

    /**
     * Determine live search template layout, when live search is not selected from customizer(this will work when live search template is not selected from customizer)
     *
     * @param string $layout
     * @return string $layout
     */
    public static function determine_search_layout( $layout ) {
        $search_layout       = betterdocs()->customizer->defaults->get( 'betterdocs_search_layout_select' );
        $docs_layout         = betterdocs()->customizer->defaults->get( 'betterdocs_docs_layout_select' );
        $archive_page_layout = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select' );
        $single_layout       = betterdocs()->customizer->defaults->get( 'betterdocs_single_layout_select' );

        if ( is_post_type_archive( 'docs' ) ) {
            if ( $docs_layout != "layout-7" && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( $docs_layout == 'layout-7' && ! $search_layout ) {
                $layout = 'layout-2';
            }
        } else if ( is_tax( 'doc_tag' ) && ! $search_layout ) {
            $layout = 'layout-1';
        } else if ( is_tax( 'doc_category' ) ) {
            if ( $archive_page_layout != 'layout-7' && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( $archive_page_layout == 'layout-7' && ! $search_layout ) {
                $layout = 'layout-2';
            }
        } else if ( is_singular( 'docs' ) ) {
            if ( $single_layout != 'layout-8' && $single_layout != 'layout-9' && !$search_layout ) {
                $layout = 'layout-1';
            } else if ( ( $single_layout == 'layout-8' && ! $search_layout ) || ( $single_layout == 'layout-9' && ! $search_layout ) ) {
                $layout = 'layout-2';
            }
        }

        return $layout;
    }
    public static function  mb_ord_fallback($char) {
        $code = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
        return $code[1];
    }

    public static function  mb_chr_fallback($code) {
        return mb_convert_encoding(pack('N', $code), 'UTF-8', 'UCS-4BE');
    }

    public static function unicodeRange($start, $end) {
        $range = [];
        for ($i = self::mb_ord_fallback($start); $i <= self::mb_ord_fallback($end); $i++) {
            $range[] = self::mb_chr_fallback($i);
        }
        return $range;
    }

    public static function get_character_range($enable_non_latin, $script) {
        if($enable_non_latin){
            switch ($script) {
                case 'arabic':
                    return self::unicodeRange('ء', 'ي');
                case 'cyrillic':
                    return self::unicodeRange('А', 'Я');
                case 'hebrew':
                    return self::unicodeRange('א', 'ת');
                case 'greek':
                    return self::unicodeRange('Α', 'Ω');
                default:
                    return range('A', 'Z');
            }
        }

        return range('A', 'Z');
    }

    public static function get_the_top_most_parent( $term_id ) {
        while( $term_id != 0 ) {
            $parent_id = wp_get_term_taxonomy_parent_id( $term_id, 'doc_category' );

            if( $parent_id == 0 ) {
                break;
            }
            
            $term_id = $parent_id;
        }
        return $term_id;
    }

}
