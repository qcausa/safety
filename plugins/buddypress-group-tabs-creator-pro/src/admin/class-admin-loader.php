<?php
/**
 * Admin loader class
 *
 * @package BPGTC
 * @subpackage Admin
 */

namespace PressThemes\BPGTC\Admin;

// Exit if file accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Loader
 */
class Admin_Loader {

	/**
	 * Boot class
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Setup
	 */
	public function setup() {

		Admin_MetaBox_Helper::boot();
		Post_Type_List_Helper::boot();

		add_filter( 'plugin_action_links_' . plugin_basename( bpgtc_group_tabs()->get_file() ), array( $this, 'action_links' ) );

	}

	/**
	 * Action links
	 *
	 * @param  array $actions action links.
	 *
	 * @return array
	 */
	public function action_links( $actions ) {
		$actions['view-group-tabs']      = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', admin_url( 'edit.php?post_type=' . bpgtc_get_post_type() ), __( 'View Tabs', 'buddypress-group-tabs-creator-pro' ), __( 'View Tabs', 'buddypress-group-tabs-creator-pro' ) );
		$actions['view-group-tabs-docs'] = sprintf( '<a href="%1$s" title="%2$s" target="_blank">%2$s</a>', 'https://buddydev.com/docs/guides/plugins/buddypress-plugins/buddypress-group-tabs-creator-pro/getting-started-buddypress-group-tabs-creator-pro/', __( 'Documentation', 'buddypress-group-tabs-creator-pro' ) );

		return $actions;
	}


}
