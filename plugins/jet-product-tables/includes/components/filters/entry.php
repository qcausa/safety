<?php

namespace Jet_WC_Product_Table\Components\Filters;

use Jet_WC_Product_Table\Plugin;

class Entry {

	public static $assets_enqueued = false;

	/**
	 * Filters of the instance
	 *
	 * @var array
	 */
	protected $filters = [];

	/**
	 * Sortable columns of the instance
	 *
	 * @var array
	 */
	protected $sortable_columns = [];

	/**
	 * Is table with lazy loading or not
	 *
	 * @var array
	 */
	protected $is_lazy_loaded = [];

	/**
	 * Array of filtered table settings required to render it on the backend
	 *
	 * @var object
	 */
	protected $table = [];

	protected $entry_id = null;

	/**
	 * Constructor function that initializes the class and registers an action to register columns.
	 *
	 * @param array $filters List of entry filters.
	 * @param array $table   Information about entry table.
	 */
	public function __construct( $filters = [], $table = [] ) {

		$this->filters = $filters;
		$this->table   = $this->sanitize_table_data( $table );

		$this->setup_sortable_columns();
		$this->setup_lazy_load();
	}

	/**
	 * Prepare entry to be lazy loaded
	 */
	public function setup_lazy_load() {
		if ( ! empty( $this->table['settings']['lazy_load'] ) ) {
			$this->is_lazy_loaded = true;
			$this->enqueue_assets();
		}
	}

	/**
	 * Prepare sortable columns and store into $this->sortable_columns
	 */
	public function setup_sortable_columns() {

		if ( ! empty( $this->table['columns'] ) ) {

			$has_sortable = false;

			foreach ( $this->table['columns'] as $index => $column ) {

				$column_instance = Plugin::instance()->columns_controller->get_column( $column['id'] );
				$sortable        = $column_instance ? $column_instance->get_sortable_controls( $column ) : '';

				if ( $sortable ) {
					$has_sortable = true;
					$this->sortable_columns[ $index ] = $sortable;
				}
			}

			if ( $has_sortable ) {
				$this->enqueue_assets();
			}
		}
	}

	/**
	 * Get sortable column of the given entry by column index
	 *
	 * @param  integer $col_index Index of the column to get.
	 * @return array
	 */
	public function get_sortable_column( $col_index = 0 ) {
		return $this->sortable_columns[ $col_index ] ?? '';
	}

	/**
	 * Check if entry has filters.
	 *
	 * @return boolean
	 */
	public function has_filters() {
		return ( ! empty( $this->filters ) || ! empty( $this->sortable_columns ) || $this->is_lazy_loaded ) ? true : false;
	}

	/**
	 * Generate entry ID
	 *
	 * @return string
	 */
	public function entry_id() {

		if ( null === $this->entry_id ) {

			$entry_data = $this->table;

			// Clear the settings which not affects table body
			$settings_clean_up = [
				'show_header',
				'sticky_header',
				'show_footer',
				'lazy_load',
				'pager',
				'load_more',
			];

			foreach ( $settings_clean_up as $setting ) {
				if ( isset( $entry_data['settings'][ $setting ] ) ) {
					unset( $entry_data['settings'][ $setting ] );
				}
			}

			$this->entry_id = md5( wp_json_encode( $entry_data ) );
		}

		return $this->entry_id;
	}

	/**
	 * Sanitize table data to avoid signature check errors
	 * @param  array $table Table data.
	 * @return array
	 */
	public function sanitize_table_data( $table = [] ) {

		$sanitized_data = [];

		foreach ( $table as $key => $value ) {

			if ( is_array( $value ) ) {
				$sanitized_data[ $key ] = $this->sanitize_table_data( $value );
			} elseif ( in_array( $value, [ // phpcs:ignore
				'true',
				'false',
			] ) ) {
				$sanitized_data[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} else {
				$sanitized_data[ $key ] = (string) $value;
			}
		}

		return array_filter( $sanitized_data );
	}

	/**
	 * Get entry data
	 *
	 * @return array
	 */
	public function entry_data() {
		return $this->table;
	}

	/**
	 * Genrerate siganture of current entry
	 *
	 * @return string
	 */
	public function entry_signature() {
		$secret = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

		return md5( $secret . $this->entry_id() );
	}

	/**
	 * Render filters HTML
	 */
	public function render_filters() {

		if ( empty( $this->filters ) ) {
			return;
		}

		ob_start();

		do_action( 'jet-wc-product-table/components/filters/entry/before-filters', $this );

		foreach ( $this->filters as $filter ) {

			$filter_type = $filter['id'] ?? false;

			if ( ! $filter_type ) {
				continue;
			}

			$filter_type_instance = Plugin::instance()->filters_controller->get_filter_type( $filter_type );

			if ( ! $filter_type_instance ) {
				continue;
			}

			echo '<div class="jet-wc-product-filter-block">';
			$filter_type_instance->render_instance( $filter );
			echo '</div>';

		}

		do_action( 'jet-wc-product-table/components/filters/entry/after-filters', $this );

		$filters = ob_get_clean();

		printf(
			'<div class="jet-wc-product-filters">%1$s</div>',
			$filters, // phpcs:ignore
		);

		$this->enqueue_assets();
	}

	/**
	 * Returns an array of data attributes for current entry to add it to table container
	 *
	 * @return array
	 */
	public function get_entry_data_attributes() {
		return [
			'data-entry-id'  => $this->entry_id(),
			'data-entry'     => htmlspecialchars( wp_json_encode( $this->entry_data() ) ),
			'data-signature' => $this->entry_signature(),
			'data-uid'       => $this->get_uid(),
		];
	}

	/**
	 * Return random unique ID for each entry
	 *
	 * @return string
	 */
	public function get_uid() {

		$chars        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$chars_length = strlen( $chars );
		$result       = '';

		for ( $i = 0; $i < 10; $i++ ) {
			$result .= $chars[ random_int( 0, $chars_length - 1 ) ];
		}

		return $result;
	}

	/**
	 * Remove active tag by variable name and value.
	 *
	 * @param  string $var_name Varibale name to remove.
	 * @param  string $value    Value to remove.
	 * @return string
	 */
	public function remove_active_tag_link( $var_name = '', $value = '' ) {
		return sprintf(
			'<a href="#" class="jet-wc-product-active-tag__remove" data-filter="%1$s" data-value="%2$s">&times;</a>',
			$var_name, $value
		);
	}

	/**
	 * Render HTML wrapper for active tags and active tags list itself
	 *
	 * @param  array $request_query Request arguments.
	 * @return void
	 */
	public function render_active_tags( $request_query = [] ) {

		if ( ! empty( $this->filters ) ) {

			$has_active_filters = ! empty( $request_query );

			echo '<div class="jet-wc-product-active-tags">';

			$this->render_active_tags_list( $request_query );
			if ( $has_active_filters ) {
				echo '<a href="#" class="jet-wc-product-active-tags__reset" id="reset-all-filters">' . esc_html__( 'Reset all', 'jet-wc-product-table' ) . '</a>';
			}

			echo '</div>';
		}
	}

	/**
	 * Render load more button HTML
	 *
	 * @param  array $args Button arguments.
	 * @return string
	 */
	public function render_load_more( $args = [] ) {

		$table = $this->entry_data();

		if ( empty( $table['settings']['load_more'] ) ) {
			return;
		}

		$args = wp_parse_args( $args, [
			'page'  => 1,
			'pages' => 1,
			'label' => 'Load more',
		] );

		$page  = absint( $args['page'] );
		$pages = absint( $args['pages'] );

		if ( $page >= $pages ) {
			return;
		}

		printf(
			'<button type="button" class="jet-wc-product-table-more button" data-page="%2$s">%1$s</button>',
			wp_kses_post( $args['label'] ),
			absint( $page ) + 1
		);

		$this->enqueue_assets();
	}

	/**
	 * Render paging navigation HTML
	 *
	 * @param  array $args Pager arguments - curret page, total page number and size of edges.
	 * @return string
	 */
	public function render_pager( $args = [] ) {

		$table = $this->entry_data();

		if ( empty( $table['settings']['pager'] ) ) {
			return;
		}

		$pager = new Pager( $args );
		$pager->print();

		$this->enqueue_assets();
	}

	/**
	 * Render active tags HTML
	 *
	 * @param  array $request_query Request arguments.
	 * @return string
	 */
	public function render_active_tags_list( $request_query = [] ) {

		if ( empty( $request_query ) ) {
			return;
		}

		$active_tags = '';

		foreach ( $request_query as $var => $value ) {

			$var_data  = explode( '::', $var );
			$filter    = $var_data[0];
			$query_var = $var_data[1] ?? false;

			$filter_type_instance = Plugin::instance()->filters_controller->get_filter_type( $filter );

			if ( $filter_type_instance ) {

				$verbosed_selection = $filter_type_instance->verbose_selection( $query_var, $value );

				if ( ! $verbosed_selection ) {
					continue;
				}

				$active_tags .= sprintf(
					'<div class="jet-wc-product-active-tag">%1$s%2$s</div>',
					$filter_type_instance->verbose_selection( $query_var, $value ),
					$this->remove_active_tag_link( $var, $value )
				);

			}
		}

		if ( $active_tags ) {
			$active_tags = sprintf(
				'<div class="jet-wc-product-active-tag">%1$s%2$s</div>',
				__( 'Clear all', 'jet-wc-product-table' ),
				$this->remove_active_tag_link()
			) . $active_tags;
		}

		// phpcs:ignore
		printf( '<div class="jet-wc-product-active-tags__list">%1$s</div>', $active_tags );
	}

	/**
	 * Enqueue filter-related assets
	 */
	public function enqueue_assets() {

		if ( self::$assets_enqueued ) {
			return;
		}

		self::$assets_enqueued = true;

		Plugin::instance()->assets->enqueue_script( 'jet-wc-product-filters' );

		wp_localize_script( 'jet-wc-product-filters', 'JetWCProductFiltersData', [
			'apiURL' => Plugin::instance()->filters_controller->api->get_api_url(),
		] );
	}

	/**
	 * Set current request vraiables
	 *
	 * @param array  $request Request variables.
	 * @param object $query   Query to add request into.
	 */
	public function set_request( $request, $query ) {

		foreach ( $request as $key => $value ) {

			$key_data    = explode( '::', $key );
			$filter_type = $key_data[0];
			$query_var   = $key_data[1] ?? false;

			$filter_type_instance = Plugin::instance()->filters_controller->get_filter_type( $filter_type );
			$filter_type_instance->set_request( $query_var, $value, $query );

		}
	}

	/**
	 * Check requset sginature.
	 *
	 * @param  string $signature Input signature to compare with generated one.
	 * @return bool
	 */
	public function check_signature( $signature ) {
		return $signature === $this->entry_signature();
	}
}
