<?php
/**
 * Plugin admin file add new meta boxes to our post type
 *
 * @package BPGTC
 * @subpackage Admin
 */
namespace PressThemes\BPGTC\Handlers;

/**
 * If file accessed directly if will exit
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ajax_handler
 */
class Ajax_handler {

	/**
	 * Boot class
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Setup callbacks
	 */
	private function setup() {
		add_action( 'wp_ajax_bpgtc_get_groups_list', array( $this, 'group_auto_suggest_handler' ) );
	}

	/**
	 * Group response builder
	 */
	public function group_auto_suggest_handler() {

		$search_term = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
		$excluded    = isset( $_POST['included'] ) ? wp_parse_id_list( $_POST['included'] ) : '';

		$groups = groups_get_groups(
			array(
				'search_terms' => $search_term,
				'exclude'      => $excluded,
				'show_hidden'  => true,
			)
		);

		$groups = $groups['groups'];

		$list = array();
		foreach ( $groups as $group ) {
			$list[] = array(
				'label' => $group->name,
				'url'   => bpgtc_get_group_url( $group ),
				'id'    => $group->id,
			);
		}

		echo wp_json_encode( $list );
		exit( 0 );
	}
}
