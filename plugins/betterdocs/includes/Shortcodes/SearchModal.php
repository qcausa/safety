<?php

namespace WPDeveloper\BetterDocs\Shortcodes;

use WPDeveloper\BetterDocs\Core\Query;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\Core\Shortcode;
use WPDeveloper\BetterDocs\Admin\Customizer\Defaults;

class SearchModal extends Shortcode {
	public function __construct( Settings $settings, Query $query, Helper $helper, Defaults $defaults ) {
		parent::__construct( $settings, $query, $helper, $defaults );

		add_action( 'wp_ajax_nopriv_betterdocs_get_search_result', [ $this, 'get_search_results' ] );
		add_action( 'wp_ajax_betterdocs_get_search_result', [ $this, 'get_search_results' ] );
	}

	public function get_style_depends() {
		return [ 'betterdocs-search-modal' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-search-modal' ];
	}

	public function get_name() {
		return 'betterdocs_search_modal';
	}

	/**
	 * Summary of default_attributes
	 * @return array
	 */
	public function default_attributes() {
		return apply_filters(
			'betterdocs_search_modal_default_attr',
			[
				'placeholder'        => __( 'Search Doc', 'betterdocs' ),
				'heading'            => '',
				'subheading'         => '',
				'heading_tag'        => 'h2',
				'subheading_tag'     => 'h3',
				'number_of_docs'     => '5',
				'number_of_faqs'     => '5',
				'search_button_text' => __( 'Search', 'betterdocs' ),
				'faq_categories_ids' => '',
				'layout'             => 'layout-1',
				'doc_ids'            => '',
				'doc_categories_ids' => ''
			]
		);
	}

	public function render( $atts, $content = null ) {
		betterdocs()->assets->localize(
			'betterdocs-search-modal',
			'searchModalConfig',
			[
				'nonce' => wp_create_nonce( 'wp_rest' )
			]
		);

		if ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-1' ) {
			$attributes = [
				'placeholder'        => $atts['placeholder'],
				'heading'            => $atts['heading'],
				'subheading'         => $atts['subheading'],
				'headingtag'         => $atts['heading_tag'],
				'subheadingtag'      => $atts['subheading_tag'],
				'buttontext'         => isset( $atts['search_button_text'] ) ? $atts['search_button_text'] : '',
				'numberofdocs'       => isset( $atts['number_of_docs'] ) ? $atts['number_of_docs'] : 5,
				'numberoffaqs'       => isset( $atts['number_of_faqs'] ) ? $atts['number_of_faqs'] : 5,
				'faq_categories_ids' => isset( $atts['faq_categories_ids'] ) ? $atts['faq_categories_ids'] : '',
				'doc_ids'            => isset( $atts['doc_ids'] ) ? $atts['doc_ids'] : '',
				'doc_categories_ids' => isset( $atts['doc_categories_ids'] ) ? $atts['doc_categories_ids'] : ''
			];
			$attributes = apply_filters( 'betterdocs_search_modal_shortcode_attributes', $attributes );
			echo '<div class="betterdocs-search-modal-layout-1" id="betterdocs-search-modal"';
			foreach ( $attributes as $key => $value ) {
				if ( ! empty( $value ) ) {
					echo ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
			echo '></div>';
		} elseif ( isset( $atts['layout'] ) && $atts['layout'] == 'sidebar' ) {
			$attributes = [
				'placeholder'        => $atts['placeholder'],
				'numberofdocs'       => isset( $atts['number_of_docs'] ) ? $atts['number_of_docs'] : 5,
				'numberoffaqs'       => isset( $atts['number_of_faqs'] ) ? $atts['number_of_faqs'] : 5,
				'faq_categories_ids' => isset( $atts['faq_categories_ids'] ) ? $atts['faq_categories_ids'] : '',
				'doc_ids'            => isset( $atts['doc_ids'] ) ? $atts['doc_ids'] : '',
				'doc_categories_ids' => isset( $atts['doc_categories_ids'] ) ? $atts['doc_categories_ids'] : ''
			];
			$attributes = apply_filters( 'betterdocs_search_modal_shortcode_attributes', $attributes );

			echo '<div class="betterdocs-search-modal-sidebar" id="betterdocs-search-modal"';
			foreach ( $attributes as $key => $value ) {
				if ( ! empty( $value ) ) {
					echo ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
			echo '></div>';
		}
	}
}
