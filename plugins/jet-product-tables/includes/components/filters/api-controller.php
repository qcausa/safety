<?php

namespace Jet_WC_Product_Table\Components\Filters;

use Jet_WC_Product_Table\Table;
use Jet_WC_Product_Table\Plugin;

class API_Controller {

	protected $namespace = null;
	protected $resource_name = 'filter';
	protected $table = null;

	public function __construct() {

		switch ( $this->api_request_type() ) {
			case 'wp-json':
				$this->namespace = 'jet-wc-product-table/v1';
				add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
				break;

			default:
				$this->namespace = 'jet-wc-product-table-v1';
				add_action( 'parse_request', [ $this, 'register_self_route' ] );
				break;
		}
	}

	public function api_request_type() {
		return apply_filters( 'jet-wc-product-table/components/filters/api-request-type', 'self' );
	}

	public function get_api_url() {

		switch ( $this->api_request_type() ) {
			case 'wp-json':
				return get_rest_url( null, sprintf( '/%1$s/%2$s', $this->namespace, $this->resource_name ) );

			default:
				return add_query_arg( [
					$this->namespace => $this->resource_name,
					'nocache' => time(),
				] );
		}
	}

	/**
	 * Register 'self' route processor
	 *
	 * @return [type] [description]
	 */
	public function register_self_route() {

		// phpcs:ignore
		if ( empty( $_REQUEST[ $this->namespace ] ) || $this->resource_name !== $_REQUEST[ $this->namespace ] ) {
			return;
		}

		$request = [];

		foreach ( $this->get_args() as $key => $arg ) {
			if ( isset( $_REQUEST[ $key ] ) ) { // phpcs:ignore
				$request[ $key ] = $_REQUEST[ $key ]; // phpcs:ignore
			} else {
				$request[ $key ] = $arg['default'] ?? '';
			}
		}

		add_filter( 'pre_handle_404', function () {
			status_header( 200 );
			return true;
		} );

		add_action( 'wp', function () use ( $request ) {

			$allowed = $this->permissions_check( $request );

			if ( is_wp_error( $allowed ) ) {
				wp_send_json_error( $allowed->get_error_message(), 403 );
			}

			$result = $this->process_request( $request );

			if ( isset( $result->data ) ) {
				wp_send_json( $result->data );
			} else {
				wp_send_json_error( 'Can`t process the request', 403 );
			}
		} );
	}

	/**
	 * Regsiter route for the default Rest API
	 *
	 * @return void
	 */
	public function register_rest_route() {

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'process_request' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => $this->get_args(),
				],
			]
		);
	}


	/**
	 * Check rest API permissions
	 *
	 * @param  array $request Curret request.
	 * @return bool|WP_Error
	 */
	public function permissions_check( $request ) {

		$table     = $this->get_table_from_request( $request );
		$signature = $request['signature'];

		if ( ! $table->get_filters()->check_signature( $signature ) ) {
			return new \WP_Error(
				'rest_forbidden_request',
				__( 'Request is hijacked and couldn`t be completed.', 'jet-wc-product-table' ),
				array( 'status' => 403 )
			);
		}

		Plugin::instance()->filters_controller->set_is_filters_request( true );

		return true;
	}

	public function get_table_from_request( $request ) {

		if ( null === $this->table ) {

			$table       = $request['table'];
			$this->table = new Table( $table );

		}

		return $this->table;
	}

	public function process_request( $request ) {

		$table = $this->get_table_from_request( $request );
		$table->get_filters()->set_request(
			$request['query'],
			$table->get_query()
		);

		if ( ! empty( $request['sort'] ) ) {
			$table->set_sorting( $request['sort'] );
		}

		$page = ! empty( $request['page'] ) ? absint( $request['page'] ) : false;

		if ( $page ) {
			$table->get_query()->set_query_prop( 'page', $page );
			$table->get_query()->set_query_prop( 'paged', $page );
		}

		ob_start();
		$table->render_table_rows();
		$body = ob_get_clean();

		ob_start();
		$table->get_filters()->render_active_tags_list( $request['query'] );
		$active_tags = ob_get_clean();

		ob_start();
		$table->get_filters()->render_pager( [
			'page'  => $table->get_query()->get_page_num(),
			'pages' => $table->get_query()->get_pages_count(),
		] );
		$pager = ob_get_clean();

		ob_start();
		$table->get_filters()->render_load_more( [
			'label' => $table->get_settings( 'load_more_label' ),
			'page'  => $table->get_query()->get_page_num(),
			'pages' => $table->get_query()->get_pages_count(),
		] );
		$more = ob_get_clean();

		$is_more = ! empty( $request['is_more'] ) ? filter_var( $request['is_more'], FILTER_VALIDATE_BOOLEAN ) : false;

		return rest_ensure_response( apply_filters( 'jet-wc-product-table/components/filters/api/reponse', [
			'body'        => $body,
			'active_tags' => $active_tags,
			'pager'       => $pager,
			'more'        => $more,
			'is_more'     => $is_more,
		] ) );
	}

	/**
	 * Returns allowed arguments of API request.
	 *
	 * @return [type] [description]
	 */
	public function get_args() {
		return [
			'query'     => [ 'default' => [] ],
			'sort'      => [ 'default' => false ],
			'page'      => [ 'default' => false ],
			'is_more'   => [ 'default' => false ],
			'table'     => [ 'default' => [] ],
			'signature' => [
				'type'    => 'string',
				'default' => '',
			],
		];
	}
}
