<?php

namespace Jet_WC_Product_Table\Components\Style_Manager;

class Storage {

	protected $option_name = 'jet_wc_product_table_design_settings';
	protected $options     = null;

	/**
	 * Save given styles array.
	 *
	 * @param  array $styles Styles array to save.
	 * @return void
	 */
	public function set_styles( $styles = [] ) {

		foreach ( $styles as $key => $value ) {
			if ( is_array( $value ) ) {
				$styles[ $key ] = $this->sanitize_array( $value );
			} else {
				$styles[ $key ] = sanitize_text_field( $value );
			}
		}

		update_option( $this->option_name, $styles, false );

		$this->options = $styles;
	}

	/**
	 * Get saved styling options
	 *
	 * @return array.
	 */
	public function get_styles() {

		if ( null === $this->options ) {
			$this->options = get_option( $this->option_name, [] );
		}

		return $this->options;
	}

	/**
	 * Recursively sanitize array
	 *
	 * @param  array $data_array Array to sanitize.
	 * @return array
	 */
	private function sanitize_array( $data_array = [] ) {
		foreach ( $data_array as $key => $value ) {
			if ( is_array( $value ) ) {
				$data_array[ $key ] = $this->sanitize_array( $value );
			} else {
				$data_array[ $key ] = sanitize_text_field( $value );
			}
		}

		return $data_array;
	}

	private function get_default_options() {
		return [
			'heading_background_color' => '',
			'heading_color' => '',
			'heading_typography' => [
				'font-size'       => '',
				'line-height'     => '',
				'font-weight'     => '',
				'font-style'      => '',
				'text-decoration' => '',
				'text-transform'  => '',
				'text-align'      => '',
			],
			'heading_border' => [],
			'heading_padding' => [],
			'content_background_color' => '',
			'content_color' => '',
			'content_links_color' => '',
			'content_alternate_background' => '',
			'content_typography' => [
				'font-size'       => '',
				'line-height'     => '',
				'font-weight'     => '',
				'font-style'      => '',
				'text-decoration' => '',
				'text-transform'  => '',
				'text-align'      => '',
			],
			'content_border' => [],
			'content_padding' => [],
			'filters_layout_gap' => '',
			'active_filters_layout_gap' => '',
		];
	}
}
