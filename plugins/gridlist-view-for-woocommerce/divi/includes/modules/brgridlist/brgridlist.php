<?php

class ET_Builder_Module_brgridlist extends ET_Builder_Module {

	public $slug       = 'et_pb_brgridlist';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => '',
		'author_uri' => '',
	);
    function init() {
        $this->name       = __( 'BeRocket Grid/List Buttons', 'BeRocket_products_label_domain' );
		$this->folder_name = 'et_pb_berocket_modules';

        $this->fields_defaults = array(
            'all_page'              => array('on'),
        );
		$this->advanced_fields = array(
			'fonts'         => array(
				'body'   => array(
					'css'          => array(
						'main'      => "{$this->main_css_element} .berocket_lgv_widget",
						'important' => 'plugin_only',
					),
					'label' => esc_html__( 'Buttons', 'simp-simple' ),
                    'hide_font' => true,
                    'hide_letter_spacing' => true,
                    'hide_line_height' => true,
				),
            ),
			'link_options'  => false,
			'visibility'    => false,
			'text'          => false,
			'transform'     => false,
			'animation'     => false,
			'background'    => false,
			'borders'       => false,
			'box_shadow'    => false,
			'button'        => false,
			'filters'       => false,
			'margin_padding'=> false,
			'max_width'     => false,
		);
    }

    function get_fields() {
        $fields = array(
            'all_page' => array(
                "label"             => esc_html__( 'Show on all pages', 'BeRocket_LGV_domain' ),
                'type'              => 'yes_no_button',
                'options'           => array(
                    'off' => esc_html__( "No", 'et_builder' ),
                    'on'  => esc_html__( 'Yes', 'et_builder' ),
                ),
            ),
        );

        return $fields;
    }

    function render( $atts, $content = null, $function_name = '' ) {
        $atts = BRGL_GridListExtension::convert_on_off($atts);
        ob_start();
        the_widget( 'berocket_lgv_widget', $atts );
        return ob_get_clean();
    }
}

new ET_Builder_Module_brgridlist;
