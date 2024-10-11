<?php

namespace WPDeveloper\BetterDocs\Admin\Customizer\Sections;

use WPDeveloper\BetterDocs\Admin\Customizer\Controls\ToggleControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\AlphaColorControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\SeparatorControl;
use  WPDeveloper\BetterDocs\Admin\Customizer\Sections\Section;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\RangeValueControl;
use WPDeveloper\BetterDocs\Admin\Customizer\Controls\MultiDimensionControl;



class SearchModal extends Section {

     /**
     * Section Priority
     * @var int
     */
    protected $priority = 510;

    /**
     * Get the title of the section.
     * @return string
     */
    public function get_title() {
        return __( 'Search Modal', 'betterdocs' );
    }

      /**
     * Get the section id.
     * @return string
     */
    public function get_id() {
        return 'betterdocs_search_modal_settings';
    }

    public function modal_wrapper_section() {
        $this->customizer->add_setting( 'modal_wrapper_section', [
            'default'           => $this->defaults['modal_wrapper_section'],
            'sanitize_callback' => 'esc_html'
        ] );

        $this->customizer->add_control( new SeparatorControl(
            $this->customizer, 'modal_wrapper_section', [
                'label'    => __( 'Modal Wrapper', 'betterdocs' ),
                'priority' => 1,
                'settings' => 'modal_wrapper_section',
                'section'  => 'betterdocs_search_modal_settings'
            ]
        ) );
    }

    public function modal_wrapper_background_color() {
        $this->customizer->add_setting( 'modal_wrapper_background_color', [
            'default'           => $this->defaults['modal_wrapper_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'modal_wrapper_background_color',
                [
                    'label'    => __( 'Background Color', 'betterdocs' ),
                    'priority' => 2,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'modal_wrapper_background_color',
                ]
            )
        );
    }

    public function modal_wrapper_padding() {
        $this->customizer->add_setting( 'modal_wrapper_padding', [
            'default'    => $this->defaults['modal_wrapper_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'modal_wrapper_padding',
                [
                    'label'        => __( 'Padding (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'modal_wrapper_padding',
                    'priority'     => 3,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 0,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function modal_wrapper_margin() {
        $this->customizer->add_setting( 'modal_wrapper_margin', [
            'default'    => $this->defaults['modal_wrapper_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'modal_wrapper_margin',
                [
                    'label'        => __( 'Margin (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'modal_wrapper_margin',
                    'priority'     => 4,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 0,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function search_field_wrapper() {
        $this->customizer->add_setting( 'search_field_wrapper', [
            'default'           => $this->defaults['search_field_wrapper'],
            'sanitize_callback' => 'esc_html'
        ] );

        $this->customizer->add_control( new SeparatorControl(
            $this->customizer, 'search_field_wrapper', [
                'label'    => __( 'Search Field', 'betterdocs' ),
                'priority' => 5,
                'settings' => 'search_field_wrapper',
                'section'  => 'betterdocs_search_modal_settings'
            ]
        ) );
    }


    public function search_field_modal_background_color() {
        $this->customizer->add_setting( 'search_field_modal_background_color', [
            'default'           => $this->defaults['search_field_modal_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_field_modal_background_color',
                [
                    'label'    => __( 'Background Color', 'betterdocs' ),
                    'priority' => 6,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_field_modal_background_color',
                ]
            )
        );
    }

    public function search_field_modal_padding() {
        $this->customizer->add_setting( 'search_field_modal_padding', [
            'default'    => $this->defaults['search_field_modal_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_field_modal_padding',
                [
                    'label'        => __( 'Padding (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_field_modal_padding',
                    'priority'     => 7,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 5,
                        'input2' => 5,
                        'input3' => 5,
                        'input4' => 5
                    ]
                ]
            )
        );
    }

    public function search_field_modal_margin() {
        $this->customizer->add_setting( 'search_field_modal_margin', [
            'default'    => $this->defaults['search_field_modal_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_field_modal_margin',
                [
                    'label'        => __( 'Margin (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_field_modal_margin',
                    'priority'     => 8,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 0,
                        'input4' => 0
                    ]
                ]
            )
        );
    }


    public function search_field_modal_text_color() {
        $this->customizer->add_setting( 'search_field_modal_text_color', [
            'default'           => $this->defaults['search_field_modal_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_field_modal_text_color',
                [
                    'label'    => __( 'Color', 'betterdocs' ),
                    'priority' => 9,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_field_modal_text_color',
                ]
            )
        );
    }

    public function search_field_modal_text_font_size() {
        $this->customizer->add_setting( 'search_field_modal_text_font_size', [
            'default'           => $this->defaults['search_field_modal_text_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_field_modal_text_font_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_field_modal_text_font_size',
                'label'       => __( 'Font Size', 'betterdocs' ),
                'priority'    => 10,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_field_modal_maginifier_icon_size(){
        $this->customizer->add_setting( 'search_field_modal_maginifier_icon_size', [
            'default'           => $this->defaults['search_field_modal_maginifier_icon_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_field_modal_maginifier_icon_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_field_modal_maginifier_icon_size',
                'label'       => __( 'Maginifier Icon Size', 'betterdocs' ),
                'priority'    => 11,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }


    public function search_field_categories_wrapper() {
        $this->customizer->add_setting( 'search_field_categories_wrapper', [
            'default'           => $this->defaults['search_field_categories_wrapper'],
            'sanitize_callback' => 'esc_html'
        ] );

        $this->customizer->add_control( new SeparatorControl(
            $this->customizer, 'search_field_categories_wrapper', [
                'label'    => __( 'Search Categories', 'betterdocs' ),
                'priority' => 12,
                'settings' => 'search_field_categories_wrapper',
                'section'  => 'betterdocs_search_modal_settings'
            ]
        ) );
    }

    public function search_field_categories_text_color() {
        $this->customizer->add_setting( 'search_field_categories_text_color', [
            'default'           => $this->defaults['search_field_categories_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_field_categories_text_color',
                [
                    'label'    => __( 'Color', 'betterdocs' ),
                    'priority' => 13,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_field_categories_text_color',
                ]
            )
        );
    }

    public function search_field_categories_background_color() {
        $this->customizer->add_setting( 'search_field_categories_background_color', [
            'default'           => $this->defaults['search_field_categories_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_field_categories_background_color',
                [
                    'label'    => __( 'Background Color', 'betterdocs' ),
                    'priority' => 14,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_field_categories_background_color',
                ]
            )
        );
    }


    public function search_field_categories_font_size(){
        $this->customizer->add_setting( 'search_field_categories_font_size', [
            'default'           => $this->defaults['search_field_categories_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_field_categories_font_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_field_categories_font_size',
                'label'       => __( 'Font Size', 'betterdocs' ),
                'priority'    => 15,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_modal_content_tabs() {
        $this->customizer->add_setting( 'search_modal_content_tabs', [
            'default'           => $this->defaults['search_modal_content_tabs'],
            'sanitize_callback' => 'esc_html'
        ] );

        $this->customizer->add_control( new SeparatorControl(
            $this->customizer, 'search_modal_content_tabs', [
                'label'    => __( 'Content Tabs', 'betterdocs' ),
                'priority' => 16,
                'settings' => 'search_modal_content_tabs',
                'section'  => 'betterdocs_search_modal_settings'
            ]
        ) );
    }


    public function search_modal_content_tabs_text_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_text_color', [
            'default'           => $this->defaults['search_modal_content_tabs_text_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_text_color',
                [
                    'label'    => __( 'Color', 'betterdocs' ),
                    'priority' => 17,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_text_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_background_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_background_color', [
            'default'           => $this->defaults['search_modal_content_tabs_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_background_color',
                [
                    'label'    => __( 'Background Color', 'betterdocs' ),
                    'priority' => 18,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_background_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_text_font_size() {
        $this->customizer->add_setting( 'search_modal_content_tabs_text_font_size', [
            'default'           => $this->defaults['search_modal_content_tabs_text_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_modal_content_tabs_text_font_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_modal_content_tabs_text_font_size',
                'label'       => __( 'Font Size', 'betterdocs' ),
                'priority'    => 19,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_modal_content_tabs_margin() {
        $this->customizer->add_setting( 'search_modal_content_tabs_margin', [
            'default'    => $this->defaults['search_modal_content_tabs_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_modal_content_tabs_margin',
                [
                    'label'        => __( 'Margin (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_modal_content_tabs_margin',
                    'priority'     => 20,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 0,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function search_modal_content_tabs_padding() {
        $this->customizer->add_setting( 'search_modal_content_tabs_padding', [
            'default'    => $this->defaults['search_modal_content_tabs_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_modal_content_tabs_padding',
                [
                    'label'        => __( 'Padding (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_modal_content_tabs_padding',
                    'priority'     => 21,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 28,
                        'input3' => 0,
                        'input4' => 28
                    ]
                ]
            )
        );
    }

    public function search_modal_content_tabs_border() {
        $this->customizer->add_setting( 'search_modal_content_tabs_border', [
            'default'    => $this->defaults['search_modal_content_tabs_border'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_modal_content_tabs_border',
                [
                    'label'        => __( 'Border', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_modal_content_tabs_border',
                    'priority'     => 22,
                    'input_fields' => [
                        'input1' => __( 'border top', 'betterdocs' ),
                        'input2' => __( 'border right', 'betterdocs' ),
                        'input3' => __( 'border bottom', 'betterdocs' ),
                        'input4' => __( 'border left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 1,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function search_modal_content_tabs_border_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_border_color', [
            'default'           => $this->defaults['search_modal_content_tabs_border_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_border_color',
                [
                    'label'    => __( 'Border Color', 'betterdocs' ),
                    'priority' => 23,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_border_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list'],
            'sanitize_callback' => 'esc_html'
        ] );

        $this->customizer->add_control( new SeparatorControl(
            $this->customizer, 'search_modal_content_tabs_docs_list', [
                'label'    => __( 'Content Docs List', 'betterdocs' ),
                'priority' => 24,
                'settings' => 'search_modal_content_tabs_docs_list',
                'section'  => 'betterdocs_search_modal_settings'
            ]
        ) );
    }

    public function search_modal_content_tabs_docs_list_font_size() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_font_size', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_modal_content_tabs_docs_list_font_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_modal_content_tabs_docs_list_font_size',
                'label'       => __( 'Font Size', 'betterdocs' ),
                'priority'    => 25,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_modal_content_tabs_docs_list_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_color', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_docs_list_color',
                [
                    'label'    => __( 'Color', 'betterdocs' ),
                    'priority' => 26,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_docs_list_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list_background_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_background_color', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_background_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_docs_list_background_color',
                [
                    'label'    => __( 'Background Color', 'betterdocs' ),
                    'priority' => 27,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_docs_list_background_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list_padding() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_padding', [
            'default'    => $this->defaults['search_modal_content_tabs_docs_list_padding'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_modal_content_tabs_docs_list_padding',
                [
                    'label'        => __( 'Padding (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_modal_content_tabs_docs_list_padding',
                    'priority'     => 28,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 16,
                        'input2' => 24,
                        'input3' => 16,
                        'input4' => 24
                    ]
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list_margin() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_margin', [
            'default'    => $this->defaults['search_modal_content_tabs_docs_list_margin'],
            'transport'  => 'postMessage',
            'capability' => 'edit_theme_options'
        ] );

        $this->customizer->add_control(
            new MultiDimensionControl(
                $this->customizer,
                'search_modal_content_tabs_docs_list_margin',
                [
                    'label'        => __( 'Margin (PX)', 'betterdocs' ),
                    'section'      => 'betterdocs_search_modal_settings',
                    'settings'     => 'search_modal_content_tabs_docs_list_margin',
                    'priority'     => 29,
                    'input_fields' => [
                        'input1' => __( 'top', 'betterdocs' ),
                        'input2' => __( 'right', 'betterdocs' ),
                        'input3' => __( 'bottom', 'betterdocs' ),
                        'input4' => __( 'left', 'betterdocs' )
                    ],
                    'defaults'     => [
                        'input1' => 0,
                        'input2' => 0,
                        'input3' => 0,
                        'input4' => 0
                    ]
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list_icon_size() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_icon_size', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_icon_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_modal_content_tabs_docs_list_icon_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_modal_content_tabs_docs_list_icon_size',
                'label'       => __( 'Icon Size', 'betterdocs' ),
                'priority'    => 30,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_modal_content_tabs_docs_list_category_font_size() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_category_font_size', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_font_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_modal_content_tabs_docs_list_category_font_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_modal_content_tabs_docs_list_category_font_size',
                'label'       => __( 'List Category Font Size', 'betterdocs' ),
                'priority'    => 31,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }

    public function search_modal_content_tabs_docs_list_category_color() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_category_color', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_color'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'rgba']
        ] );

        $this->customizer->add_control(
            new AlphaColorControl(
                $this->customizer,
                'search_modal_content_tabs_docs_list_category_color',
                [
                    'label'    => __( 'Color', 'betterdocs' ),
                    'priority' => 32,
                    'section'  => 'betterdocs_search_modal_settings',
                    'settings' => 'search_modal_content_tabs_docs_list_category_color',
                ]
            )
        );
    }

    public function search_modal_content_tabs_docs_list_category_icon_size() {
        $this->customizer->add_setting( 'search_modal_content_tabs_docs_list_category_icon_size', [
            'default'           => $this->defaults['search_modal_content_tabs_docs_list_category_icon_size'],
            'capability'        => 'edit_theme_options',
            'transport'         => 'postMessage',
            'sanitize_callback' => [$this->sanitizer, 'integer']
        ] );

        $this->customizer->add_control( new RangeValueControl(
            $this->customizer, 'search_modal_content_tabs_docs_list_category_icon_size', [
                'type'        => 'betterdocs-range-value',
                'section'     => 'betterdocs_search_modal_settings',
                'settings'    => 'search_modal_content_tabs_docs_list_category_icon_size',
                'label'       => __( 'List Category Font Size', 'betterdocs' ),
                'priority'    => 33,
                'input_attrs' => [
                    'class'  => '',
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px' // optional suffix
                ]
            ] )
        );
    }
}

