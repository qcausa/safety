<?php

namespace Jet_WC_Product_Table;

use Jet_WC_Product_Table\Components\Query\Controller as QueryController;
use Jet_WC_Product_Table\Components\Filters\Entry;
use Jet_WC_Product_Table\Components\Admin_Bar\Admin_Bar;
use Jet_WC_Product_Table\Components\Style_Manager\Parser;

/**
 * Handles the logic for setting up and rendering a product table with filters and columns.
 */
class Table {

	use Traits\Attributes_To_String;

	protected $query;
	protected $columns;
	protected $filters;
	protected $settings;
	protected static $assets_enqueued = false;
	protected static $inline_assets_enqueued = [];

	/**
	 * Constructor to set up the table components.
	 *
	 * @param array $args Initialization arguments.
	 */
	public function __construct( $args = [] ) {

		$query    = $args['query'] ?? [];
		$settings = $args['settings'] ?? [];

		$columns         = isset( $args['columns'] ) ? $args['columns'] : null;
		$filters         = isset( $args['filters'] ) ? $args['filters'] : null;
		$filters_enabled = isset( $args['filters_enabled'] ) ? $args['filters_enabled'] : null;

		if ( is_null( $columns ) ) {
			$columns = Plugin::instance()->settings->get( 'columns' );
		}

		if ( is_null( $filters_enabled ) ) {
			$filters_enabled = Plugin::instance()->settings->get( 'filters_enabled' );
		}

		if ( $filters_enabled && is_null( $filters ) ) {
			$filters = Plugin::instance()->settings->get( 'filters' );
		}

		if ( ! $filters_enabled ) {
			$filters = [];
		}

		$this->setup_settings( $settings );
		$this->setup_query( $query );
		$this->setup_columns( $columns );
		$this->setup_filters( $filters );

		Admin_Bar::instance()->register_table();
	}

	/**
	 * Enqueue column-specific assets on eacj table render
	 *
	 * @return void
	 */
	public function enqueue_columns_assets() {

		foreach ( $this->get_columns() as $column ) {

			$column_instance = Plugin::instance()->columns_controller->get_column( $column['id'] );

			if ( ! $column_instance ) {
				continue;
			}

			$column_instance->enqueue_column_assets( $column );
		}
	}

	/**
	 * Enqueue table assets
	 *
	 * @return [type] [description]
	 */
	public function enqueue_assets() {

		$this->enqueue_columns_assets();

		if ( self::$assets_enqueued ) {
			return;
		}

		self::$assets_enqueued = true;

		wp_enqueue_style(
			'jet-wc-product-table-styles',
			JET_WC_PT_URL . 'assets/css/public/table.css',
			[],
			JET_WC_PT_VERSION
		);

		$css_parser = Plugin::instance()->styles_manager->get_parser();

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container th.jet-wc-product-table-col--th',
			'rules'    => [
				'color: %%heading_color%%',
				'background-color: %%heading_background_color%%',
				'font-size: %%heading_typography.font-size%%',
				'line-height: %%heading_typography.line-height%%',
				'font-weight: %%heading_typography.font-weight%%',
				'font-style: %%heading_typography.font-style%%',
				'text-decoration: %%heading_typography.text-decoration%%',
				'text-transform: %%heading_typography.text-transform%%',
				'text-align: %%heading_typography.text-align%%',
				'%%heading_border%%',
				'padding: %%heading_padding%%',
			],
		] );

		/**
		 * Some kind of hack to reset left border for all cells except first, if left border was defined.
		 * This is required to avoid side borders duplication.
		 */
		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container th.jet-wc-product-table-col--th + th.jet-wc-product-table-col--th',
			'rules'    => [
				'border-left: calc( %%heading_border.left.width%% - %%heading_border.left.width%% )',
				'border-left: calc( %%heading_border.width%% - %%heading_border.width%% )',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container td.jet-wc-product-table-col--td',
			'rules'    => [
				'color: %%content_color%%',
				'background-color: %%content_background_color%%',
				'font-size: %%content_typography.font-size%%',
				'line-height: %%content_typography.line-height%%',
				'font-weight: %%content_typography.font-weight%%',
				'font-style: %%content_typography.font-style%%',
				'text-decoration: %%content_typography.text-decoration%%',
				'text-transform: %%content_typography.text-transform%%',
				'text-align: %%content_typography.text-align%%',
				'%%content_border%%',
				'padding: %%content_padding%%',
			],
		] );

		/**
		 * Same hack as for TH.
		 */
		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container td.jet-wc-product-table-col--td + td.jet-wc-product-table-col--td',
			'rules'    => [
				'border-left: calc( %%content_border.left.width%% - %%content_border.left.width%% )',
				'border-left: calc( %%content_border.width%% - %%content_border.width%% )',
			],
		] );

		/**
		 * Same hack as for TH but for the top border.
		 */
		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container tr + tr td.jet-wc-product-table-col--td',
			'rules'    => [
				'border-top: calc( %%content_border.top.width%% - %%content_border.top.width%% )',
				'border-top: calc( %%content_border.width%% - %%content_border.width%% )',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container td.jet-wc-product-table-col--td',
			'rules'    => [
				'color: %%content_color%%',
				'background-color: %%content_background_color%%',
				'font-size: %%content_typography.font-size%%',
				'line-height: %%content_typography.line-height%%',
				'font-weight: %%content_typography.font-weight%%',
				'font-style: %%content_typography.font-style%%',
				'text-decoration: %%content_typography.text-decoration%%',
				'text-transform: %%content_typography.text-transform%%',
				'text-align: %%content_typography.text-align%%',
				'%%content_border%%',
				'padding: %%content_padding%%',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container td.jet-wc-product-table-col--td a',
			'rules'    => [
				'color: %%content_color_links%%',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container .jet-wc-product-table-row:nth-child(even) td.jet-wc-product-table-col--td',
			'rules'    => [
				'background-color: %%content_alternate_background_color%%',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container .jet-wc-product-filters',
			'rules'    => [
				'gap: %%filters_layout_gap%%px',
			],
		] );

		$css_parser->add_selector( [
			'selector' => '.jet-wc-product-table-container .jet-wc-product-active-tags__list',
			'rules'    => [
				'gap: %%active_filters_layout_gap%%px',
			],
		] );

		wp_add_inline_style( 'jet-wc-product-table-styles', $css_parser->get_parsed_css( false ) );
	}

	/**
	 * Enqueue inline assets
	 *
	 * @return void
	 */
	public function inline_assets() {

		if (
			$this->get_settings( 'sticky_header' )
			&& $this->get_settings( 'show_header' )
			&& ! in_array( 'sticky-header', self::$inline_assets_enqueued, true )
		) {
			Plugin::instance()->assets->enqueue_inline_script( 'sticky-header' );
			self::$inline_assets_enqueued[] = 'sticky-header';
		}

		if ( ! in_array( 'table', self::$inline_assets_enqueued, true ) ) {
			Plugin::instance()->assets->enqueue_inline_script( 'table' );
			self::$inline_assets_enqueued[] = 'table';
		}
	}

	/**
	 * Setup table settings.
	 *
	 * @param array $settings Settings for the table.
	 */
	public function setup_settings( $settings = [] ) {

		/**
		 * Temporary disabled this logic to find the better way of merging settings
		 * $defaults = Plugin::instance()->settings->get_defaults();
		 * // phpcs:ignore
		 * foreach ( $defaults['settings'] as $key => $value ) {
		 * if ( is_bool( $value ) && ! isset( $settings[ $key ] ) ) {
		 * $settings[ $key ] = false;
		 * }
		 * }
		 */

		$settings = array_merge( Plugin::instance()->settings->get( 'settings' ), $settings );

		$this->settings = $settings;
	}

	/**
	 * Setup the query component.
	 *
	 * @param array $query Arguments for the query.
	 */
	protected function setup_query( $query = [] ) {
		$this->query = new QueryController( $query );

		if ( $this->get_settings( 'pager' ) || $this->get_settings( 'load_more' ) ) {
			$this->get_query()->set_query_prop( 'paginate', true );
		}
	}

	/**
	 * Setup filters for the table.
	 *
	 * @param array $filters Filter configuration.
	 */
	protected function setup_filters( $filters = [] ) {

		$this->filters = new Entry( $filters, [
			'query'    => $this->get_query()->query_props(),
			'columns'  => $this->get_columns(),
			'settings' => $this->get_settings(),
		] );
	}

	/**
	 * Setup columns for the table.
	 *
	 * @param array $columns List of columns.
	 */
	public function setup_columns( $columns = [] ) {
		$this->columns = $columns;
	}

	/**
	 * Get settings or a specific setting.
	 *
	 * @param string|null $setting Name of the setting to retrieve.
	 *
	 * @return mixed The setting value or all settings.
	 */
	public function get_settings( $setting = null ) {

		if ( null === $setting ) {
			return $this->settings;
		}

		return $this->settings[ $setting ] ?? null;
	}

	/**
	 * Retrieves the current query object.
	 *
	 * @return QueryController The current query object.
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Retrieves the current filters object.
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Retrieves the current columns manager.
	 *
	 * @return Column_Manager The columns manager.
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Render the entire table with filters, table, and pagination.
	 */
	public function render() {

		$this->enqueue_assets();

		$attrs = $this->attrs_to_string( $this->get_filters()->get_entry_data_attributes() );
		echo '<div class="jet-wc-product-table-container woocommerce" ' . $attrs . '>'; // phpcs:ignore

		if (
			function_exists( 'woocommerce_output_all_notices' )
			&& true === apply_filters( 'jet-wc-product-table/table/show-wc-notices', true, $this )
		) {
			woocommerce_output_all_notices();
		}

		$this->render_filters();
		$this->render_pager( 'before' );
		$this->render_table();
		$this->render_pager( 'after' );
		$this->render_load_more();

		$this->inline_assets();

		echo '</div>';
	}

	/**
	 * Render filters section.
	 */
	public function render_filters() {

		if ( ! $this->get_filters()->has_filters() ) {
			return;
		}

		$this->get_filters()->render_filters();
		$this->get_filters()->render_active_tags();
	}

	/**
	 * Set sorting (order) parameters
	 *
	 * @param array $sort Sort arguments.
	 */
	public function set_sorting( $sort = [] ) {

		$by    = $sort['order_by'] ?? false;
		$order = $sort['order'] ?? 'asc';

		if ( ! $by ) {
			return;
		}

		$column_id = $by['id'] ?? false;

		if ( ! $column_id ) {
			return false;
		}

		$column = Plugin::instance()->columns_controller->get_column( $column_id );

		if ( ! $column ) {
			return;
		}

		$by['order'] = $order;

		$column->set_order_by_column( $by, $this->get_query() );
	}

	/**
	 * Render pagination controls.
	 *
	 * @param string $position Where to render.
	 */
	public function render_pager( $position = 'after' ) {

		if ( empty( $this->get_settings( 'pager' ) ) ) {
			return;
		}

		if ( $position !== $this->get_settings( 'pager_position' )
			&& 'both' !== $this->get_settings( 'pager_position' )
		) {
			return;
		}

		echo '<div class="jet-wc-product-pager-block">';

		$this->get_filters()->render_pager( [
			'page'  => $this->get_query()->get_page_num(),
			'pages' => $this->get_query()->get_pages_count(),
		] );

		echo '</div>';
	}

	/**
	 * Render load more button
	 *
	 * @return [type] [description]
	 */
	public function render_load_more() {
		if ( empty( $this->get_settings( 'load_more' ) ) ) {
			return;
		}

		echo '<div class="jet-wc-product-more-block">';

		$this->get_filters()->render_load_more( [
			'label' => $this->get_settings( 'load_more_label' ),
			'page'  => $this->get_query()->get_page_num(),
			'pages' => $this->get_query()->get_pages_count(),
		] );

		echo '</div>';
	}

	/**
	 * Render the table.
	 */
	public function render_table() {

		$mobile_layout   = $this->get_settings( 'mobile_layout' );
		$wrapper_classes = [ 'jet-wc-product-table-wrapper' ];
		$table_classes   = [ 'jet-wc-product-table' ];

		if ( $this->get_settings( 'sticky_header' ) ) {
			$table_classes[] = 'jet-wc-product-table--sticky-header';
		}

		if ( $this->is_lazy_loaded() ) {
			$table_classes[] = 'jet-wc-product-table--lazy';
		}

		$table_classes[]   = 'jet-wc-product-table--mobile-' . $mobile_layout;
		$table_classes[]   = 'jet-wc-product-table--dir-' . $this->get_settings( 'direction' );
		$wrapper_classes[] = 'jet-wc-product-table-wrapper--mobile-' . $mobile_layout;

		echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

		echo '<table class="' . esc_attr( implode( ' ', $table_classes ) ) . '">';

		if ( $this->get_settings( 'show_header' ) ) {
			$this->render_table_headings( 'thead' );
		}

		$this->render_table_body();

		if ( $this->get_settings( 'show_footer' ) ) {
			$this->render_table_headings( 'tfoot' );
		}

		echo '</table>';

		echo '</div>';
	}

	/**
	 * Check if table content should be lazy loaded
	 *
	 * @return boolean [description]
	 */
	public function is_lazy_loaded() {

		$lazy = $this->get_settings( 'lazy_load' );

		// Avoid lazy loadin if table is already loaded with AJAX or REST API.
		if ( wp_doing_ajax()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			$lazy = false;
		}

		return $lazy;
	}

	/**
	 * Renders <tbody> tag with all the data inside or just <tbody> if table should be lazy loaded
	 *
	 * @return void
	 */
	public function render_table_body() {

		echo '<tbody>';

		if ( ! $this->is_lazy_loaded() ) {

			/**
			 * Store current global product into $base_product.
			 * This step is required to ensure after table we'll have the same global $product as before.
			 */
			global $product;
			$base_product = $product;

			$this->render_table_rows();

			/**
			 * Revert global $product.
			 * And reset loop to coorectly process all data after table.
			 */
			$product = $base_product;
			wc_reset_loop();
		}

		echo '</tbody>';
	}

	/**
	 * Returns array of the classes for the single column.
	 *
	 * @param string           $base    Base element.
	 * @param Column_Manager   $column  Column instance.
	 * @param array            $args    Column arguments.
	 * @param WC_Product|false $product Product object (optional).
	 *
	 * @return string Space-separated list of classes.
	 */
	public function column_classes( $base = 'td', $column = null, $args = [], $product = false ) {

		$classes = [
			'jet-wc-product-table-col',
			'jet-wc-product-table-col--' . $base,
			'jet-wc-product-table-col--type-' . $column->get_id(),
		];

		$is_sortable = $args['is_sortable'] ?? false;
		$is_sortable = filter_var( $is_sortable, FILTER_VALIDATE_BOOLEAN );

		if ( $is_sortable ) {
			$classes[] = 'jet-wc-product-table-col--sortable';
		}

		$mobile_layout = $this->get_settings( 'mobile_layout' );

		if ( 'shorten' === $mobile_layout ) {

			$mobile_columns = $this->get_settings( 'mobile_columns' );

			// phpcs:ignore
			if ( empty( $mobile_columns ) || ( isset( $args['_uid'] ) && in_array( $args['_uid'], $mobile_columns ) ) ) {
				$classes[] = 'jet-wc-product-table-col--mobile-show';
			} else {
				$classes[] = 'jet-wc-product-table-col--mobile-hide';
			}
		}

		if ( 'collapsed' === $mobile_layout ) {

			$index = $this->get_column_index( $args );

			if ( false !== $index ) {
				if ( 0 === $index ) {
					$classes[] = 'jet-wc-product-table-col--collpased-title';
				} else {
					$classes[] = 'jet-wc-product-table-col--collpased-body';
				}
			}
		}

		$classes = apply_filters(
			'jet-wc-product-table/table/column-classes',
			$classes,
			$base,
			$column,
			$args,
			$product,
			$this
		);

		return implode( ' ', $classes );
	}

	/**
	 * Render content of the table.
	 * This method is also used to get filtered or lazy loaded table content
	 *
	 * @return void
	 */
	public function render_table_rows() {

		$products = $this->get_query()->get_products();

		switch ( $this->get_settings( 'direction' ) ) {

			case 'vertical':
				foreach ( $this->get_columns() as $column ) {

					$column_instance = Plugin::instance()->columns_controller->get_column( $column['id'] );

					if ( ! $column_instance ) {
						continue;
					}

					echo '<tr class="jet-wc-product-table-row jet-wc-product-table-row--body">';

					$this->add_column_index( $column, 0 );

					printf(
						'<th class="%1$s">%2$s</th>',
						esc_attr( $this->column_classes( 'th', $column_instance, $column ) ),
						$this->get_column_name( $column ) // phpcs:ignore
					);

					foreach ( $products as $index => $product ) {

						$content = $column_instance->get_column_content( $product, $column );

						$this->add_column_index( $column, $index + 1 );

						printf(
							'<td class="%1$s">%2$s</td>',
							esc_attr( $this->column_classes( 'td', $column_instance, $column, $product ) ),
							$content //phpcs:ignore
						);

					}

					echo '</tr>';
				}

				break;

			case 'horizontal':
				// with next release move `default` as separate case to allow completely custom render
			default:
				foreach ( $products as $product ) {
					$this->render_table_row( $product );
				}
				break;
		}
	}

	/**
	 * Render table headings.
	 *
	 * @param string $wrapper The HTML tag to use for wrapping headings.
	 */
	public function render_table_headings( $wrapper = 'thead' ) {

		if ( 'horizontal' !== $this->get_settings( 'direction' ) ) {
			return;
		}

		// phpcs:ignore
		echo "<{$wrapper}><tr class=\"jet-wc-product-table-row jet-wc-product-table-row--headings\">";
		foreach ( $this->get_columns() as $index => $column ) {

			$sortable        = $this->get_filters()->get_sortable_column( $index );
			$column_instance = Plugin::instance()->columns_controller->get_column( $column['id'] );

			if ( ! $column_instance ) {
				continue;
			}

			$this->add_column_index( $column, $index );

			printf(
				'<th class="%1$s">%2$s%3$s</th>',
				esc_attr( $this->column_classes( 'th', $column_instance, $column ) ),
				$this->get_column_name( $column ), // phpcs:ignore
				$sortable // phpcs:ignore
			);

		}

		// phpcs:ignore
		echo "</tr></{$wrapper}>";
	}

	/**
	 * Add given index to given columns attributes.
	 * Column attributes are passed by reference so no need to return value, just change it.
	 *
	 * @param array $column Column arguments.
	 * @param int   $index  Column index.
	 */
	public function add_column_index( &$column = [], $index = 0 ) {
		$column['_cindex'] = $index;
	}

	/**
	 * Get index of given column
	 *
	 * @param array $column column arguments.
	 *
	 * @return mixed
	 */
	public function get_column_index( $column ) {
		return $column['_cindex'] ?? false;
	}

	/**
	 * Returns name of the given column
	 *
	 * @param array $column Column arguments.
	 *
	 * @return string
	 */
	public function get_column_name( $column = [] ) {
		return ! empty( $column['label'] ) ? wp_kses_post( $column['label'] ) : '';
	}

	/**
	 * Render a single row of the table.
	 *
	 * @param mixed $product The product for the row.
	 */
	public function render_table_row( $product ) {

		echo '<tr class="jet-wc-product-table-row jet-wc-product-table-row--body">';

		foreach ( $this->get_columns() as $index => $column ) {

			$column_instance = Plugin::instance()->columns_controller->get_column( $column['id'] );

			if ( ! $column_instance ) {
				continue;
			}

			$content = $column_instance ? $column_instance->get_column_content( $product, $column ) : '';
			$this->add_column_index( $column, $index );

			printf(
				'<td class="%1$s" data-column-name="%3$s">%2$s</td>',
				esc_attr( $this->column_classes( 'td', $column_instance, $column, $product ) ),
				$content, //phpcs:ignore
				$this->get_column_name( $column ) //phpcs:ignore
			);
		}

		echo '</tr>';
	}
}
