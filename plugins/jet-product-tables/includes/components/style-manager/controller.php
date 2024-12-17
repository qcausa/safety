<?php

namespace Jet_WC_Product_Table\Components\Style_Manager;

class Controller {

	protected $storage;

	public function __construct() {
		add_action( 'jet-wc-product-table/settings/save', [ $this, 'save_styles' ] );
		add_filter( 'jet-wc-product-table/settings/localize-data', [ $this, 'add_style_options_to_localized_data' ] );
	}

	/**
	 * Get parser instance
	 *
	 * @param  array $styles Styles array to parse.
	 * @return \Jet_WC_Product_Table\Components\Style_Manager\Storage
	 */
	public function get_parser( $styles = [] ) {

		if ( empty( $styles ) ) {
			$styles = $this->get_storage()->get_styles();
		}

		return new Parser( $styles );
	}

	/**
	 * Get styles storage instance
	 *
	 * @return \Jet_WC_Product_Table\Components\Style_Manager\Storage
	 */
	public function get_storage() {

		if ( ! $this->storage ) {
			$this->storage = new Storage();
		}

		return $this->storage;
	}

	/**
	 * Add style options to localized data to use in editor.
	 *
	 * @param  array $data Default data.
	 * @return array
	 */
	public function add_style_options_to_localized_data( array $data = [] ) {

		if ( empty( $data['settings'] ) ) {
			$data['settings'] = [];
		}

		$data['settings']['design'] = $this->get_storage()->get_styles();

		return $data;
	}

	/**
	 * Save styles settings into storage on global settings save
	 *
	 * @param  array $settings All settings to save.
	 * @return void
	 */
	public function save_styles( $settings ) {
		if ( isset( $settings['design'] ) ) {
			$this->get_storage()->set_styles( $settings['design'] );
		}
	}
}
