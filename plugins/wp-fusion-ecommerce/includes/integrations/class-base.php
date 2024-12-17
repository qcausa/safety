<?php

class WPF_EC_Integrations_Base {

	/**
	 * The integration slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $slug;

	public function __construct() {

		$this->init();

		if ( isset( $this->slug ) ) {
			wp_fusion_ecommerce()->integrations->{$this->slug} = $this;
		}

	}

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {}

}
