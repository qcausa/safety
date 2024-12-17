<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Allow modules to reliably load scripts/styles on the member type add/edit screen
 */
function bpmtp_admin_scripts( $hook_suffix ) {

	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	if ( ! bpmtp_member_types_pro()->is_admin_add_edit() ) {
		return;
	}

	do_action( 'bpmtp_post_type_admin_enqueue_scripts' );
}
add_action( 'admin_enqueue_scripts', 'bpmtp_admin_scripts' );

/**
 * Add notice if BuddyPress Member Type Generator is active or the Xprofile Member type field is active
 */
function bpmtp_show_other_plugin_errors() {

	$is_member_type_generator_active = function_exists( 'bp_member_type_generator' );
	$is_member_type_field_active     = function_exists( 'bp_xprofile_member_type_field_helper' );

	$plugin_membertype_generator = 'bp-member-type-generator/bp-member-type-generator.php';
	$plugin_membertype_field     = 'bp-xprofile-member-type-field/bp-xprofile-member-type-field-loader.php';

	?>
	<?php if ( $is_member_type_generator_active || $is_member_type_field_active ) : ?>
        <div class="notice notice-success ">
			<?php if ( $is_member_type_generator_active ): ?>
                <p><?php printf( __( 'BuddyPress Member Types Pro: Please deactivate <a href="%s" title="Deactivate"><em>BuddyPress Member Type Generator</em></a>.', 'buddypress-member-types-pro' ), wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $plugin_membertype_generator . '&amp;plugin_status=active', 'deactivate-plugin_' . $plugin_membertype_generator ) ); ?></p>
			<?php endif; ?>
			<?php if ( $is_member_type_field_active ): ?>
                <p><?php printf( __( 'BuddyPress Member Types Pro: Please deactivate <a href="%s" title="Deactivate"> <em>BuddyPress Xprofile Member Type Field</em></a> plugin.', 'buddypress-member-types-pro' ), wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $plugin_membertype_field . '&amp;plugin_status=active', 'deactivate-plugin_' . $plugin_membertype_field ) ); ?></p>
			<?php endif; ?>
        </div>
	<?php endif; ?>
	<?php
}

add_action( 'admin_notices', 'bpmtp_show_other_plugin_errors' );


/**
 * Add View member types on plugin screen
 *
 * @param array $actions links to be shown in the plugin list context.
 *
 * @return array
 */
function bpmtp_plugin_action_links( $actions ) {
    if ( post_type_exists( bpmtp_get_post_type() ) ) {
	    $actions['view-member-type'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', admin_url( 'edit.php?post_type=' . bpmtp_get_post_type() ), __( 'View Member Types', 'buddypress-member-types-pro' ), __( 'View Member Types', 'buddypress-member-types-pro' ) );
    }

	$actions['view-mtype-docs'] = sprintf( '<a href="%1$s" title="%2$s" target="_blank">%2$s</a>', 'https://buddydev.com/docs/buddypress-member-types-pro/getting-started-buddypress-member-types-pro/', __( 'Documentation', 'buddypress-member-types-pro') );

	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( bpmtp_member_types_pro()->get_file() ), 'bpmtp_plugin_action_links' );
