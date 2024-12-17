<?php
namespace WPDeveloper\BetterDocs\Editors\Elementor\Widget\Basic;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Group_Control_Background;
use Elementor\Controls_Manager;
use WPDeveloper\BetterDocs\Editors\Elementor\BaseWidget;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class SearchForm extends BaseWidget {
	public $view_wrapper = 'betterdocs-search-form-wrapper';

	public function get_name() {
		return 'betterdocs-search-form';
	}

	public function get_title() {
		return __( 'BetterDocs Search Form', 'betterdocs' );
	}

	public function get_categories() {
		return [ 'betterdocs-elements', 'docs-archive', 'betterdocs-elements-single' ];
	}

	public function get_icon() {
		return 'betterdocs-icon-search';
	}

	public function get_style_depends() {
		return [ 'betterdocs-search', 'betterdocs-search-modal' ];
	}

	public function get_script_depends() {
		return [ 'betterdocs-search', 'betterdocs-pro', 'betterdocs-search-modal', 'betterdocs-category-grid', 'betterdocs-extend-search-modal' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @return array Widget keywords.
	 * @since  3.5.2
	 * @access public
	 *
	 */
	public function get_keywords() {
		return [
			'knowledgebase',
			'knowledge Base',
			'documentation',
			'doc',
			'kb',
			'betterdocs',
			'search',
			'search form'

		];
	}

	public function get_custom_help_url() {
		return 'https://betterdocs.co/docs/single-doc-in-elementor';
	}

	protected function register_controls() {
		$this->layout_selection();
		$this->search_modal_query();
		$this->search_content_settings();
		$this->search_box_layout_1();
		$this->search_field_layout_1();
		$this->search_result_box_layout_1();
		$this->search_result_list_layout_1();

		$this->search_box_layout_2();
		$this->search_field_layout_2();
		$this->search_modal_layout();
	}

	public function layout_selection() {
		$this->start_controls_section(
			'layout_selection_section',
			[
				'label' => __( 'Search Layout', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_CONTENT
			]
		);

		$this->add_control(
			'layout_select',
			[
				'label'       => esc_html__( 'Select layout', 'betterdocs' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'layout-2',
				'label_block' => false,
				'options'     => [
					'layout-1' => esc_html__( 'Classic Layout', 'betterdocs' ),
					'layout-2' => esc_html__( 'Modal Layout', 'betterdocs' )
				]
			]
		);

		$this->end_controls_section();
	}

	public function search_modal_query() {
		$this->start_controls_section(
			'search_modal_query',
			[
				'label'     => __( 'Modal Query', 'betterdocs' ),
				'condition' => [
					'layout_select' => [ 'layout-2' ]
				]
			]
		);

		// $this->add_control(
		//     'include_doc_categories',
		//     [
		//         'label'       => __( 'Doc Categories', 'betterdocs' ),
		//         'label_block' => true,
		//         'type'        => Controls_Manager::SELECT2,
		//         'options'     => array_reduce( get_terms( ['taxonomy' => 'doc_category', 'hide_empty' => true] ), [$this, 'return_mod_terms'] ),
		//         'multiple'    => true,
		//         'default'     => []
		//     ]
		// );

		// $this->add_control(
		//     'include_faq',
		//     [
		//         'label'       => __( 'FAQ', 'betterdocs' ),
		//         'label_block' => true,
		//         'type'        => Controls_Manager::SELECT2,
		//         'options'     => array_reduce( get_terms(['taxonomy' => 'betterdocs_faq_category']),  [$this, 'return_mod_terms'] ),
		//         'multiple'    => true,
		//         'default'     => []
		//     ]
		// );

		$this->add_control(
			'search_modal_doc_query_type',
			[
				'label'       => __( 'Select Docs Type', 'betterdocs' ),
				'label_block' => true,
				'type'        => Controls_Manager::SELECT2,
				'options'     => [
					'popular_docs'          => __( 'Popular Docs', 'betterdocs' ),
					'specific_doc_ids'      => __( "Specific Doc Id's", 'betterdocs' ),
					'specific_doc_term_ids' => __( "Specific Doc Term Id's", 'betterdocs' )
				],
				'multiple'    => false,
				'default'     => 'popular_docs'
			]
		);

		$this->add_control(
			'search_modal_query_term_ids',
			[
				'label'       => __( "Doc Term ID's", 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Example: 8, 9', 'betterdocs' ),
				'default'     => '',
				'condition'   => [
					'search_modal_doc_query_type' => 'specific_doc_term_ids'
				]
			]
		);

		$this->add_control(
			'search_modal_query_doc_ids',
			[
				'label'       => __( "Doc ID's", 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Example: 15, 16', 'betterdocs' ),
				'default'     => '',
				'condition'   => [
					'search_modal_doc_query_type' => 'specific_doc_ids'
				]
			]
		);

		$this->add_control(
			'initial_docs_number',
			[
				'label'     => __( 'Number of Docs', 'betterdocs' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => '5',
				'condition' => [
					'search_modal_doc_query_type' => 'popular_docs'
				]
			]
		);

		$this->add_control(
			'search_modal_faq_query_type',
			[
				'label'       => __( 'Select FAQ Type', 'betterdocs' ),
				'label_block' => true,
				'type'        => Controls_Manager::SELECT2,
				'default'     => 'default',
				'options'     => [
					'default'               => __( 'Default', 'betterdocs' ),
					'specific_faq_term_ids' => __( "Specific FAQ Term Id's", 'betterdocs' ),
				],
				'multiple'    => false,
			]
		);

		$this->add_control(
			'search_modal_query_faq_term_ids',
			[
				'label'       => __( "FAQ Term ID's", 'betterdocs' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Example: 8, 9', 'betterdocs' ),
				'default'     => '',
				'condition'   => [
					'search_modal_faq_query_type' => 'specific_faq_term_ids'
				]
			]
		);

		$this->add_control(
			'initial_faqs_number',
			[
				'label'     => __( "Number of FAQ's", 'betterdocs' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => '5',
				'condition' => [
					'search_modal_faq_query_type' => 'default'
				]
			]
		);

		$this->end_controls_section();
	}

	public function return_mod_terms( $accumulator, $term ) {
		$accumulator[ $term->term_id ] = htmlspecialchars_decode( $term->name );
		return $accumulator;
	}


	public function search_content_settings() {
		$this->start_controls_section(
			'search_content_placeholders',
			[
				'label' => __( 'Search Content', 'betterdocs' ),
				'tab'   => Controls_Manager::TAB_CONTENT
			]
		);

		$this->add_control(
			'section_search_field_placeholder',
			[
				'label'   => __( 'Placeholder', 'betterdocs' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Search', 'betterdocs' )
			]
		);

		$this->add_control(
			'section_search_field_heading',
			[
				'label' => __( 'Search Heading', 'betterdocs' ),
				'type'  => Controls_Manager::TEXT
			]
		);

		$this->add_control(
			'section_search_field_sub_heading',
			[
				'label' => __( 'Search Subheading', 'betterdocs' ),
				'type'  => Controls_Manager::TEXT
			]
		);

		$this->add_control(
			'betterdocs_search_button_toogle',
			[
				'label'        => __( 'Enable Search Button', 'betterdocs' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'betterdocs' ),
				'label_off'    => __( 'Off', 'betterdocs' ),
				'return_value' => 'true',
				'default'      => true
			]
		);

		do_action( 'betterdocs/elementor/widgets/advanced-search/switcher', $this );

		$this->end_controls_section();
	}

	public function search_box_layout_1() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Box
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_box_settings',
			[
				'label'     => __( 'Search Box', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-1'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_box_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-search-form-wrapper'
			]
		);

		$this->add_responsive_control(
			'search_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 50,
					'right'  => 50,
					'bottom' => 50,
					'left'   => 50
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 50,
					'right'  => 50,
					'bottom' => 50,
					'left'   => 50
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-form-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Search Box'
	}

	public function search_box_layout_2() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Box
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_box_settings_layout_2',
			[
				'label'     => __( 'Search Box', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-2'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_box_bg_layout_2',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-search-layout-1'
			]
		);

		$this->add_responsive_control(
			'search_box_padding_layout_2',
			[
				'label'      => esc_html__( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 50,
					'right'  => 50,
					'bottom' => 50,
					'left'   => 50
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_box_margin_layout_2',
			[
				'label'      => esc_html__( 'Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 50,
					'right'  => 50,
					'bottom' => 50,
					'left'   => 50
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Search Box'
	}

	public function search_field_layout_1() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Field
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_field_settings',
			[
				'label'     => __( 'Search Field', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-1'
				]
			]
		);

		$this->add_control(
			'search_field_bg',
			[
				'label'     => esc_html__( 'Field Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-searchform' => 'background: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_field_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-searchform .betterdocs-search-field' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_field_text_typography',
				'selector' => '{{WRAPPER}} .betterdocs-searchform .betterdocs-search-field'
			]
		);

		$this->add_responsive_control(
			'search_field_padding',
			[
				'label'      => __( 'Field Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform .betterdocs-search-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'search_field_placeholder',
			[
				'label'     => esc_html__( 'Field Placeholder Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-searchform .betterdocs-search-field::placeholder' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_box_outer_margin',
			[
				'label'      => __( 'Search Box Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'advanced_search_padding',
			[
				'label'      => __( 'Search Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'advanced_search_border',
				'label'    => esc_html__( 'Search Box Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-searchform'
			]
		);

		$this->add_control(
			'search_box_outer_width',
			[
				'label'      => esc_html__( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform' => 'width: {{SIZE}}{{UNIT}}; height: auto;'
				]
			]
		);

		$this->add_responsive_control(
			'search_field_padding_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'field_search_icon_heading',
			[
				'label'     => esc_html__( 'Search Icon', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'field_search_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-searchform svg.docs-search-icon' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'field_search_icon_size',
			[
				'label'      => esc_html__( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-searchform svg.docs-search-icon' => 'width: {{SIZE}}{{UNIT}}; height: auto;'
				]
			]
		);

		$this->add_control(
			'field_close_icon_heading',
			[
				'label'     => esc_html__( 'Close Icon', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'search_field_close_icon_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-search-close .close-line' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_field_close_icon_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .docs-search-loader, {{WRAPPER}} .docs-search-close .close-border' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section(); # end of 'Search Field'
	}

	public function search_field_layout_2() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Field
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_field_settings_layout_2',
			[
				'label'     => __( 'Search Field', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-2'
				]
			]
		);

		$this->add_control(
			'search_field_bg_layout_2',
			[
				'label'     => esc_html__( 'Field Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'background: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_field_text_color_layout_2',
			[
				'label'     => esc_html__( 'Text Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-input-wrapper .search-input' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_field_text_typography_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-input-wrapper .search-input'
			]
		);

		$this->add_responsive_control(
			'search_field_padding_layout_2',
			[
				'label'      => __( 'Field Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_box_outer_margin_layout_2',
			[
				'label'      => __( 'Search Box Margin', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'advanced_search_padding_layout_2',
			[
				'label'      => __( 'Search Box Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'advanced_search_border_layout_2',
				'label'    => esc_html__( 'Search Box Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-search-layout-1 .search-bar'
			]
		);

		$this->add_control(
			'search_box_outer_width_layout_2',
			[
				'label'      => esc_html__( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'width: {{SIZE}}{{UNIT}}; height: auto;'
				]
			]
		);

		$this->add_responsive_control(
			'search_field_padding_radius_layout_2',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'field_search_icon_heading_layout_2',
			[
				'label'     => esc_html__( 'Search Icon', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'field_search_icon_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-input-wrapper .search-icon g path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'field_search_icon_size_layout_2',
			[
				'label'      => esc_html__( 'Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 500
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-input-wrapper .search-icon' => 'width: {{SIZE}}{{UNIT}}; height: auto;'
				]
			]
		);

		$this->add_control(
			'field_search_button_heading_layout_2',
			[
				'label'     => esc_html__( 'Search Button', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'field_search_button_color_layout_2',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-button' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'field_search_button_background_color_layout_2',
			[
				'label'     => esc_html__( 'Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-button' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'field_search_button_border_radius_layout_2',
			[
				'label'      => esc_html__( 'Border Radius', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_search_button_typography_layout_2',
				'selector' => '{{WRAPPER}} .betterdocs-search-layout-1 .search-bar .search-button'
			]
		);

		$this->end_controls_section(); # end of 'Search Field'
	}

	public function search_modal_layout() {
		$this->start_controls_section(
			'search_modal',
			[
				'label'     => __( 'Search Modal', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-2'
				]
			]
		);

		$this->add_control(
			'search_modal_field',
			[
				'label'     => esc_html__( 'Search Field', 'betterdocs' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_magnifier_color',
			[
				'label'     => esc_html__( 'Magnifier Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header svg g path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_field_background_color',
			[
				'label'     => esc_html__( 'Field Background Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_modal_field_typography',
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field'
			]
		);

		$this->add_responsive_control(
			'search_modal_field_color',
			[
				'label'     => esc_html__( 'Field Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_modal_field_placeholder_color',
			[
				'label'     => esc_html__( 'Field Placeholder Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-searchform-input-wrap .betterdocs-search-field::placeholder' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_modal_category_section',
			[
				'label'     => esc_html__( 'Search Category', 'betterdocs' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_modal_categories_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_modal_categories_typography',
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-header .betterdocs-select-option-wrapper .betterdocs-form-select'
			]
		);

		$this->add_control(
			'search_modal_content_tabs',
			[
				'label'     => esc_html__( 'Content Tabs', 'betterdocs' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_modal_content_tabs_typography',
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span'
			]
		);

		$this->add_control(
			'search_modal_content_tabs_icon_size',
			[
				'label'      => __( 'Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5
					],
					'%'  => [
						'min' => 0,
						'max' => 100
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'search_modal_content_tabs_icon_colors',
			[
				'label'     => esc_html__( 'Icon Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items span svg path' => 'fill: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_modal_content_active_tab_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-info-tab .betterdocs-tab-items.active'
			]
		);

		$this->add_control(
			'search_modal_content_list',
			[
				'label'     => esc_html__( 'Content List', 'betterdocs' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_modal_content_list_typography',
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4'
			]
		);

		$this->add_responsive_control(
			'search_modal_content_list_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main h4' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_modal_content_list_icon_size',
			[
				'label'      => __( 'Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5
					],
					'%'  => [
						'min' => 0,
						'max' => 100
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-main svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_modal_content_list_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list'
			]
		);

		$this->add_control(
			'search_modal_content_list_category',
			[
				'label'     => esc_html__( 'Content List Category', 'betterdocs' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_modal_content_list_category_typography',
				'selector' => '{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5'
			]
		);

		$this->add_responsive_control(
			'search_modal_content_list_category_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub h5' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'search_modal_content_list_category_icon_size',
			[
				'label'      => __( 'Icon Size', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5
					],
					'%'  => [
						'min' => 0,
						'max' => 100
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-search-wrapper .betterdocs-search-details .betterdocs-search-content .betterdocs-search-items-wrapper .betterdocs-search-item-content .betterdocs-search-item-list .content-sub svg' => 'height: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->end_controls_section();
	}

	public function search_result_box_layout_1() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Result Box
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_result_settings',
			[
				'label'     => __( 'Search Result Box', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-1'
				]
			]
		);

		$this->add_responsive_control(
			'result_box_width',
			[
				'label'      => __( 'Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => 100,
					'unit' => '%'
				],
				'size_units' => [ '%', 'px', 'em' ],
				'range'      => [
					'%' => [
						'max'  => 100,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result' => 'width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_responsive_control(
			'result_box_max_width',
			[
				'label'      => __( 'Max Width', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => 1600,
					'unit' => 'px'
				],
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'max'  => 1600,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result' => 'max-width: {{SIZE}}{{UNIT}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'result_box_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result'
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'result_box_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result'
			]
		);

		$this->end_controls_section(); # end of 'Search Result Box'
	}

	public function search_result_list_layout_1() {
		/**
		 * ----------------------------------------------------------
		 * Section: Search Result Item
		 * ----------------------------------------------------------
		 */
		$this->start_controls_section(
			'section_search_result_item_settings',
			[
				'label'     => __( 'Search Result List', 'betterdocs' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout_select' => 'layout-1'
				]
			]
		);

		$this->start_controls_tabs( 'item_settings_tab' );

		// Normal State Tab
		$this->start_controls_tab(
			'item_normal',
			[ 'label' => esc_html__( 'Normal', 'betterdocs' ) ]
		);

		$this->add_control(
			'result_box_item',
			[
				'label' => esc_html__( 'Item', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'result_box_item_typography',
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result li a .betterdocs-search-title'
			]
		);

		$this->add_control(
			'result_box_item_color',
			[
				'label'     => esc_html__( 'Item Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li a .betterdocs-search-title' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'result_item_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result li'
			]
		);

		$this->add_responsive_control(
			'result_box_item_padding',
			[
				'label'      => __( 'Padding', 'betterdocs' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				]
			]
		);

		$this->add_control(
			'search_result_box_item_category',
			[
				'label'     => esc_html__( 'Category', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'result_box_item_category_typography',
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result li span.betterdocs-search-category'
			]
		);

		$this->add_control(
			'result_box_item_category_color',
			[
				'label'     => esc_html__( 'Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li span.betterdocs-search-category' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'item_hover',
			[ 'label' => esc_html__( 'Hover', 'betterdocs' ) ]
		);

		$this->add_responsive_control(
			'result_item_transition',
			[
				'label'      => __( 'Transition', 'betterdocs' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'size' => 300,
					'unit' => '%'
				],
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [
						'max'  => 2500,
						'step' => 1
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li, {{WRAPPER}} .betterdocs-live-search .docs-search-result li a, {{WRAPPER}} .betterdocs-live-search .docs-search-result li span, {{WRAPPER}} .betterdocs-live-search .docs-search-result' => 'transition: {{SIZE}}ms;'
				]
			]
		);

		$this->add_control(
			'result_box_item_hover_heading',
			[
				'label' => esc_html__( 'Item', 'betterdocs' ),
				'type'  => Controls_Manager::HEADING
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'result_box_item_hover_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result li:hover',
				'exclude'  => [
					'image'
				]
			]
		);

		$this->add_control(
			'result_box_item_hover_color',
			[
				'label'     => esc_html__( 'Item Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li:hover a' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'result_item_hover_border',
				'label'    => esc_html__( 'Border', 'betterdocs' ),
				'selector' => '{{WRAPPER}} .betterdocs-live-search .docs-search-result li:hover'
			]
		);

		$this->add_control(
			'result_box_item_hover_count_heading',
			[
				'label'     => esc_html__( 'Count', 'betterdocs' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'result_box_item_hover_count_color',
			[
				'label'     => esc_html__( 'Item Color', 'betterdocs' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .betterdocs-live-search .docs-search-result li:hover span' => 'color: {{VALUE}};'
				]
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section(); # end of 'Search Result Item'

		do_action( 'betterdocs/elementor/widgets/advanced-search/controllers', $this );
	}

	public function render_callback() {
		$settings = &$this->attributes;
		if ( $settings['layout_select'] == 'layout-1' ) {
			$this->views( 'widgets/search-form' );
		} else {
			$number_of_docs     = isset( $settings['initial_docs_number'] ) ? $settings['initial_docs_number'] : '';
			$number_of_faqs     = isset( $settings['initial_faqs_number'] ) ? $settings['initial_faqs_number'] : '';
			$doc_categories_ids = isset( $settings['search_modal_query_term_ids'] ) ? $settings['search_modal_query_term_ids'] : '';
			$doc_ids            = isset( $settings['search_modal_query_doc_ids'] ) ? $settings['search_modal_query_doc_ids'] : '';
			$faq_categories_ids = isset( $settings['search_modal_query_faq_term_ids'] ) ? $settings['search_modal_query_faq_term_ids'] : '';
			// $faq_terms          = isset( $settings['include_faq'] ) ?  implode(',', $settings['include_faq']) : '';
			// $doc_terms          = isset( $settings['include_doc_categories'] ) ? implode(',', $settings['include_doc_categories'] ) : '';
			echo do_shortcode( '[betterdocs_search_modal faq_categories_ids="' . $faq_categories_ids . '" doc_ids="' . $doc_ids . '" doc_categories_ids="' . $doc_categories_ids . '" search_button="' . ( isset( $settings['betterdocs_search_button_toogle'] ) ? $settings['betterdocs_search_button_toogle'] : true ) . '" number_of_docs="' . $number_of_docs . '" number_of_faqs="' . $number_of_faqs . '" heading_tag="h2" subheading_tag="h3" search_button_text="Search" layout="layout-1" heading="' . ( isset( $settings['section_search_field_heading'] ) ? $settings['section_search_field_heading'] : '' ) . '" placeholder="' . ( isset( $settings['section_search_field_placeholder'] ) ? $settings['section_search_field_placeholder'] : '' ) . '" subheading="' . ( isset( $settings['section_search_field_sub_heading'] ) ? $settings['section_search_field_sub_heading'] : '' ) . '" category_search="' . ( isset( $settings['betterdocs_category_search_toogle'] ) ? $settings['betterdocs_category_search_toogle'] : false ) . '" popular_search="' . ( isset( $settings['betterdocs_popular_search_toogle'] ) ? $settings['betterdocs_popular_search_toogle'] : false ) . '"]' );
		}
	}

	public function view_params() {
		$settings = &$this->attributes;

		$popular_search_title   = isset( $settings['advance_search_popular_search_title_placeholder'] ) ? $settings['advance_search_popular_search_title_placeholder'] : '';
		$category_search_toggle = isset( $settings['betterdocs_category_search_toogle'] ) ? $settings['betterdocs_category_search_toogle'] : '';
		$search_button_toggle   = isset( $settings['betterdocs_search_button_toogle'] ) ? $settings['betterdocs_search_button_toogle'] : true;
		$popular_search_toggle  = isset( $settings['betterdocs_popular_search_toogle'] ) ? $settings['betterdocs_popular_search_toogle'] : '';

		$_shortcode_attributes = apply_filters(
			'betterdocs_elementor_search_form_params',
			[
				'enable_heading'       => 'true',
				'popular_search_title' => $popular_search_title,
				'category_search'      => $category_search_toggle,
				'search_button'        => $search_button_toggle,
				'popular_search'       => $popular_search_toggle,
				'heading'              => esc_html( $settings['section_search_field_heading'] ),
				'subheading'           => esc_html( $settings['section_search_field_sub_heading'] ),
				'placeholder'          => esc_html( $settings['section_search_field_placeholder'] )
			],
			$this->attributes
		);

		return [
			'shortcode_attr' => $_shortcode_attributes
		];
	}

	// In plain mode, render without shortcode
	public function render_plain_content() {
		$settings = $this->get_settings_for_display();
		echo '[betterdocs_search_form placeholder="' . esc_attr( $settings['section_search_field_placeholder'] ) . '"]';
	}
}
