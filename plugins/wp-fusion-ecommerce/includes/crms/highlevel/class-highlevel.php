<?php

/**
 * Highlevel CRM integration.
 *
 * @since 1.21.0
 * 
 * @link https://wpfusion.com/documentation/ecommerce-tracking/highlevel-ecommerce/
 */
class WPF_EC_HighLevel {

	/**
	 * Lets other integrations know which features are supported by the CRM.
	 */
	public $supports = array( 'deal_stages' );

	/**
	 * Get things started.
	 *
	 * @since 1.21.0
	 */
	public function init() {

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		add_action( 'wpf_sync', array( $this, 'sync' ) );

		// Sync data on first run.
		$pipelines = wpf_get_option( 'highlevel_pipelines' );

		if ( ! is_array( $pipelines ) || empty( $pipelines ) ) {
			$this->sync();
		}

	}


	/**
	 * Add fields to settings page.
	 *
	 * @since 1.21.0
	 *
	 * @return array Settings
	 */
	public function register_settings( $settings, $options ) {

		$settings['ec_ghl_header'] = array(
			'title'   => __( 'HighLevel Ecommerce Settings', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['highlevel_pipeline_stage'] = array(
			'title'       => __( 'Deal Stage', 'wp-fusion' ),
			'type'        => 'select',
			'section'     => 'ecommerce',
			'placeholder' => __( 'Select a Stage', 'wp-fusion' ),
			'choices'     => isset( $options['highlevel_pipelines'] ) ? $options['highlevel_pipelines'] : array(),
			'desc'        => __( 'Select a default pipeline and stage for new deals.', 'wp-fusion' ),
		);

		return $settings;

	}


	/**
	 * Syncs pipelines on plugin install or when Resynchronize is clicked.
	 *
	 * @since 1.21.0
	 */
	public function sync() {

		$request  = wp_fusion()->crm->url . 'opportunities/pipelines/?locationId=' . wp_fusion()->crm->location_id;
		$response = wp_safe_remote_get( $request, wp_fusion()->crm->get_params() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response  = json_decode( wp_remote_retrieve_body( $response ) );
		$pipelines = array();
		$statuses  = array( 'open' => 'Open', 'won' => 'Won', 'lost' => 'Lost', 'abandoned' => 'Abandoned' );

		if ( ! empty( $response->pipelines ) ) {
			foreach ( $response->pipelines as $pipeline ) {

				foreach ( $pipeline->stages as $stage ) {

					foreach ( $statuses as $status_id => $status ) {
						$pipelines[ $pipeline->id . '+' . $stage->id . '+' . $status_id ] = $pipeline->name . ' &raquo; ' . $stage->name . ' &raquo; ' . $status;
					}
				}
			}
		}

		wp_fusion()->settings->set( 'highlevel_pipelines', $pipelines );

	}

	/**
	 * Add an order.
	 *
	 * @since 1.21.0
	 *
	 * @param int    $order_id   The order ID in WordPress.
	 * @param string $contact_id The contact ID in the CRM.
	 * @param array  $order_args The order details.
	 * @return int The deal ID.
	 */
	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( wpf_get_option( 'highlevel_pipeline_stage' ) ) ) {
			wpf_log( 'notice', 0, 'To sync orders with highlevel you must first select a deal stage from the Enhanced Ecommerce tab in the WP Fusion settings.' );
			return false;
		}

		if ( ! empty( $order_args['deal_stage'] ) ) {
			$pipeline_stage = $order_args['deal_stage'];
		} else {
			$pipeline_stage = wpf_get_option( 'highlevel_pipeline_stage' );
		}

		if ( false === strpos( $pipeline_stage, '+' ) ) {
			// pre 1.23.1 storage, only used the first pipeline.
			$pipeline_id       = wpf_get_option( 'highlevel_pipeline_id' );
			$pipeline_stage_id = wpf_get_option( 'highlevel_pipeline_stage' );
			$status            = 'open';
		} else {

			$pipeline_stage    = explode( '+', $pipeline_stage );
			$pipeline_id       = $pipeline_stage[0];
			$pipeline_stage_id = $pipeline_stage[1];

			if ( isset( $pipeline_stage[2] ) ) {
				$status = $pipeline_stage[2];
			} else {
				$status = 'open';
			}

		}

		$data = array(
			'pipelineId'      => $pipeline_id,
			'locationId'      => wp_fusion()->crm->location_id,
			'name'            => $order_args['order_label'],
			'pipelineStageId' => $pipeline_stage_id,
			'status'          => $status,
			'contactId'       => $contact_id,
			'monetaryValue'   => floatval( $order_args['total'] ),
			//'createdAt'       => gmdate( 'c', $order_args['order_date'] ), not supported, see https://ideas.gohighlevel.com/opportunities/p/need-ability-to-edit-opportunity-created-date.
		);

		/**
		 * Filters the deal data.
		 *
		 * @since 1.21.0
		 *
		 * @param array $data     The deal data.
		 * @param int   $order_id ID of the order.
		 */

		$data = apply_filters( 'wpf_ecommerce_highlevel_add_deal', $data, $order_id );

		wpf_log(
			'info',
			$order_args['user_id'],
			'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:',
			array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = wp_json_encode( $data );
		$response       = wp_safe_remote_post( wp_fusion()->crm->url . 'opportunities/', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->opportunity->id;

	}

	/**
	 * Update a deal stage when an order status is changed.
	 *
	 * @since 1.21.0
	 *
	 * @param int    $deal_id  The deal ID.
	 * @param string $stage    The deal stage.
	 * @param int    $order_id The order ID.
	 * @return bool|WP_Error True on success, error on fail.
	 */
	public function change_stage( $deal_id, $stage, $order_id ) {

		if ( false === strpos( $stage, '+' ) ) {

			// pre 1.23.1 storage, only used the first pipeline.
			$pipeline_id = wpf_get_option( 'highlevel_pipeline_id' );

		} else {

			$pipeline_stage = explode( '+', $stage );
			$pipeline_id    = $pipeline_stage[0];
			$stage          = $pipeline_stage[1];

		}

		$data = array(
			'pipelineStageId' => $stage,
			'pipelineId'      => $pipeline_id,
		);

		if ( isset( $pipeline_stage[2] ) ) {
			$data['status'] = $pipeline_stage[2];
		}

		$params = wp_fusion()->crm->get_params();

		$params['body']   = wp_json_encode( $data );
		$params['method'] = 'PUT';

		$response = wp_safe_remote_request( wp_fusion()->crm->url . 'opportunities/' . $deal_id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
