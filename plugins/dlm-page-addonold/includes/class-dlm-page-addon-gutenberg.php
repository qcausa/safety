<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Page_Addon_Gutenberg {

	public function __construct() {
		add_action( 'init', array( $this, 'load' ), 99 );
	}

	public function load() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		$assets = array(
			'dependencies' =>
				array( 'react', 'react-dom', 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-data' ),
			'version'      => 'c6e49b7ce15319ddd68536bb819b38b8',
		);

		// register Gutenberg JS
		wp_register_script(
			'dlm_page_addon_gutenberg_block',
			DLM_PA_URL . 'assets/blocks/dist/blocks.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_register_style(
			'dlm_page_addon_gutenberg_block-editor',
			DLM_PA_URL . 'assets/css//blocks.editor.css',
			array( 'wp-edit-blocks' )
		);

		// register the block in PHP
		register_block_type(
			'dlm-page-addon/downloads-list-table',
			array(
				'editor_style'    => 'dlm_page_addon_gutenberg_block-editor',
				'editor_script'   => 'dlm_page_addon_gutenberg_block',
				'render_callback' => array( $this, 'render_download_button' ),
			)
		);

		wp_add_inline_script( 'dlm_page_addon_gutenberg_block', 'const dlmPARestUrl = "' . esc_url_raw( get_rest_url( 'admin-ajax.php' ) )  . '";', 'before' );
		wp_set_script_translations( 'dlm_page_addon_gutenberg_block', 'dlm-page-addon', DLM_PA_FILE . 'languages' );

	}

	public function render_download_button( $args ) {

		wp_enqueue_style( 'dlm-page-addon-frontend' );
		$default_args = array(
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		$default_block_args = array(
			'headers' =>
				array(
					array(
						'label' => 'Author',
						'name'  => 'author',
						'show'  => true,
					),
					array(
						'label' => 'Title',
						'name'  => 'title',
						'show'  => true,
					),
					array(
						'label' => 'ID',
						'name'  => 'id',
						'show'  => true,
					),
					array(
						'label' => 'Description',
						'name'  => 'description',
						'show'  => true,
					),
					array(
						'label' => 'Download Count',
						'name'  => 'downloadCount',
						'show'  => true,
					),
					array(
						'label' => 'Date',
						'name'  => 'date',
						'show'  => true,
					),
					array(
						'label' => 'Actions',
						'name'  => 'actions',
						'show'  => true,
					),
				),
		);

		$query_args = wp_parse_args( $args, $default_args );
		$block_args = wp_parse_args( $args, $default_block_args );

		// Unset what we do not need to transmit to query or what is not properly formated.
		unset( $query_args['headers'] );
		unset( $query_args['orderby'] );
		unset( $query_args['order'] );
		unset( $query_args['category'] );
		unset( $query_args['className'] );

		if ( isset( $args['category'] ) && ! empty( $args['category']['id'] && 0 !== $args['category']['id'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'dlm_download_category',
					'field'    => 'term_id',
					'terms'    => $args['category']['id'],
				),
			);
		}

		// @todo: Orderby and order for next iteration
		/* if ( isset( $args['orderby'] ) ) {
			$query_args['orderby'] = $args['orderby']['value'];
		}

		if ( isset( $args['order'] ) ) {
			$query_args['order'] = $args['order']['value'];
		} */

		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $query_args );

		$html  = '<table class="' . ( isset( $block_args['className'] ) ? esc_attr( $block_args['className'] ) : '' ) . '">';
		$html .= '<thead>';
		$html .= '<tr>';
		foreach ( $block_args['headers'] as $header ) {
			if ( isset( $header['show'] ) && $header['show'] ) {
				$html .= '<th>' . esc_html( $header['label'] ) . '</th>';
			}
		}
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		foreach ( $downloads as $download ) {
			$download_link = $download->get_the_download_link();

			$table_content = array(
				'author'        => esc_html( $download->get_the_author() ),
				'title'         => esc_html( $download->get_the_title() ),
				'id'            => absint( $download->get_id() ),
				'description'   => $download->get_description() ? wp_kses_post( $download->get_description() ) : esc_html__( 'No description', 'dlm-page-addon' ),
				'downloadCount' => absint( $download->get_download_count() ),
				'date'          => esc_html( $download->post->post_date ),
				'actions'       => wp_kses_post( apply_filters( 'dlm_page_addon_list_button', '<a class="button" href="' . esc_url( $download_link ) . '">' . esc_html__( 'Download', 'dlm-page-addon' ) . '</a>', $download ) ),
			);

			$html .= '<tr>';

			foreach ( $block_args['headers'] as $header ) {
				if ( $header['show'] ) {
					$html .= '<td>' . wp_kses_post( $table_content[ $header['name'] ] ) . '</td>';
				}
			}

			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';

		return $html;
	}

}

new DLM_Page_Addon_Gutenberg();
