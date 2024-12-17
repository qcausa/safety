<?php
/*
	Plugin Name: Download Monitor - Page Addon
	Plugin URI: https://www.download-monitor.com/extensions/page-addon/
	Description: Adds a [download_page] shortcode for showing off your available downloads, tags and categories.
	Version: 4.3.0
	Author: WPChill
	Author URI: https://wpchill.com
	Requires at least: 3.8
	Tested up to: 6.6
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_DLM_Page_Addon class.
 */
class WP_DLM_Page_Addon {

	const VERSION = '4.3.0';

	private $page_id = '';

	/** @var  WP_DLM_Page_Addon */
	private static $instance = null;

	/**
	 * Singleton getter
	 *
	 * @return WP_DLM_Page_Addon
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new WP_DLM_Page_Addon();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning
	 */
	protected function __clone() {
		// no cloning allowed
	}

	/**
	 * Private constructor, only run once
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Runs on plugin activation
	 */
	public static function activation() {
		$rewrite = new DLM_PA_Rewrite();
		$rewrite->add_endpoint();
		$rewrite->flush();
	}

	/**
	 * Setup plugin
	 */
	public function setup() {

		// Actions.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		// Comment this for the moment, as I am not sure if we need to hide the setting if "Include in search" setting is
		// toggled off.
		//add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Request.
		$request = new DLM_PA_Request();
		$request->setup();

		// Shortcodes.
		add_shortcode( 'download_page', array( $this, 'download_page' ) );
		add_shortcode( 'download_list', array( $this, 'download_list' ) );

		// add endpoints.
		$rewrite = new DLM_PA_Rewrite();
		add_action( 'init', array( $rewrite, 'add_endpoint' ) );

		// Register Extension.
		add_filter( 'dlm_extensions', array( $this, 'register_extension' ) );

		// check if in admin.
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 8 );
			// Add filter for compatibility, will be removed migration has been done.
			add_filter( 'dlm_disable_update_for_dlm-page-addon', '__return_false', 15 );
			if ( ! $this->check_functionality() ) {
				return;
			}
			// add settings.
			$settings = new DLM_PA_Settings();
			$settings->setup();

		} else {

			/**
			 * Is frontend
			 */

			// fix the title.
			$title = new DLM_PA_Title();
			$title->setup();

			// fix the content.
			$content = new DLM_PA_Content();
			$content->setup();

			// check if search results should go to PA detail page.
			if ( 0 !== intval( get_option( 'dlm_pa_search_results_page', 0 ) ) ) {
				$search_support = new DLM_PA_Search();
				$search_support->setup();
			}
		}
	}

	/**
	 * @return int
	 */
	public function get_page_id() {
		return absint( $this->page_id );
	}

	/**
	 * Sets the page id
	 *
	 * @param int $page_id
	 *
	 * @return void
	 * @since 4.2.0
	 */
	public function set_page_id( $page_id ) {
		$this->page_id = $page_id;
	}

	/**
	 * Register this extension
	 *
	 * @param array $extensions
	 *
	 * @return array $extensions
	 */
	public function register_extension( $extensions ) {
		$extensions[] = array(
			'file'    => 'dlm-page-addon',
			'version' => self::VERSION,
			'name'    => 'Page Addon'
		);

		return $extensions;
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'dlm_page_addon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		global $dlm_page_addon, $post;
		wp_enqueue_script( 'jquery' );
		wp_register_style( 'dlm-page-addon-frontend', $this->plugin_url() . '/assets/css/page.css' );
		$this->page_id = $post->ID;

		// Enqueue modal scripts if needed.
		if ( class_exists( 'WP_DLM' ) && method_exists( 'WP_DLM', 'do_xhr' ) && WP_DLM::do_xhr() && get_option( 'dlm_no_access_modal', false ) && 0 !== intval( $this->page_id ) ) {
			wp_enqueue_script( 'dlm-page-addon-modal', $this->plugin_url() . '/assets/js/page' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js' );
			$page_link = get_permalink( $this->page_id );
			$palink    = $page_link . 'download-info';
			if ( '' === get_option( 'permalink_structure' ) ) {
				$palink = $page_link . '&download-info=';
			}
			// Add the page addons links that should be opened in the modal.
			wp_add_inline_script( 'dlm-page-addon-modal', 'const dlmPAlinks = "' . $palink . '";', 'before' );
		}
	}

	/**
	 * Add scripts to admin.
	 *
	 * @access public
	 * @return void
	 * @since  4.2.0
	 */
	public function admin_scripts() {
		// Enqueue admin settings if needed.
		if ( isset( $_GET['page'] ) && 'download-monitor-settings' === $_GET['page'] ) {
			// Enqueue Settings JS
			wp_enqueue_script( 'dlm_pa_settings', $this->plugin_url() . '/assets/js/settings' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', array( 'jquery' ), self::VERSION );
		}
	}

	/**
	 * Get the plugin url
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		return plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );
	}

	/**
	 * Get the plugin path
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Get the endpoint link for a tag (to display on the page addon)
	 *
	 * @param object $tag
	 *
	 * @return string
	 */
	public function get_tag_link( $tag ) {
		if ( get_option( 'permalink_structure' ) ) {
			$link = trailingslashit( get_permalink( $this->page_id ) ) . 'download-tag/' . $tag->slug . '/';
		} else {
			$link = add_query_arg( 'download-tag', $tag->slug );
		}

		return esc_url( $link );
	}

	/**
	 * Get the endpoint link for a category (to display on the page addon)
	 *
	 * @param object $cat
	 *
	 * @return string
	 */
	public function get_category_link( $cat ) {
		if ( get_option( 'permalink_structure' ) ) {
			$link = trailingslashit( get_permalink( $this->page_id ) ) . 'download-category/' . $cat->slug . '/';
		} else {
			$link = add_query_arg( 'download-category', $cat->slug, get_permalink( $this->page_id ) );
		}

		return esc_url( $link );
	}

	/**
	 * Get the endpoint link for a download (to display on the page addon)
	 *
	 * @param DLM_Download $dlm_download
	 * @param int          $page_id base ID for download_info link
	 *
	 * @return string
	 */
	public function get_download_info_link( $dlm_download, $page_id = 0, $direct_download = false ) {

		if ( $direct_download ) {
			return esc_url( $dlm_download->get_the_download_link() );
		}

		if ( 0 !== $page_id ) {
			$page_url = get_permalink( $page_id );
		} else {
			$page_url = get_permalink( $this->page_id );
		}

		if ( get_option( 'permalink_structure' ) ) {
			$link = trailingslashit( $page_url ) . 'download-info/' . $dlm_download->get_slug() . '/';
		} else {
			if ( is_front_page() ) {
				$page_url = add_query_arg( 'page_id', ( 0 !== $page_id ) ? $page_id : $this->page_id, $page_url );
			}
			$link = add_query_arg( 'download-info', $dlm_download->get_slug(), $page_url );
		}

		return esc_url( $link );
	}

	/**
	 * The download page shortcode
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function download_page( $args = array() ) {
		global $wp;

		// Enqueue main plugin's frontend style
		wp_enqueue_style( 'dlm-frontend' );

		wp_enqueue_style( 'dlm-page-addon-frontend' );

		ob_start();

		do_action( 'dlm_page_addon_before_download_page' );

		if ( ! empty( $wp->query_vars['download-category'] ) ) {

			$this->download_term( $wp->query_vars['download-category'], 'dlm_download_category', $args );
		} elseif ( ! empty( $wp->query_vars['download-tag'] ) ) {

			$this->download_term( $wp->query_vars['download-tag'], 'dlm_download_tag', $args );
		} elseif ( ! empty( $wp->query_vars['download-info'] ) ) {

			$this->download_info( $wp->query_vars['download-info'], $args );
		} elseif ( ! empty( $_GET['download_search'] ) ) {

			$this->search_results( sanitize_text_field( $_GET['download_search'] ), $args );
		} else {

			// extract shortcode arguments
			extract( shortcode_atts( array(
				'format'             => 'pa',
				'posts_per_page'     => '20',
				'show_search'        => 'true',
				'show_featured'      => 'true',
				'show_tags'          => 'true',
				'featured_limit'     => '4',
				'featured_format'    => 'pa-thumbnail',
				'category_limit'     => '4',
				'front_orderby'      => 'download_count',
				'exclude_categories' => '',
				'include_categories' => '',
				'direct_download'    => 'false'
			), $args ) );

			$show_search     = ( $show_search === 'true' );
			$show_featured   = ( $show_featured === 'true' );
			$show_tags       = ( $show_tags === 'true' );
			$direct_download = ( $direct_download === 'true' );
			$meta_key        = '';
			$order_by_count  = '';
			switch ( $front_orderby ) {
				case 'title' :
				default :
					$order = 'asc';
					break;
				case 'download_count' :
					$order          = 'desc';
					$front_orderby  = 'meta_value_num';
					$meta_key       = '_download_count';
					$order_by_count = '1';
					break;
				case 'date' :
					$order = 'desc';
					break;
			}

			// template handler
			$template_handler = new DLM_Template_Handler();

			if ( $show_search ) {
				$template_handler->get_template_part( 'search-downloads', '', $this->plugin_path() . 'templates/' );
			}

			if ( $show_featured ) {

				// fetch downloads
				$downloads = download_monitor()->service( 'download_repository' )->retrieve( array(
					'orderby'    => $front_orderby,
					'order'      => $order,
					'meta_key'   => $meta_key,
					'order_by_count' => $order_by_count,
					'meta_query' => array(
						array(
							'key'   => '_featured',
							'value' => 'yes'
						)
					)
				), $featured_limit );

				// make featured downloads filterable
				$downloads = apply_filters( 'dlm_page_addon_featured_downloads', $downloads );

				if ( count( $downloads ) > 0 ) {
					$template_handler->get_template_part( 'featured-downloads', '', $this->plugin_path() . 'templates/', array(
						'downloads'       => $downloads,
						'format'          => $featured_format,
						'direct_download' => $direct_download
					) );
				}
			}

			if ( $show_tags ) {

				// get tags
				$tags = get_terms( 'dlm_download_tag', apply_filters( 'dlm_page_addon_get_tag_args', array(
					'orderby' => 'count',
					'order'   => 'DESC',
					'number'  => 50
				) ) );

				// make tags filterable
				$tags = apply_filters( 'dlm_page_addon_tags', $tags );

				if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {

					foreach ( $tags as $key => $tag ) {
						$tags[ $key ]->link = $this->get_tag_link( $tag );
						$tags[ $key ]->id   = $tag->term_id;
					}

					$template_handler->get_template_part( 'download-tags', '', $this->plugin_path() . 'templates/', array( 'tags' => $tags ) );
				}
			}

			// Categories
			$include = array_filter( array_map( 'absint', explode( ',', $include_categories ) ) );
			$exclude = array_filter( array_map( 'absint', explode( ',', $exclude_categories ) ) );

			$category_args = apply_filters( 'dlm_page_addon_get_category_args', array(
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => ! empty( $include ) ? false : true,
				'pad_counts' => true,
				'child_of'   => 0,
				'exclude'    => $exclude,
				'include'    => $include
			) );

			$categories = get_terms( 'dlm_download_category', $category_args );

			$categories = apply_filters( 'dlm_page_addon_categories', $categories, $category_args );

			if ( $categories ) {

				echo apply_filters( 'dlm_page_addon_categories_start', '<div class="download-monitor-categories">' );

				foreach ( $categories as $category ) {

					$downloads = download_monitor()->service( 'download_repository' )->retrieve( apply_filters( 'dlm_page_addon_download_retrieve_args', array(
						'orderby'   => $front_orderby,
						'order'     => $order,
						'meta_key'  => $meta_key,
						'tax_query' => array(
							array(
								'taxonomy' => 'dlm_download_category',
								'field'    => 'slug',
								'terms'    => $category->slug,
							)
						)
					),                                                                                          $category ), $category_limit );

					// make downloads filterable
					$downloads = apply_filters( 'dlm_page_addon_category_downloads', $downloads, $category );

					if ( count( $downloads ) > 0 ) {
						$template_handler->get_template_part( 'download-categories', '', $this->plugin_path() . 'templates/', array(
							'category'        => $category,
							'downloads'       => $downloads,
							'format'          => $format,
							'direct_download' => $direct_download
						) );
					}

				}
				echo apply_filters( 'dlm_page_addon_categories_end', '</div>' );

			}

		}

		return '<div id="download-page">' . ob_get_clean() . '</div><!-- Download Page powered by WordPress Download Monitor (https://www.download-monitor.com) -->';
	}

	/**
	 * Show a download's info page
	 *
	 * @param string $slug
	 * @param array  $args
	 */
	public function download_info( $slug, $args ) {
		global $wpdb;

		$download_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download' AND post_status = 'publish';", sanitize_title( $slug ) ) );

		$template_handler = new DLM_Template_Handler();

		try {

			// fetch download
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

			// fitler download
			$download = apply_filters( 'dlm_page_addon_download_info', $download );

			$template_handler->get_template_part( 'content-download', 'pa-single', $this->plugin_path() . 'templates/', array( 'dlm_download' => $download ) );
		} catch ( Exception $exception ) {
			$template_handler->get_template_part( 'no-downloads-found', '', $this->plugin_path() . 'templates/' );
		}
	}

	/**
	 * Show a term page
	 *
	 * @param string $slug
	 * @param string $taxonomy
	 * @param array  $args
	 */
	public function download_term( $slug, $taxonomy, $args ) {
		global $wp;

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( is_wp_error( $term ) || ! $term ) {
			return;
		}

		extract( shortcode_atts( array(
			'posts_per_page'     => '20',
			'format'             => 'pa',
			'default_orderby'    => 'title',
			'exclude_categories' => '',
			'direct_download'    => 'false'
		), $args ) );

		$direct_download = ( $direct_download === 'true' );
		$dlpage          = ! empty( $_GET['dlpage'] ) ? $_GET['dlpage'] : 1;
		$current_orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : $default_orderby;
		$meta_key        = '';
		$order_by_count  = '';
		switch ( $current_orderby ) {
			case 'title' :
			default :
				$order = 'asc';
				break;
			case 'download_count' :
				$order           = 'desc';
				$current_orderby = 'meta_value_num';
				$meta_key        = '_download_count';
				$order_by_count  = '1';
				break;
			case 'date' :
				$order = 'desc';
				break;
		}

		$args = apply_filters( 'dlm_page_addon_term_query_args', array(
			'orderby'        => $current_orderby,
			'order'          => $order,
			'meta_key'       => $meta_key,
			'order_by_count' => $order_by_count,
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $slug
				)
			)
		) );

		// fetch downloads
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $args, $posts_per_page, $posts_per_page * ( $dlpage - 1 ) );

		// make download filterable
		$downloads = apply_filters( 'dlm_page_addon_downloads_term_list', $downloads, $slug, $taxonomy, $term );

		$pages = ceil( download_monitor()->service( 'download_repository' )->num_rows( $args ) / $posts_per_page );

		// template handler
		$template_handler = new DLM_Template_Handler();

		// load template parts
		$template_handler->get_template_part( 'subcategories', '', $this->plugin_path() . 'templates/', array(
			'term'               => $term,
			'taxonomy'           => $taxonomy,
			'exclude_categories' => $exclude_categories
		) );


		// only load order template file if we have the right query_vars
		if ( ! empty( $wp->query_vars['pagename'] ) && ! empty( $wp->query_vars['download-tag'] ) ) {
			$base_url = home_url( '/' . $wp->query_vars['pagename'] . '/download-tag/' . $wp->query_vars['download-tag'] . '/' );
			$template_handler->get_template_part( 'orderby', '', $this->plugin_path() . 'templates/', array(
				'current_orderby' => $current_orderby,
				'base_url'        => $base_url
			) );
		}

		$template_handler->get_template_part( 'download-list', '', $this->plugin_path() . 'templates/', array(
			'format'          => $format,
			'downloads'       => $downloads,
			'direct_download' => $direct_download
		) );
		$template_handler->get_template_part( 'pagination', '', $this->plugin_path() . 'templates/', array( 'pages' => $pages ) );

	}

	/**
	 * Show search results
	 *
	 * @param string $search
	 * @param array  $args
	 */
	public function search_results( $search, $args ) {

		extract( shortcode_atts( array(
			'posts_per_page'  => '20',
			'format'          => 'pa',
			'front_orderby'   => 'download_count',
			'direct_download' => 'false'
		), $args ) );

		$dlpage          = ! empty( $_GET['dlpage'] ) ? $_GET['dlpage'] : 1;
		$direct_download = ( $direct_download === 'true' );

		$args = apply_filters( 'dlm_page_addon_search_query_args', array(
			'orderby'        => $front_orderby,
			'order'          => 'asc',
			'posts_per_page' => $posts_per_page,
			'offset'         => $posts_per_page * ( $dlpage - 1 ),
			's'              => $search,
		) );

		if ( function_exists( 'relevanssi_prevent_default_request' ) ) {
			remove_filter( 'posts_request', 'relevanssi_prevent_default_request', 10, 2 );
		}

		// fetch downloads
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $args, $posts_per_page, $posts_per_page * ( $dlpage - 1 ) );

		// make downloads filterable
		$downloads = apply_filters( 'dlm_page_addon_search_results', $downloads );

		$pages = ceil( download_monitor()->service( 'download_repository' )->num_rows( $args ) / $posts_per_page );

		if ( function_exists( 'relevanssi_prevent_default_request' ) ) {
			add_filter( 'posts_request', 'relevanssi_prevent_default_request', 10, 2 );
		}

		// template handler
		$template_handler = new DLM_Template_Handler();

		if ( count( $downloads ) > 0 ) {
			$template_handler->get_template_part( 'download-list', '', $this->plugin_path() . 'templates/', array(
				'format'          => $format,
				'downloads'       => $downloads,
				'direct_download' => $direct_download
			) );
			$template_handler->get_template_part( 'pagination', '', $this->plugin_path() . 'templates/', array( 'pages' => $pages ) );
		} else {
			$template_handler->get_template_part( 'no-downloads-found', '', $this->plugin_path() . 'templates/' );
		}

	}

	/**
	 * Table headers rendering function.
	 *
	 * @param $args
	 *
	 * @return string
	 * @since 4.2.0
	 */
	private function render_table_head( $args ) {

		$html = '';
		if ( isset( $args['headlings'] ) && '' != $args['headlings'] ) {
			$headlings = explode( ',', sanitize_text_field( wp_unslash( $args['headlings'] ) ) );
			if ( ! empty( $headlings ) ) {

				foreach ( $headlings as $thead ) {
					if ( isset( $args[ $thead ] ) ) {
						$html .= '<th>' . esc_html( $args[ $thead ] ) . '</th>';
					}
				}
			}
			$html .= '<th></th>';
			if ( ! empty( $html ) ) {

				return $html;
			}
		}

		$html .= '<th>' . esc_html( $args['head_id'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_title'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_author'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_description'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_count'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_featured'] ) . '</th>';
		$html .= '<th>' . esc_html( $args['head_date'] ) . '</th>';
		$html .= '<th></th>';

		return $html;
	}

	/**
	 * Table body rendering function.
	 *
	 * @param $query_args
	 * @param $args
	 *
	 * @return string
	 * @since 4.2.0
	 */
	private function render_table_body( $query_args, $args ) {
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $query_args );
		$html      = '';
		foreach ( $downloads as $download ) {

			if ( isset( $args['headlings'] ) && '' != $args['headlings'] ) {
				$headlings = explode( ',', sanitize_text_field( wp_unslash( $args['headlings'] ) ) );
				if ( ! empty( $headlings ) ) {
					$html .= '<tr>';
					foreach ( $headlings as $item ) {

						if ( isset( $item ) ) {
							switch ( $item ) {
								case 'head_id':
									$html .= '<td>' . absint( $download->get_id() ) . '</td>';
									break;
								case 'head_title':
									$html .= '<td>' . esc_html( $download->get_title() ) . '</td>';
									break;
								case 'head_author':
									$html .= '<td>' . esc_html( get_the_author_meta( 'nickname', $download->get_author() ) ) . '</td>';
									break;
								case 'head_description':
									$description = $download->get_description() ? $download->get_description() : esc_html__( 'No description', 'dlm-page-addon' );
									$html        .= '<td>' . wp_kses_post( $description ) . '</td>';
									break;
								case 'head_count':
									$html .= '<td>' . absint( $download->get_download_count() ) . '</td>';
									break;
								case 'head_featured':
									$featured = $download->is_featured() ? esc_html__( 'Yes', 'dlm-page-addon' ) : esc_html__( 'No', 'dlm-page-addon' );
									$html     .= '<td>' . esc_html( $featured ) . '</td>';
									break;
								case 'head_date':
									$html .= '<td>' . esc_html( $download->post->post_date ) . '</td>';
									break;
								default:
									$html .= apply_filters( 'dlm_page_addon_list_value', '', $args, $item, $download );
									break;
							}
						}
					}
					$download_link   = $download->get_the_download_link();
					$download_button = apply_filters( 'dlm_page_addon_list_button', '<td><a class="button" href="' . $download_link . '">' . 'Download' . '</a></td>', $download );
					$html            .= $download_button;
					$html            .= '</tr>';
				}
			} else {
				$featured        = $download->is_featured() ? esc_html__( 'Yes', 'dlm-page-addon' ) : esc_html__( 'No', 'dlm-page-addon' );
				$description     = $download->get_description() ? $download->get_description() : esc_html__( 'No description', 'dlm-page-addon' );
				$download_link   = $download->get_the_download_link();
				$download_button = apply_filters( 'dlm_page_addon_list_button', '<td><a class="button" href="' . $download_link . '">' . 'Download' . '</a></td>', $download );
				$html            .= '<tr>';
				$html            .= '<td>' . absint( $download->get_id() ) . '</td>';
				$html            .= '<td>' . esc_html( $download->get_title() ) . '</td>';
				$html            .= '<td>' . esc_html( get_the_author_meta( 'nickname', $download->get_author() ) ) . '</td>';
				$html            .= '<td>' . wp_kses_post( $description ) . '</td>';
				$html            .= '<td>' . absint( $download->get_download_count() ) . '</td>';
				$html            .= '<td>' . esc_html( $featured ) . '</td>';
				$html            .= '<td>' . esc_html( $download->post->post_date ) . '</td>';
				$html            .= wp_kses_post( $download_button );
				$html            .= '</tr>';
			}
		}

		return $html;
	}

	/**
	 * Download List Shortcode Handler
	 */
	public function download_list( $args ) {

		wp_enqueue_style( 'dlm-page-addon-frontend' );

		$default_args = array(
			'head_id'          => __( 'ID', 'dlm-page-addon' ),
			'head_title'       => __( 'Title', 'dlm-page-addon' ),
			'head_author'      => __( 'Author', 'dlm-page-addon' ),
			'head_description' => __( 'Description', 'dlm-page-addon' ),
			'head_count'       => __( 'Download Count', 'dlm-page-addon' ),
			'head_featured'    => __( 'Featured', 'dlm-page-addon' ),
			'head_date'        => __( 'Date', 'dlm-page-addon' ),
		);

		$args = wp_parse_args( $args, $default_args );

		$query_args = array(
			'orderby' => 'title',
			'order'   => 'asc',
		);
		if ( isset( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'dlm_download_category',
					'field'    => 'slug',
					'terms'    => $args['category'],
				)
			);
		}

		$html = '<table>';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= $this->render_table_head( $args );
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		$html .= $this->render_table_body( $query_args, $args );
		$html .= '</tbody>';
		$html .= '</table>';

		return $html;

	}

	/**
	 * Display admin notices
	 *
	 * @return void
	 */
	public function admin_notices() {

		if ( ! class_exists( 'WP_DLM' ) ) {
			?>
			<div class="error">
				<p><?php _e( 'Download Monitor - Page Addon requires Download Monitor to work.', 'dlm-page-addon' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Check extensions functionality
	 *
	 * @return bool
	 *
	 * @since 4.1.8
	 */
	public function check_functionality() {
		if ( class_exists( 'DLM_Admin_Helper' ) ) {
			$admin_helper = DLM_Admin_Helper::get_instance();
			if ( method_exists( $admin_helper, 'check_license_validity' ) ) {
				if ( ! $admin_helper->check_license_validity( 'dlm-page-addon' ) ) {
					return false;
				}
			}
		}

		return true;
	}
}

// include vendor autoload
require_once dirname( __FILE__ ) . '/vendor/autoload_52.php';

function _dlm_page_addon_main() {

	// define plugin file
	define( 'DLM_PA_FILE', __FILE__ );
	define( 'DLM_PA_PATH', plugin_dir_path( __FILE__ ) );
	define( 'DLM_PA_URL', plugin_dir_url( __FILE__ ) );
	require_once __DIR__ . '/includes/Product/class-dlm-product-child.php';
	require_once __DIR__ . '/includes/class-dlm-extensions-notices.php';
	require_once __DIR__ . '/includes/class-dlm-page-addon-gutenberg.php';
	require_once __DIR__ . '/includes/PROInstall/class-dlm-pro-install.php';

	// Include modal class if XHR & modal are enabled.
	if ( class_exists( 'WP_DLM' ) && method_exists( 'WP_DLM', 'do_xhr' ) && WP_DLM::do_xhr() && get_option( 'dlm_no_access_modal', false ) ) {
		require_once __DIR__ . '/includes/class-dlm-page-addon-modal.php';
	}

	$GLOBALS['dlm_page_addon'] = WP_DLM_Page_Addon::instance();
}

add_action( 'plugins_loaded', '_dlm_page_addon_main', 11 );

// run on activation
register_activation_hook( __FILE__, array( 'WP_DLM_Page_Addon', 'activation' ) );
register_activation_hook( __FILE__, 'dlm_page_addon_activate_plugin' );

register_deactivation_hook( __FILE__, 'dlm_page_addon_deactivate_plugin' );


function dlm_page_addon_deactivate_plugin() {
	// Activate extension license if exists.
	if ( class_exists( 'DLM_PRO_Extensions_Handler' ) ) {
		$extensions = DLM_PRO_Extensions_Handler::get_instance();
		$extensions->handle_extension_action( 'deactivate', array(
			'slug' => 'dlm-page-addon',
			'name' => 'Page Addon'
		) );
	}
}

function dlm_page_addon_activate_plugin() {

	// Activate extension license if exists.
	if ( class_exists( 'DLM_PRO_Extensions_Handler' ) ) {
		$extensions = DLM_PRO_Extensions_Handler::get_instance();
		$extensions->handle_extension_action( 'activate', array(
			'slug' => 'dlm-page-addon',
			'name' => 'Page Addon'
		) );
	}
}