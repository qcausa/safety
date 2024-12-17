<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Buddypress_Shortcode_Activity_Widget extends Widget_Base {

	public function get_name() {
		return 'buddypress_shortcode_activity_widget';
	}

	public function get_title() {
		return esc_html__( 'Activity List', 'shortcodes-for-buddypress' );
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	public function get_categories() {
		return array( 'buddypress-widgets' );
	}

	public function is_buddypress_plugin_activate() {

		$plugin = 'buddypress/bp-loader.php';

		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		} else {
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
		}
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Query', 'shortcodes-for-buddypress' ),
			)
		);

		$this->add_control(
			'sfb_title',
			array(
				'label'       => __( 'Title', 'shortcodes-for-buddypress' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Add activity title', 'shortcodes-for-buddypress' ),
			)
		);

		$this->add_control(
			'sfb_number',
			array(
				'label'       => __( 'display number of activities', 'shortcodes-for-buddypress' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 20,
				'description' => __( 'How many activity items to display.', 'shortcodes-for-buddypress' ),
			)
		);

		$this->add_control(
			'go_sfb_pro_notice',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => \Shortcodes_For_Buddypress_Public::sfb_go_pro_template(
					array(
						'title'    => __( 'Shortcodes for BuddyPress PRO', 'shortcodes-for-buddypress' ),
						'messages' => array(
							__( 'Power up up your listing with custom queries and templates.', 'shortcodes-for-buddypress' ),
						),
						'link'     => 'https://wbcomdesigns.com/downloads/shortcodes-for-buddypress-pro',
					)
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'activity_item_container_section',
			array(
				'label' => __( 'Activity Item Container', 'shortcodes-for-buddypress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'container_background',
				'label'    => __( 'Background', 'shortcodes-for-buddypress' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)',
			)
		);

		$this->add_control(
			'container_border_radius',
			array(
				'label'      => __( 'Border Radius', 'shortcodes-for-buddypress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => __( 'Padding', 'shortcodes-for-buddypress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'spacing_items',
			array(
				'label'     => __( 'Spacing items', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .activity > ul.activity-list > li:not(.load-more)'            => 'margin-top: 0; margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .activity > ul.activity-list > li:last-child' => 'margin-bottom: 0;',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'load_more_button_section',
			array(
				'label' => __( 'Load More Button', 'shortcodes-for-buddypress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'base_style',
			array(
				'label'     => __( 'Base Style', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::HIDDEN,
				'default'   => '1',
				'selectors' => array(
					'{{WRAPPER}} .load-more'   => 'background-color: transparent; border: none; margin: 0;',
					'{{WRAPPER}} .load-newest' => 'background-color: transparent; border: none; margin: 0;',
				),
			)
		);

		$this->add_control(
			'load_more_btn_display_type',
			array(
				'label'     => __( 'Display', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'block',
				'options'   => array(
					'inline-block' => __( 'Inline', 'shortcodes-for-buddypress' ),
					'block'        => __( 'Block', 'shortcodes-for-buddypress' ),
				),
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a' => 'display: block;',
				),
			)
		);

		$this->add_responsive_control(
			'load_more_btn_align',
			array(
				'label'     => __( 'Alignment', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'shortcodes-for-buddypress' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'shortcodes-for-buddypress' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'shortcodes-for-buddypress' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a' => 'text-align: {{VALUE}};',
				),
				'condition' => array(
					'load_more_btn_display_type' => 'inline-block',
				),
				'default'   => '',
			)
		);

		$this->add_control(
			'load_more_btn_display_inline_block',
			array(
				'label'     => __( 'Base Style', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::HIDDEN,
				'default'   => '1',
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a' => 'display: inline-block;',
				),
				'condition' => array(
					'load_more_btn_display_type' => 'inline-block',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'load_more_btn_typography',
				'label'    => __( 'Typography', 'shortcodes-for-buddypress' ),
				'selector' => '{{WRAPPER}} #buddypress .activity-list .load-more a, {{WRAPPER}} .load-newest a',
			)
		);

		$this->start_controls_tabs( 'load_more_btn_style_tabs' );

		$this->start_controls_tab(
			'load_more_btn_style_normal_tab',
			array(
				'label' => __( 'Normal', 'shortcodes-for-buddypress' ),
			)
		);

		$this->add_control(
			'load_more_btn_background',
			array(
				'label'     => __( 'Background', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a'   => 'background-color: {{VALUE}}',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'load_more_btn_text_color',
			array(
				'label'     => __( 'Color', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a'   => 'color: {{VALUE}}',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'load_more_btn_style_hover_tab',
			array(
				'label' => __( 'Hover', 'shortcodes-for-buddypress' ),
			)
		);

		$this->add_control(
			'load_more_btn_hover_background',
			array(
				'label'     => __( 'Background', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a:hover'   => 'background-color: {{VALUE}}',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'load_more_btn_hover_text_color',
			array(
				'label'     => __( 'Color', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a:hover'   => 'color: {{VALUE}}',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'load_more_btn_hover_border',
			array(
				'label'     => __( 'Border Color', 'shortcodes-for-buddypress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a:hover'   => 'border-color: {{VALUE}}',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a:hover' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'load_more_btn_hover_shadow',
				'selector' => '{{WRAPPER}} #buddypress .activity-list .load-more a:hover, #buddypress {{WRAPPER}} .load-newest a:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'load_more_btn_border',
				'selector'  => '{{WRAPPER}} #buddypress .activity-list .load-more a, {{WRAPPER}} #buddypress .activity-list .load-newest a',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'load_more_btn_border_radius',
			array(
				'label'      => __( 'Border Radius', 'shortcodes-for-buddypress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'load_more_btn_shadow',
				'selector' => '{{WRAPPER}} #buddypress .activity-list .load-more a, {{WRAPPER}} #buddypress .activity-list .load-newest a',
			)
		);

		$this->add_responsive_control(
			'load_more_btn_padding',
			array(
				'label'      => __( 'Padding', 'shortcodes-for-buddypress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} #buddypress .activity-list .load-more a'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} #buddypress .activity-list .load-newest a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

	}

	protected function render() {
		if ( function_exists( 'bp_is_active' ) && ( bp_is_active( 'activity' ) ) ) {
			
			global $activity_args;

			wp_enqueue_script( 'bp-nouveau-activity' );
			$settings = $this->get_settings_for_display();

			$args = array(
				'title'            => $settings['sfb_title'],
				'per_page'         => ( isset( $settings['sfb_number'] ) ) ? $settings['sfb_number'] : 20,
				'display_comments' => 'threaded',
				'container_class'  => 'activity',
			);

			$activity_args = $args;
			unset( $activity_args['title'] );

			$activity_ajax_querystring = static function ( $ajax_querystring, $object ) {
				global $activity_args;
				$qs = array();

				if ( ! empty( $activity_args ) ) {
					foreach ( $activity_args as $key => $value ) {
						if ( $value != '' ) {
							$qs[] = $key . '=' . $value;
						}
					}
				}

				$query_string = empty( $qs ) ? '' : join( '&', (array) $qs );
				if ( $query_string != '' ) {
					$ajax_querystring .= '&' . $query_string;
				}

				return $ajax_querystring;
			};

			add_filter( 'bp_ajax_querystring', $activity_ajax_querystring, 99, 2 );

			?>
			
			<div class="buddypress-activity-element">
				<?php if ( $args['title'] ) : ?>
					<h3 class="activity-shortcode-title"><?php echo esc_html( $args['title'] ); ?></h3>
				<?php endif; ?>
			
				<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav activity">
					<?php if ( function_exists( 'bp_nouveau_before_activity_directory_content' ) ) : ?>	
						<?php bp_nouveau_before_activity_directory_content(); ?>
					<?php endif; ?>

					<div class="screen-content">
						<?php if( function_exists( 'bp_nouveau_activity_hook' ) ) {
							bp_nouveau_activity_hook( 'before_directory', 'list' ); 
						}?>

						<div id="activity-stream" class="activity" data-bp-list="activity" data-ajax="false">
							<?php if ( class_exists( 'Youzify' ) ) { 
								try {
									shortcodes_for_buddypress_template( 'youzify/activity/bp-shortcodes-activity-youzify.php' );
								} catch (\Throwable $th) {
									// throw $th;
									// error_log($th);
								}
							} else {
								bp_get_template_part( 'activity/activity-loop' ); 
							}
							?>
						</div>

						<?php if( function_exists( 'bp_nouveau_after_activity_directory_content' ) ) {
							bp_nouveau_after_activity_directory_content(); 
						}
						?>

					</div>
				</div>
			</div>
			<?php
			remove_filter( 'bp_ajax_querystring', $activity_ajax_querystring, 99, 2 );

			?>
			<style>
			.elementor-element-<?php echo esc_html( $this->get_id() ); ?> #buddypress .load-more{ display:none}
			</style>
			<?php
		} else {
			/** This user notice will render when user uses activity shortcode with Elementor and the activity component is disabled . */
			?>
			<div class="bpsp-flex-container">
				<div><i class="fa fa-exclamation-circle bpsp-fa-icon"></i></div>
				<p><?php echo esc_html__( 'Please enable the Activity Component.', 'shortcodes-for-buddypress' ); ?></p>
			</div>
			<?php
		}
	}

}


\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Buddypress_Shortcode_Activity_Widget() );
