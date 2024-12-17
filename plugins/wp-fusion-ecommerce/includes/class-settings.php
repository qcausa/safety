<?php

class WPF_EC_Settings {

	public function __construct() {

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 30, 2 );

	}

	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @since  1.15.2
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['ecommerce'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'ecommerce' => __( 'Enhanced Ecommerce', 'wp-fusion' ) ) );
		}

		return $page;

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @since  1.15.2
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['total_revenue_header'] = array(
			'title'   => __( 'Total Revenue', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['total_revenue_field'] = array(
			'title'   => __( 'Total Revenue Field', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Optionally, select a custom field in %s to use for total revenue tracking.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'crm_field',
			'section' => 'ecommerce',
			'tooltip' => __( 'Most CRMs track this data for you automatically, so it\'s not necessary to also track total revenue in a custom field. Enabling this setting will also slow down your checkout by a couple of seconds.', 'wp-fusion' ),
		);

		return $settings;

	}


}
