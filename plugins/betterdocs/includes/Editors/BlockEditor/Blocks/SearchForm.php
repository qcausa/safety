<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class SearchForm extends Block {
	public $view_wrapper = 'betterdocs-search-form-wrapper';

	protected $editor_styles = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];

	protected $frontend_styles  = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];
	protected $frontend_scripts = [
		'betterdocs-search',
		'betterdocs-search-modal'
	];

	/**
	 * unique name of block
	 * @return string
	 */
	public function get_name() {
		return 'searchbox';
	}

	public function get_default_attributes() {
		return [
			'blockId'                       => '',
			'placeholderText'               => __( 'Search', 'betterdocs' ),
			'searchLayout'                  => 'layout-1',
			'searchHeading'                 => '',
			'searchSubHeading'              => '',
			'searchButtonLayout2'           => false, //for search modal
			'categorySearchLayout2'         => false, //for search modal
			'popularSearchLayout2'          => false, //for search modal,
			'initialFAQNumber'              => 5, //for search modal,
			'initialDocsNumber'             => 5, //for search modal,
			'searchModalQueryTermIds'       => '', //for search modal
			'searchModalQueryDocIds'        => '', //for search modal
			'searchModalQueriesFaqGroupIds' => ''
		];
	}

	public function render( $attributes, $content ) {
		$settings = $this->attributes;
		if ( isset( $settings['searchLayout'] ) && $settings['searchLayout'] == 'layout-1' ) {
			$this->views( 'widgets/search-form' );
		} else {
			$popular_search     = isset( $settings['popularSearchLayout2'] ) ? $settings['popularSearchLayout2'] : false;
			$category_search    = isset( $settings['categorySearchLayout2'] ) ? $settings['categorySearchLayout2'] : false;
			$search_button      = isset( $settings['searchButtonLayout2'] ) ? $settings['searchButtonLayout2'] : false;
			$number_of_docs     = isset( $settings['initialDocsNumber'] ) ? $settings['initialDocsNumber'] : 5;
			$number_of_faqs     = isset( $settings['initialFAQNumber'] ) ? $settings['initialFAQNumber'] : 5;
			$doc_categories_ids = isset( $settings['searchModalQueryTermIds'] ) ? $settings['searchModalQueryTermIds'] : '';
			$doc_ids            = isset( $settings['searchModalQueryDocIds'] ) ? $settings['searchModalQueryDocIds'] : '';
			$faq_categories_ids = isset( $settings['searchModalQueriesFaqGroupIds'] ) ? $settings['searchModalQueriesFaqGroupIds'] : '';
			$searchHeading      = isset( $settings['searchHeading'] ) ? $settings['searchHeading'] : '';
			$subHeading         = isset( $settings['searchSubHeading'] ) ? $settings['searchSubHeading'] : '';
			echo '<div class="' . esc_attr( $settings['blockId'] ) . '">';
			echo do_shortcode( '[betterdocs_search_modal faq_categories_ids="' . $faq_categories_ids . '" doc_ids="' . $doc_ids . '" doc_categories_ids="' . $doc_categories_ids . '" number_of_docs="' . $number_of_docs . '" number_of_faqs="' . $number_of_faqs . '" search_button_text="Search" search_button="' . $search_button . '" popular_search="' . $popular_search . '" category_search="' . $category_search . '" layout="layout-1" heading="' . $searchHeading . '" subheading="' . $subHeading . '" heading_tag="h2" subheading_tag="h2" placeholder="' . $settings['placeholderText'] . '"]' );
			echo '</div>';
		}
	}

	public function view_params() {
		$settings = &$this->attributes;

		$_shortcode_attributes = apply_filters(
			'betterdocs_elementor_search_form_params',
			[
				'placeholder' => esc_html( $settings['placeholderText'] )
			],
			$this->attributes
		);

		return [
			'shortcode_attr' => $_shortcode_attributes
		];
	}
}
