<?php
/**
 * BP Nouveau - Groups Directory
 *
 * @since 3.0.0
 * @version 3.0.0
 */
global $groups_atts;

$classes            = array();
$group_loop_classes = static function () use ( $classes ) {
	$bp_nouveau            = bp_nouveau();
	$classes[]             = 'item-list';
	$classes[]             = 'groups-list';
	$classes[]             = 'bp-list';
	$classes[]             = 'rg-group-list';
	$component             = sanitize_key( 'groups' );
	$bp_nouveau_appearance = get_option( 'bp_nouveau_appearance' );
	$groups_layout         = ( isset( $bp_nouveau_appearance['groups_layout'] ) && $bp_nouveau_appearance['groups_layout'] != '' ) ? $bp_nouveau_appearance['groups_layout'] : 'three';
	
	global $wbtm_reign_settings;
	$classes[] = isset( $wbtm_reign_settings['reign_buddyextender']['group_directory_type'] ) ? $wbtm_reign_settings['reign_buddyextender']['group_directory_type'] : 'wbtm-group-directory-type-2';
	
	

	if ( function_exists( 'bp_nouveau_customizer_grid_choices' ) ) {
		$customizer_option = sprintf( '%s_layout', $component );
		$layout_prefs      = bp_nouveau_get_temporary_setting(
			$customizer_option,
			bp_nouveau_get_appearance_settings( $customizer_option )
		);

		if ( $layout_prefs && (int) $layout_prefs > 1 && function_exists( 'bp_nouveau_customizer_grid_choices' ) ) {
			$grid_classes = bp_nouveau_customizer_grid_choices( 'classes' );
			if ( isset( $grid_classes[ $layout_prefs ] ) ) {
				$classes = array_merge(
					$classes, array(
						'grid',
						$grid_classes[ $layout_prefs ],
					)
				);
			}

			if ( ! isset( $bp_nouveau->{$component} ) ) {
				$bp_nouveau->{$component} = new stdClass();
			}
			// Set the global for a later use.
			$bp_nouveau->{$component}->loop_layout = $layout_prefs;
		}
	} else {
		$classes = array_merge(
			$classes,
			array(
				'grid',
				$groups_layout,
			)
		);
	}

	return $classes;
};
add_filter( 'bp_nouveau_get_loop_classes', $group_loop_classes );
$loop_layout 		= ! empty( $groups_atts['loop-layout'] ) ? $groups_atts['loop-layout'] : '';
$cover_class        = ( function_exists( 'bb_platform_group_element_enable' ) && ( ! bb_platform_group_element_enable( 'cover-images' ) ) ) ? 'bb-cover-disabled' : 'bb-cover-enabled';
$group_cover_height = function_exists( 'bb_get_group_cover_image_height' ) ? bb_get_group_cover_image_height() : 'small';
?>

<div id="buddypress" class="bp-dir-hori-nav <?php echo esc_attr( bp_nouveau_get_container_classes() ) . ' ' . esc_attr( $groups_atts['container_class'] ); ?>">
	<div class="screen-content">
		<input type="hidden" data-bp-filter="groups" value="<?php echo esc_attr( $groups_atts['bpsh_query'] ); ?>" />
		<div id="groups-dir-list" class="groups dir-list <?php echo esc_attr( $loop_layout );?>" data-bp-list="groups" data-ajax="false">
			<?php if ( bp_has_groups( $groups_atts['bpsh_query'] ) ) : ?>
				<ul id="groups-list" class="grid <?php bp_nouveau_loop_classes();?><?php echo esc_attr( ' ' . $cover_class . ' ' ); ?>">
					<?php
					while ( bp_groups() ) :
						bp_the_group();
						$bp_group_id = bp_get_group_id();
						?>
						<li <?php bp_group_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
							<div class="list-wrap">
								<?php
								$active_theme = wp_get_theme();
								if ( 'BuddyBoss Theme' == $active_theme ) {
								if ( ! bp_disable_group_cover_image_uploads() && bb_platform_group_element_enable( 'cover-images' ) ) {
									$group_cover_image_url = bp_attachments_get_attachment(
										'url',
										array(
											'object_dir' => 'groups',
											'item_id'    => $bp_group_id,
										)
									);
									$has_default_cover     = function_exists( 'bb_attachment_get_cover_image_class' ) ? bb_attachment_get_cover_image_class( $bp_group_id, 'group' ) : '';
									?>
										<div class="bs-group-cover bb-shortcode-group-only only-grid-view <?php echo esc_attr( $has_default_cover . ' cover-' . $group_cover_height ); ?>">
											<a href="<?php bp_group_permalink(); ?>">
											<?php if ( ! empty( $group_cover_image_url ) ) { ?>
													<img src="<?php echo esc_url( $group_cover_image_url ); ?>">
												<?php } ?>
											</a>
										</div>
										<?php
									}
								}
								?>

								<?php
								$active_theme = wp_get_theme();
								if ( 'BuddyX' == $active_theme || 'BuddyxPro' == $active_theme ) {
									if ( function_exists( 'buddyx_render_group_cover_image' ) ) {
										buddyx_render_group_cover_image();
									}
								} elseif ( 'REIGN' == $active_theme ) {
									if ( function_exists( 'reign_render_group_cover_image' ) ) {
										reign_render_group_cover_image();
									}
								}
								?>
								<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
									<div class="item-avatar">
										<a href="<?php bp_group_url(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
									</div>
								<?php endif; ?>
								<div class="item">
									<div class="item-block">
										<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>
										<?php if ( bp_nouveau_group_has_meta() ) : ?>
											<p class="item-meta group-details"><?php bp_nouveau_get_group_meta( array( 'keys' => array( 'status', 'count' ) ) ); ?></p>
										<?php endif; ?>
										<p class="last-activity item-meta">
											<?php

												/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
												printf(	esc_html__( 'Active %s', 'shortcodes-for-buddypress' ), sprintf('<span data-livestamp="%1$s">%2$s</span>',esc_html(bp_core_get_iso8601_date( bp_get_group_last_active( 0, array( 'relative' => false ) ) ) ),	esc_html( bp_get_group_last_active() )
													)
												);
											?>
										</p>

										<?php bp_nouveau_groups_loop_buttons(); ?>
									</div>

									<div class="group-desc"><p><?php bp_group_description_excerpt( $group = false, $length = 50 ); ?></p></div>
									<?php bp_nouveau_groups_loop_item(); ?>
								</div>
							</div>
						</li>
					<?php endwhile; ?>
				</ul>	
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
