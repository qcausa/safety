<?php
namespace Jet_WC_Product_Table;

use Jet_WC_Product_Table\Components\Admin_Bar\Admin_Bar;

/**
 * Manages settings presets.
 */
class Presets {

	protected $action       = 'jet_wc_product_table_presets';
	protected $table        = 'jet_wc_product_table_presets';
	protected $table_exists = null;

	public function __construct() {
		add_action( 'jet-wc-product-table/settings/assets/after', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_jet_wc_product_table_presets', [ $this, 'handle_presets_actions' ] );
	}

	public function enqueue_assets() {

		wp_localize_script( 'jet-wc-product-table-settings', 'JetWCProductsTablePresets', [
			'hook'  => $this->action,
			'nonce' => wp_create_nonce( $this->action ),
		] );
	}

	public function get_presets_for_js() {

		$presets = $this->get_presets();
		$result  = [];

		if ( ! empty( $presets ) ) {
			foreach ( $presets as $preset ) {
				$result[] = [
					'value' => $preset['ID'],
					'label' => $preset['name'],
				];
			}
		}

		return $result;
	}

	/**
	 * Handle presets action.
	 *
	 * @return void
	 */
	public function handle_presets_actions() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), $this->action ) ) {
			wp_send_json_error( __( 'Page is expired. Please reload it and try again', 'jet-wc-product-table' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You`re not allowed to manage presests', 'jet-wc-product-table' ) );
		}

		$this->ensure_presets_db_table();

		$preset_action = isset( $_REQUEST['preset_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['preset_action'] ) ) : false;

		switch ( $preset_action ) {
			case 'get_presets':
				wp_send_json_success( $this->get_presets() );
				break;

			case 'add_preset':
				$name = isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : false;
				$settings = isset( $_REQUEST['settings'] ) ? $_REQUEST['settings'] : []; // phpcs:ignore

				if ( ! $name || ! $settings ) {
					wp_send_json_error( 'Incomplete request - name and settings are required parameters. Please make sure they are provided.' );
				}

				$this->add_preset( $name, $settings );
				wp_send_json_success( $this->get_presets() );

				break;

			case 'delete_preset':
				$id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : false;

				if ( ! $id ) {
					wp_send_json_error( 'Incomplete request - ID is required parameter.' );
				}

				$this->delete_preset( $id );
				wp_send_json_success( $this->get_presets() );

				break;

			case 'update_preset':
				// phpcs:ignore
				$preset = isset( $_REQUEST['preset'] ) ? $_REQUEST['preset'] : false;

				if ( ! $preset || ! is_array( $preset ) || ! isset( $preset['ID'] ) ) {
					wp_send_json_error( 'Incomplete request. Please check preset data in your request.' );
				}

				$this->update_preset( absint( $preset['ID'] ), $preset );
				wp_send_json_success( $this->get_presets() );

				break;

		}
	}

	public function delete_preset( $preset_id = 0 ) {
		$this->wpdb()->delete( $this->table(), [
			'ID' => $preset_id,
		] );
	}

	public function update_preset( $id = 0, $preset = '' ) {

		$name = isset( $preset['name'] ) ? sanitize_text_field( $preset['name'] ) : false;
		$data = isset( $preset['data'] ) ? $preset['data'] : [];

		if ( ! $name || ! $data ) {
			return false;
		}

		return $this->wpdb()->update(
			$this->table(),
			[
				'name'  => $name,
				'attrs' => null,
				'data'  => wp_json_encode( Plugin::instance()->settings->sanitize_settings( $data ) ),
			],
			[
				'ID' => $id,
			]
		);
	}

	public function add_preset( $name = '', $settings = [] ) {

		return $this->wpdb()->insert( $this->table(), [
			'name'  => sanitize_text_field( $name ),
			'attrs' => null,
			'data'  => wp_json_encode( Plugin::instance()->settings->sanitize_settings( $settings ) ),
		] );
	}

	/**
	 * Get al presets
	 *
	 * @return array
	 */
	public function get_presets() {

		$table  = $this->table();
		$items  = $this->wpdb()->get_results( "SELECT * FROM $table" );
		$result = [];

		foreach ( $items as $item ) {
			$result[] = [
				'ID' => $item->ID,
				'name' => $item->name,
				'attrs' => $item->attrs ? json_decode( $item->attrs ) : [],
				'data' => $item->data ? json_decode( $item->data ) : [],
			];
		}

		return $result;
	}

	public function get_preset_data_for_display( $preset_id = 0 ) {

		$table  = $this->table();
		$preset_id = absint( $preset_id );
		$preset = $this->wpdb()->get_row( "SELECT * FROM $table WHERE ID = $preset_id;", ARRAY_A );

		if ( ! $preset ) {
			return [];
		} else {

			Admin_Bar::instance()->register_preset( $preset_id, $preset['name'] );

			return json_decode( $preset['data'], true );
		}
	}

	/**
	 * Returns WPDB instance
	 * @return [type] [description]
	 */
	public function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Returns table name
	 * @return [type] [description]
	 */
	public function table() {
		return $this->wpdb()->prefix . $this->table;
	}

	/**
	 * Check DB table alredy exists
	 *
	 * @return boolean [description]
	 */
	public function is_table_exists() {

		if ( null !== $this->table_exists ) {
			return $this->table_exists;
		}

		$table = $this->table();

		if ( strtolower( $table ) === strtolower( self::wpdb()->get_var( "SHOW TABLES LIKE '$table'" ) ) ) {
			$this->table_exists = true;
		} else {
			$this->table_exists = false;
		}

		return $this->table_exists;
	}

	public function ensure_presets_db_table() {

		if ( $this->is_table_exists() ) {
			return;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$table_name = $this->table();
		$wpdb_collate = $this->wpdb()->collate;

		dbDelta(
			"CREATE TABLE {$table_name} (
				ID int unsigned NOT NULL auto_increment,
				name text NULL,
				attrs text NULL,
				data longtext NULL,
				PRIMARY KEY(ID)
			)
			COLLATE {$wpdb_collate}"
		);
	}
}
