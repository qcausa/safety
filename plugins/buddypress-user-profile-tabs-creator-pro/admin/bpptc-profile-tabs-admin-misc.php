<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Allow modules to reliably load scripts/styles on the Add/Edit tabs page.
 */
function bpptc_admin_scripts( $hook_suffix ) {

	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	if ( ! bpptc_profile_tabs_pro()->is_admin_add_edit() ) {
		return;
	}

	do_action( 'bpptc_post_type_admin_enqueue_scripts' );
}
add_action( 'admin_enqueue_scripts', 'bpptc_admin_scripts' );


/**
 * Add View Tabs button on plugins screen.
 *
 * @param array $actions links to be shown in the plugin list context.
 *
 * @return array
 */
function bpptc_plugin_action_links( $actions ) {

	$actions['view-profile-tabs'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', admin_url( 'edit.php?post_type=' . bpptc_get_post_type() ), __( 'View Tabs', 'buddypress-user-profile-tabs-creator-pro' ), __( 'View Tabs', 'buddypress-user-profile-tabs-creator-pro' ) );
	$actions['view-profile-tabs-docs'] = sprintf( '<a href="%1$s" title="%2$s" target="_blank">%2$s</a>', 'https://buddydev.com/docs/guides/plugins/buddypress-plugins/buddypress-user-profile-tabs-creator-pro/getting-started-buddypress-user-profile-tabs-creator-pro/', __( 'Documentation', 'buddypress-user-profile-tabs-creator-pro' ) );

	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( bpptc_profile_tabs_pro()->get_file() ), 'bpptc_plugin_action_links' );
