<?php

namespace Jet_WC_Product_Table\Components\Shop_Integration;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Components\Admin_Bar\Admin_Bar;

class Controller {

	private $integrations_registry = [];

	/**
	 * Constructor function that initializes the class and registers an action to register columns.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'register_integrations' ] );
		add_action( 'template_redirect', [ $this, 'apply_integrations' ], 0 );
	}

	/**
	 * Registers all the column types for the product table.
	 */
	public function register_integrations() {

		$this->register_integration( new Integrations\Shop_Page() );
		$this->register_integration( new Integrations\Product_Taxonomy() );
		$this->register_integration( new Integrations\Product_Categories() );
		$this->register_integration( new Integrations\Product_Tags() );
		$this->register_integration( new Integrations\Product_Variations_Tab() );
		$this->register_integration( new Integrations\Product_Variations_Content() );
		$this->register_integration( new Integrations\Related_Products() );

		do_action( 'jet-wc-product-table/components/integrations/register', $this );
	}

	/**
	 * Registers a single column instance in the columns registry.
	 *
	 * @param object $integration_instance Instance of the column class.
	 */
	public function register_integration( $integration_instance ) {
		$this->integrations_registry[ $integration_instance->get_id() ] = $integration_instance;
	}

	/**
	 * Retrieves a column instance by ID.
	 *
	 * @param string $integration_id The ID of the column.
	 *
	 * @return object|false The column object if found, false otherwise.
	 */
	public function get_integration( $integration_id ) {
		return isset( $this->integrations_registry[ $integration_id ] ) ? $this->integrations_registry[ $integration_id ] : false;
	}

	/**
	 * Make sure given $columns_data array contains correctly formatted information about columns
	 *
	 * @param array $integrations_data Input array with information about columns.
	 *
	 * @return array  Sanitized array with information about columns
	 */
	public function sanitize_integrations_data( $integrations_data = [] ) {

		$prepared_data = [];

		foreach ( $integrations_data as $integration_id => $integration ) {

			$integration['enabled'] = $integration['enabled'] ?? false;
			$integration['enabled'] = filter_var( $integration['enabled'], FILTER_VALIDATE_BOOLEAN );
			$integration['preset']  = ! empty( $integration['preset'] ) ? absint( $integration['preset'] ) : '';

			$prepared_data[ $integration_id ] = $integration;

		}

		return $prepared_data;
	}

	/**
	 * Prepares column data for use in JavaScript, formatting it for easy consumption on the frontend.
	 *
	 * @return array Array of columns data formatted for JavaScript.
	 */
	public function get_integrations_for_js() {

		$list_for_js = [];

		foreach ( $this->integrations_registry as $integration ) {
			$list_for_js[] = [
				'id'          => $integration->get_id(),
				'label'       => $integration->get_name(),
				'description' => $integration->get_description(),
			];
		}

		return $list_for_js;
	}

	public function get_default_settings() {

		$defaults = [];

		foreach ( $this->integrations_registry as $integration ) {
			$defaults[ $integration->get_id() ] = [
				'enabled' => false,
				'preset'  => '',
			];
		}

		return $defaults;
	}

	public function apply_integrations() {

		$integrations = Plugin::instance()->settings->get( 'integrations' );

		foreach ( $integrations as $integration_id => $integration ) {

			$enabled = $integration['enabled'] ?? false;
			$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

			if ( ! $enabled || ! isset( $this->integrations_registry[ $integration_id ] ) ) {
				continue;
			}

			$integration_instance = $this->get_integration( $integration_id );
			$preset               = $integration['preset'] ?? '';

			if ( $integration_instance->is_integration_page_now() ) {

				Admin_Bar::instance()->register_integration(
					$integration_instance->get_id(),
					$integration_instance->get_name()
				);

				$integration_instance->add_preset( $preset )->apply();
			}
		}
	}
}
