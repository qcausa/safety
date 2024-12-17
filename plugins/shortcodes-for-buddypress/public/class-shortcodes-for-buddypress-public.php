<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Shortcodes_For_Buddypress
 * @subpackage Shortcodes_For_Buddypress/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Shortcodes_For_Buddypress
 * @subpackage Shortcodes_For_Buddypress/public
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Shortcodes_For_Buddypress_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_filter( 'bp_enqueue_assets_in_bp_pages_only', array( $this, 'bp_shortcode_js_css' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $post;
		
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shortcodes_For_Buddypress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shortcodes_For_Buddypress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		do_action( 'before_enqueue_buddypress_shortcode_styles' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shortcodes-for-buddypress-public.css', array(), $this->version, 'all' );

		if ( class_exists( 'Youzify' ) && ( (bp_shortcode_is_shortcode_page('members-listing') || bp_shortcode_is_shortcode_page('groups-listing')  || bp_shortcode_is_shortcode_page('activity-listing') ) ) )  { 
			wp_enqueue_script( 'masonry' );
	        wp_enqueue_style( 'youzify-directories', YOUZIFY_ASSETS . 'css/youzify-directories.min.css', array(), YOUZIFY_VERSION );
	        wp_enqueue_style( 'youzify', YOUZIFY_ASSETS . 'css/youzify.min.css', array(), YOUZIFY_VERSION );
	        wp_enqueue_script( 'youzify-directories', YOUZIFY_ASSETS .'js/youzify-directories.min.js', YOUZIFY_VERSION, true );
	    }

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $post;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shortcodes_For_Buddypress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shortcodes_For_Buddypress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( defined( 'RTMEDIA_VERSION' ) ) {
			wp_enqueue_script(
				'rtmedia-backbone',
				plugin_dir_url( __FILE__ ) . 'js/rtMedia.backbone.js',
				array(
					'plupload-all',
					'backbone',
					// Whole rtmedia-backbone file is dependent on rtmedia-main  file (eg. rtMediaHook).
					'rtmedia-main',
				),
				RTMEDIA_VERSION,
				true
			);
		}
		do_action( 'before_enqueue_buddypress_shortcode_scripts' );
		if(function_exists('bp_is_active') &&  bp_is_active('activity')){
			$_bp_theme_package = get_option( '_bp_theme_package_id' );
			
			wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shortcodes-for-buddypress-public.js', array( 'jquery', 'bp-nouveau-activity' ), $this->version, true );
			if ( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'activity-listing' ) ) && ( ! class_exists( 'Youzify' ) )  && ('nouveau' === $_bp_theme_package)) {
				wp_enqueue_script( $this->plugin_name );
				wp_localize_script(
					$this->plugin_name,
					'buddypress_shortcode',
					apply_filters( 'bp_activity_localized_params' , array(
						'ajax_url'   => admin_url( 'admin-ajax.php' ),
						'ajax_nonce' => wp_create_nonce( 'bpsp-nonce' ),
						)
					)
				);
			}
		}
		
	}

	public function bp_shortcode_js_css() {
		return false;
	}

	public function buddypress_shortcodes_body_classes( $classes ) {
		global $post;

		if ( isset( $post->post_content ) && ( has_shortcode( $post->post_content, 'activity-listing' ) || has_shortcode( $post->post_content, 'members-listing' ) || has_shortcode( $post->post_content, 'groups-listing' ) || has_shortcode( $post->post_content, 'notifications-listing' ) ) ) {
			$classes[] = 'bpsh-buddypress';
			$classes[] = 'buddypress';
		}

		return $classes;
	}

	public function buddypress_shortcodes_bp_nouveau_get_search_primary_object( $object ) {
		global $post;

		if ( isset( $post->post_content ) && ( has_shortcode( $post->post_content, 'activity-listing' ) ) ) {
			$object = 'dir';
		}

		return $object;
	}


	/**
	 * Call BuddyPress Activity Listing shortcode
	 *
	 * @since    1.0.0
	 */
	public function buddypress_shortcodes_activity_listing( $atts, $content = null ) {
		if(is_admin()){
			return '';
		}
		global $activity_atts;
		$bpsh_query = build_query( $atts );
		if ( ! empty( $bpsh_query ) ) {
			$bpsh_query = '&' . $bpsh_query;
		}
		$output       = '';
		$default_atts = array(
			'title'            => '', // title of the section
			'pagination'       => 1, // show or not.
			'load_more'        => 0,
			'display_comments' => 'threaded',
			'include'          => false, // pass an activity_id or string of IDs comma-separated
			'exclude'          => false, // pass an activity_id or string of IDs comma-separated
			'in'               => false, // comma-separated list or array of activity IDs among which to search
			'sort'             => 'DESC', // sort DESC or ASC
			'page'             => 1, // which page to load
			'per_page'         => 10, // how many per page
			'max'              => false, // max number to return
			'count_total'      => true,
			'scope'            => false,
			// Filtering
			'user_id'          => false, // user_id to filter on
			'object'           => false, // object to filter on e.g. groups, profile, status, friends
			'action'           => false, // action to filter on e.g. activity_update, new_forum_post, profile_updated
			'primary_id'       => false, // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
			'secondary_id'     => false, // secondary object ID to filter on e.g. a post_id
						// Searching
			'search_terms'     => false, // specify terms to search on
			'use_compat'       => ( function_exists( 'bp_use_theme_compat_with_current_theme' ) ) ? bp_use_theme_compat_with_current_theme() : '',
			'allow_posting'    => false, // experimental, some of the themes may not support it.
			'container_class'  => 'activity', // default container,
			'hide_on_activity' => 1, // hide on user and group activity pages
			'bpsh_query'       => $bpsh_query,
		);

		// Apply filter to modiy the activity attributes.
		$activity_atts = apply_filters( 'bp_activity_shortcode_query_args', shortcode_atts( $default_atts, $atts ) );
		extract( $activity_atts );

		if ( isset( $atts['hide_on_activity'] ) && $atts['hide_on_activity'] && ( function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() ||
				function_exists( 'bp_is_group_home' ) && bp_is_group_home() ) ) {
			return '';
		}

		$_bp_theme_package = get_option( '_bp_theme_package_id' );
		
		ob_start();
		
		if ( function_exists( 'bp_is_active' ) && (bp_is_active('activity')) ) {
			
			if ( $title != '' ) {
				echo '<h3 class="section-title"><span>'.esc_html( $title ).'</span></h3>';
			}
			do_action( 'before_render_activity_shortcode', $atts );
			if ( class_exists( 'Youzify' ) ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'youzify/activity/bp-shortcodes-activity-youzify.php' ) ) ? true : false;
			} elseif ( $_bp_theme_package == 'legacy' ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'legacy/activity/bp-shortcodes-activity.php' ) ) ? true : false;
			} else {
				wp_enqueue_script( 'bp-nouveau-activity' );

				if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
					wp_enqueue_script( 'bp-medium-editor' );
					wp_enqueue_style( 'bp-medium-editor' );
				}
				$no_template = ( false === shortcodes_for_buddypress_template( 'nouveau/activity/bp-shortcodes-activity.php' ) ) ? true : false;
			}
			do_action( 'after_render_activity_shortcode', $atts );

			/** 
			* If none of the above template exists, then display user notice.
			* @since 2.7.2 
			*/
			if ( $no_template ) {
				?>
				<div class="bpsp-feedback bpsp-info">
					<span class="bpsp-icon"></span>
					<p><?php echo esc_html__('No Template Found.', 'shortcodes-for-buddypress'); ?></p>
				</div>
				<?php
			}
		}else{	
			?>
			<div class="bpsp-feedback bpsp-info">
				<span class="bpsp-icon"></span>
				<p><?php echo esc_html__('Please enable the Activity Component.', 'shortcodes-for-buddypress'); ?></p>
			</div>
			<?php
		}
		$output = ob_get_clean();
		$output = apply_filters( 'modify_activity_listing_output', $output, $atts, $activity_atts );
		return $output;
	}

	/**
	 * Buddypress_shortcodes_activity_rest_api
	 *
	 * @param  mixed  $atts for shortcode arguments.
	 * @param  string $content for sho]rtocde content.
	 */
	public function buddypress_shortcodes_activity_rest_api( $atts, $content = null ) {
		$activity_param_count = 0;
		$activity_params      = '';
		if ( ! empty( $atts ) ) {
			foreach ( $atts as $key_atts => $value_atts ) {
				if ( 'title' === $key_atts ) {
					continue;
				}
				if ( $activity_param_count > 0 ) {
					$activity_params .= '&';
				}
				$activity_params .= $key_atts . '=' . $value_atts;
				++$activity_param_count;
			}
		}

		if ( isset( $atts['hide_on_activity'] ) && $atts['hide_on_activity'] && ( function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() ||
		function_exists( 'bp_is_group_home' ) && bp_is_group_home() ) ) {
			return '';
		}

		ob_start();
		$per_page = 2;
		if ( isset( $atts['title'] ) && $atts['title'] != '' ) {
			?>
			<h3 class="section-title"><span><?php echo esc_html( $atts['title'] ); ?></span></h3>
			<?php
		}
		$response = wp_remote_get( get_rest_url() . 'buddypress/v1/activity?display_comments=threaded&' . $activity_params );
		echo '<div id="buddypress" class="bp-activity-shortcode buddypress-wrap bp-dir-hori-nav activity">';
		if ( function_exists( 'bp_nouveau_before_activity_directory_content' ) ) {
			bp_nouveau_before_activity_directory_content();
		}
		?>
			<input type="hidden" id="activity_filters_objects" data-bp-filter="activity" value="<?php // echo esc_attr( $activity_atts['bpsh_query'] ); ?>" />
			<div class="screen-content">
					<div id="activity-stream" class="activity" data-bp-list="activity" data-ajax="false">
						<ul class="activity-list item-list bp-list">
					<?php
					if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
						$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
						wp_enqueue_script( 'bp-nouveau-activity' );
						$i = 2;
						global $activities_template;
						foreach ( $response_data as $response_activity_data ) {     // this loop is for activity
							// Ist step create data for activity.
							$obj                       = new stdClass();
							$obj->current_activity     = 20;
							$obj->activity_count       = 21;
							$obj->total_activity_count = 21;
							// create object in activities key.
							$obj_activities = new BP_Activity_Rest_Api_Template( $response_activity_data );
							// create object in activity key.
							$obj_activity                   = new BP_Activity_Rest_Api_Template( $response_activity_data );
							$obj->activities                = array(
								$obj_activities,
							);
							$obj->activity                  = $obj_activity;
							$obj->in_the_loop               = 1;
							$obj->pag_arg                   = 'acpage';
							$obj->pag_page                  = 1;
							$obj->pag_num                   = 10;
							$obj->pag_links                 = '';
							$obj->full_name                 = '';
							$obj->disable_blogforum_replies = 1;
							$favorite_activity              = bp_activity_get_meta( $response_activity_data['id'], 'favorite_count', true );
							$favs                           = array();
							if ( 0 != $favorite_activity ) {
								$favs[ $i ] = $response_activity_data['id'];
							}
							$obj->my_favs        = $favs;
							$obj->has_more_items = 1; // end data cretaion for a activity.
							$activities_template = $obj;
							?>
								<?php bp_nouveau_activity_hook( 'before', 'entry' ); ?>
										<!-- <li class="activity activity_update activity-item date-recorded-1654083744" id="activity-<?php echo esc_attr( $response_activity_data['id'] ); ?>" data-bp-activity-id="<?php echo esc_attr( $response_activity_data['id'] ); ?>"> -->
										<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" <?php bp_nouveau_activity_data_attribute_id(); ?> data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>">
										<div class="activity-card-head">
											<h6 class="card-head-content-type">
												<?php echo esc_html( $response_activity_data['component'] ); ?>
											</h6>
										</div>
										<div class="activity-item-head">
											<div class="activity-avatar item-avatar">
												<a href="<?php bp_activity_user_link(); ?>">
													<img loading="lazy" src="<?php echo esc_url( $response_activity_data['user_avatar']['full'] ); ?>" class="avatar user-1-avatar avatar-300 photo" width="300" height="300" alt="Profile picture of admin">
												</a>
											</div>
											<div class="activity-header">
												<?php bp_activity_action(); ?>
											</div>
										</div>
										<div class="activity-content">											
											<div class="activity-inner">
												<?php echo wp_kses_post( $response_activity_data['content']['rendered'] ); ?>
											</div>												
									<?php
									$buttons = array();
									if ( ! isset( $GLOBALS['activities_template'] ) ) {
										return $buttons;
									}
									bp_nouveau_activity_entry_buttons();
									?>
									</div>
										<div class="activity-comments">
											<?php bp_activity_comments(); ?>
											<?php bp_nouveau_activity_comment_form(); ?>
											<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
										</div>
										</li>
										<?php bp_nouveau_activity_hook( 'after', 'entry' ); ?>
										<?php
						}
						++$i;
					}
					?>
							<li class="load-more">
								<a href="<?php bp_activity_load_more_link(); ?>"><?php echo esc_html_x( 'Load More', 'button', 'shortcodes-for-buddypress' ); ?></a>
							</li>
						<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : //phpcs:ignore?>
						</ul>
						<?php endif; ?>

					</div>
			</div>
					<?php
					bp_nouveau_after_activity_directory_content();
						echo '</div>';
					return ob_get_clean();
	}

	/**
	 * Call BuddyPress Members Listing shortcode
	 *
	 * @since    1.0.0
	 */
	public function buddypress_shortcodes_members_listing( $atts, $content = null ) {

		global $members_atts;
		if ( ! empty( $atts ) && array_key_exists( 'exclude_member_role', $atts ) ) {

			if ( strpos( $atts['exclude_member_role'], ',' ) !== false ) {
				$exclude_roles = explode( ',', $atts['exclude_member_role'] );
			} else {
				$exclude_roles = $atts['exclude_member_role'];
			}
			if ( isset( $atts['exclude'] ) && strpos( $atts['exclude'], ',' ) !== false ) {
				$exclude_ids = explode( ',', $atts['exclude'] );
			} else {
				$exclude_ids = array();
			}
			$users_data     = get_users( array( 'role__in' => $exclude_roles ) );
			$uid_collection = array();
			foreach ( $users_data as $key => $users ) {
				$uid_collection[] = $users->ID;
			}
			$u               = array_unique( array_merge( $uid_collection, $exclude_ids ) );
			$atts['exclude'] = rtrim( implode( ',', $u ), ',' );
			unset( $atts['exclude_member_role'] );
		}
		if ( ! empty( $atts ) && array_key_exists( 'include_member_role', $atts ) ) {
			if ( strpos( $atts['include_member_role'], ',' ) !== false ) {
				$exclude_roles = explode( ',', $atts['include_member_role'] );
			} else {
				$exclude_roles = $atts['include_member_role'];
			}
			if ( isset( $atts['include'] ) && strpos( $atts['include'], ',' ) !== false ) {
				$exclude_ids = explode( ',', $atts['include'] );
			} else {
				$exclude_ids = array();
			}
			$users_data     = get_users( array( 'role__in' => $exclude_roles ) );
			$uid_collection = array();
			foreach ( $users_data as $key => $users ) {
				$uid_collection[] = $users->ID;
			}
			$u               = array_unique( array_merge( $uid_collection, $exclude_ids ) );
			$atts['include'] = rtrim( implode( ',', $u ), ',' );
			unset( $atts['include_member_role'] );
		}

		$bpsh_query = build_query( $atts );
		if ( ! empty( $bpsh_query ) ) {
			$bpsh_query = '&' . $bpsh_query;
		}
		$default_atts = array(
			'title'               => '',
			'type'                => 'active', // Type: active ( default ) | random | newest | popular | online | alphabetical.
			'page'                => false,
			'per_page'            => 20,
			'max'                 => false,
			'page_arg'            => 'upage', // See https://buddypress.trac.wordpress.org/ticket/3679.
			'include'             => false, // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users.
			'exclude'             => false, // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users.
			'user_id'             => false, // Pass a user_id to only show friends of this user.
			'user_ids'            => false, // Pass a user_id to only show friends of this user.
			'member_type'         => false, // Can be a comma-separated list.
			'include_member_role' => '', // Can be a comma-separated list.
			'exclude_member_role' => '', // Can be a comma-separated list.
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'search_terms'        => false,
			( function_exists( 'bp_use_theme_compat_with_current_theme' ) ) ? bp_use_theme_compat_with_current_theme() : '',
			'meta_key'            => false, // Only return users with this usermeta.
			'meta_value'          => false, // Only return users where the usermeta value matches. Requires meta_key.
			'populate_extras'     => true,      // Fetch usermeta? Friend count, last active etc.
			'container_class'     => 'members', // default container,
			'loop-layout'         => ! empty( $atts['loop-layout'] ) ? $atts['loop-layout'] : '',
			'bpsh_query'          => $bpsh_query,
		);

		// Apply filter to modify the member attributes.

		$members_atts = apply_filters( 'bpsh_members_query_args', shortcode_atts( $default_atts, $atts ) );
		extract( $members_atts );
		
		$_bp_theme_package = get_option( '_bp_theme_package_id' );
		ob_start();

		if ( function_exists( 'bp_is_active' ) ) {

			if ( $title != '' ) {
				?>
				<h3 class="section-title"><span><?php echo esc_html( $title ); ?></span></h3>
				<?php
			}

			do_action( 'before_render_member_shortcode', $atts );
			if ( class_exists( 'Youzify' ) ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'youzify/members/bp-shortcodes-members-youzify.php' ) ) ? true : false;
			} elseif ( $_bp_theme_package == 'legacy' ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'legacy/members/bp-shortcodes-members.php' ) ) ? true : false ;
			} else {
				$no_template = ( false === shortcodes_for_buddypress_template( 'nouveau/members/bp-shortcodes-members.php' ) ) ? true : false ;
			}
			do_action( 'after_render_member_shortcode', $atts );

			/**
			* If none of the above template exists, then display user notice.
			* @since 2.7.2 
			*/

			if ( $no_template ) {
				?>
				<div class="bpsp-feedback bpsp-info">
					<span class="bpsp-icon"></span>
					<p><?php echo esc_html__('No Template Found.', 'shortcodes-for-buddypress'); ?></p>
				</div>
				<?php
			}
		}

		$output = ob_get_clean(); 

		$output = apply_filters( 'modify_member_output', $output, $atts, $members_atts );

		return $output; 
	}

	/**
	 * Buddypress_shortcodes_member_rest_api
	 *
	 * @param  object $atts for member data.
	 * @param  string $content for member content.
	 */
	public function buddypress_shortcodes_member_rest_api( $atts, $content = null ) {
		$param_count = 0;
		$params      = '';
		if ( ! empty( $atts ) ) {
			foreach ( $atts as $key_atts => $value_atts ) {
				if ( 'title' === $key_atts ) {
					continue;
				}
				if ( $param_count > 0 ) {
					$params .= '&';
				}
				$params .= $key_atts . '=' . $value_atts;
				++$param_count;
			}
		}
		ob_start();
		if ( isset( $atts['title'] ) && $atts['title'] != '' ) {
			?>
			<h3 class="section-title"><span><?php echo esc_html( $atts['title'] ); ?></span></h3>
			<?php
		}
		$response = wp_remote_get( get_rest_url() . 'buddypress/v1/members?' . $params );
		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			bp_nouveau_before_loop();
			?>
			<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav alignwide">
			<?php bp_nouveau_before_members_directory_content(); ?>
			<div class="screen-content">
				<div id="members-dir-list" class="members dir-list" data-bp-list="">
			<?php
			if ( bp_get_current_member_type() ) :
				?>
			<p class="current-member-type"><?php bp_current_member_type_message(); ?></p>
			<?php endif; ?>
			<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>
				<?php bp_nouveau_pagination( 'top' ); ?>
			<ul id="members-list" class="item-list members-list bp-list grid three">
				<?php
				foreach ( $response_data as $response_activity_data ) {
					$member_friend_count  = bp_get_user_meta( $response_activity_data['id'], 'total_friend_count', true );
					$member_last_activity = bp_get_user_last_activity( $response_activity_data['id'] );
					$userdata             = get_userdata( $response_activity_data['id'] );
					$mem                  = new stdClass();
					$mem->current_member  = 1;
					$mem->member_count    = 21;
					$mem_members          = new BP_Memebr_Rest_Api_Template( $response_activity_data );
					$mem->members         = array(
						$mem_members,
					);
					$mem_member           = new BP_Memebr_Rest_Api_Template( $response_activity_data );
					unset( $mem_member->friendship_status );
					unset( $mem_member->template_meta );
					$mem->member = $mem_member;
					global $members_template;
					$members_template = $mem;
					?>
					<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
						<div class="list-wrap">
							<div class="item-avatar">
								<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar( bp_nouveau_avatar_args() ); ?></a>
							</div>
							<div class="item">
								<div class="item-block">
									<h2 class="list-title member-name">
										<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
									</h2>
									<?php if ( bp_nouveau_member_has_meta() ) : ?>
										<p class="item-meta last-activity">
											<?php bp_nouveau_member_meta(); ?>
										</p><!-- .item-meta -->
									<?php endif; ?>
									<?php if ( bp_nouveau_member_has_extra_content() ) : ?>
										<div class="item-extra-content">
											<?php bp_nouveau_member_extra_content(); ?>
										</div><!-- .item-extra-content -->
									<?php endif; ?>
									<?php
									bp_nouveau_members_loop_buttons(
										array(
											'container' => 'ul',
											'button_element' => 'button',
										)
									);
									?>
								</div>
									<?php if ( bp_get_member_latest_update() && ! bp_nouveau_loop_is_grid() ) : ?>
									<div class="user-update">
										<p class="update"> <?php bp_member_latest_update(); ?></p>
									</div>
								<?php endif; ?>
							</div><!-- // .item -->
						</div>
					</li>
				<?php } ?>
			</ul>
				<?php bp_nouveau_pagination( 'bottom' ); ?>
				<?php
				else :
					bp_nouveau_user_feedback( 'members-loop-none' );
				endif;
				?>
				</div>
			</div>
			</div>
				<?php
				bp_nouveau_after_loop();

		}
		return ob_get_clean();
	}


	/**
	 * Call BuddyPress Group Listing shortcode
	 *
	 * @since    1.0.0
	 */
	public function buddypress_shortcodes_group_listing( $atts, $content = null ) {
		global $groups_atts;
		/* Check Scope is personal then pass current user id */
		if ( isset( $atts['scope'] ) && $atts['scope'] == 'personal' ) {
			$user_id         = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
			$atts['user_id'] = $user_id;
		}
		$bpsh_query = build_query( $atts );
		if ( ! empty( $bpsh_query ) ) {
			$bpsh_query = '&' . $bpsh_query;
		}

		$default_atts = array(
			'title'              => '',
			'type'               => 'alphabetical', // popular, alphabetical, invites, single-group
			'order'              => 'DESC',
			'orderby'            => 'last_activity',
			'page'               => 1,
			'per_page'           => 20,
			'max'                => false,
			'show_hidden'        => false,
			'page_arg'           => 'grpage',
			'user_id'            => false,
			'slug'               => false,
			'search_terms'       => false,
			'group_type'         => false,
			'group_type__in'     => '',
			'group_type__not_in' => '',
			'meta_query'         => false,
			( function_exists( 'bp_use_theme_compat_with_current_theme' ) ) ? bp_use_theme_compat_with_current_theme() : '',
			'include'            => false,
			'exclude'            => false,
			'parent_id'          => null,
			'update_meta_cache'  => true,
			'container_class'    => 'group', // default container,
			'loop-layout'        => ! empty( $atts['loop-layout'] ) ? $atts['loop-layout'] : '',
			'bpsh_query'         => $bpsh_query,
		);

		// Apply filter to modify the groups attributes.

		$groups_atts = apply_filters( 'bpsh_groups_query_args', shortcode_atts( $default_atts, $atts ) );
		extract( $groups_atts );

		$_bp_theme_package = get_option( '_bp_theme_package_id' );		
		ob_start();
		
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {

			if ( $title != '' ) {
				?>
				<h3 class="section-title"><span><?php echo esc_html( $title ); ?></span></h3>
				<?php
			}

			do_action( 'before_render_group_shortcode', $atts );
			if ( class_exists( 'Youzify' ) ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'youzify/groups/bp-shortcodes-groups-youzify.php' ) ) ? true : false;
			} elseif ( $_bp_theme_package == 'legacy' ) {
				$no_template = ( false === shortcodes_for_buddypress_template( 'legacy/groups/bp-shortcodes-groups.php' ) ) ? true : false;
			} else {
				$no_template = ( false === shortcodes_for_buddypress_template( 'nouveau/groups/bp-shortcodes-groups.php' ) ) ? true : false;
			}
			do_action( 'after_render_group_shortcode', $atts );

			/**
			* If none of the above template exists, then display user notice.
			* @since 2.7.2 
			*/

			if ( $no_template ) {
				?>
				<div class="bpsp-feedback bpsp-info">
					<span class="bpsp-icon"></span>
					<p><?php echo esc_html__('No Template Found.', 'shortcodes-for-buddypress'); ?></p>
				</div>
				<?php
			}
		} else {
			
			?>
			<div class="bpsp-feedback bpsp-info">
				<span class="bpsp-icon"></span>
				<p><?php echo esc_html__('Please enable the Groups Component.', 'shortcodes-for-buddypress'); ?></p>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Buddypress_shortcodes_group_listing_rest_api
	 *
	 * @param  object $atts for get group data.
	 * @param  string $content for group content.
	 */
	public function buddypress_shortcodes_group_listing_rest_api( $atts, $content = null ) {
		$group_param_count = 0;
		$group_params      = '';
		if ( ! empty( $atts ) ) {
			foreach ( $atts as $key_atts => $value_atts ) {
				if ( 'title' === $key_atts ) {
					continue;
				}
				if ( $group_param_count > 0 ) {
					$group_params .= '&';
				}
				$group_params .= $key_atts . '=' . $value_atts;
				++$group_param_count;
			}
		}
		ob_start();
		if ( isset( $atts['title'] ) && $atts['title'] != '' ) {
			?>
			<h3 class="section-title"><span><?php echo esc_html( $atts['title'] ); ?></span></h3>
			<?php
		}
		$response = wp_remote_get( get_rest_url() . 'buddypress/v1/groups?' . $group_params );
		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			?>
			<p class="current-group-type"></p>
			<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav alignwide">				
			<div class="screen-content">
			<div id="groups-dir-list" class="groups dir-list" data-bp-list="">
				<?php if ( bp_get_current_group_directory_type() ) : ?>
					<p class="current-group-type"><?php bp_current_group_directory_type_message(); ?></p>
				<?php endif; ?>
				<?php bp_nouveau_before_loop(); ?>
				<?php if ( bp_get_current_group_directory_type() ) : ?>
					<p class="current-group-type"><?php bp_current_group_directory_type_message(); ?></p>
				<?php endif; ?>
				<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>
				<ul id="groups-list" class="item-list groups-list bp-list grid three">
					<?php
					foreach ( $response_data as $response_group_data ) {
						$group_obj                = new stdClass();
						$group_obj->current_group = 0;
						$group_obj->group_count   = 20;
						$gp_groups                = new BP_Group_Rest_Api_Template( $response_group_data );
						$group_obj->groups        = array(
							$gp_groups,
						);
						$gp_group                 = new BP_Group_Rest_Api_Template( $response_group_data );
						$group_obj->group         = $gp_group;
						$groups_template          = $group_obj;
						$group_obj->in_the_loop   = 1;
						$group_obj->single_group  = '';
						global $groups_template;
						$groups_template = $group_obj;
						?>
				<li class="item-entry public group-has-avatar" data-bp-item-id="<?php echo esc_attr( $response_group_data['id'] ); ?>" data-bp-item-component="groups">
				<div class="list-wrap">
						<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
							<?php
							$args              = '';
							$group_avatar_args = bp_parse_args(
								$args,
								array(
									'type'    => 'full',
									'width'   => false,
									'height'  => false,
									'class'   => 'avatar',
									'no_grav' => false,
									'html'    => true,
									'id'      => false,
									// translators: %1$s is the name of the group.
									'alt'     => sprintf( __( 'Group logo of %1$s', 'shortcodes-for-buddypress' ), $response_group_data['name'] ),
								),
								'get_group_avatar'
							);
							$group_avatar      = bp_core_fetch_avatar(
								array(
																		'item_id'    => $response_group_data['id'],
									'avatar_dir' => 'group-avatars',
									'object'     => 'group',
									'html'       => $group_avatar_args['html'],
									'alt'        => $group_avatar_args['alt'],
									'no_grav'    => $group_avatar_args['no_grav'],
									'css_id'     => $group_avatar_args['id'],
									'class'      => $group_avatar_args['class'],
									'width'      => $group_avatar_args['width'],
									'height'     => $group_avatar_args['height'],
								)
							);
							?>
						<div class="item-avatar">
							<a href="<?php echo esc_attr( $response_group_data['link'] ); ?>"><?php echo wp_kses_post( $group_avatar ); ?></a>
						</div>
						<?php endif; ?>
					<div class="item">
						<div class="item-block">
							<h2 class="list-title groups-title">
							<a href="<?php echo esc_attr( $response_group_data['link'] ); ?>"><?php echo esc_html( $response_group_data['name'] ); ?></a>
							</h2>
								<p class="item-meta group-details">
									<?php
									$gp_count = BP_Groups_Group::get_total_member_count( $response_group_data['id'] );
									echo esc_html( ucfirst( $response_group_data['status'] ) ) . ' Group / ' . esc_html( $gp_count ) . ' members';
									?>
									</p>
							<p class="last-activity item-meta">
								<?php
									echo esc_html( $response_group_data['created_since'] );
								?>
							</p>

						</div>

						<div class="group-desc">
							<p>
								<?php
								echo esc_html( substr( $response_group_data['description']['raw'], 0, 100 ) . '...' );
								?>
							</p>
						</div>
						<?php
						bp_nouveau_groups_loop_item();
						bp_nouveau_groups_loop_buttons();
						?>
					</div>
				</div>
			</li>

						<?php
					} // foreach loop
					?>
				</ul>
				<?php else : ?>
					<?php bp_nouveau_user_feedback( 'groups-loop-none' ); ?>
				<?php endif; ?>
				<?php bp_nouveau_after_loop(); ?> 
				</div>
			</div>
			</div>
					<?php
		}
		return ob_get_clean();
	}

	/**
	 * Display Loggedin User Notifications
	 *
	 * @since    1.0.0
	 */
	public function buddypress_shortcodes_notifications( $atts, $content = null ) {
		global $notifications_atts;

		$bpsh_query = build_query( $atts );
		if ( ! empty( $bpsh_query ) ) {
			$bpsh_query = '&' . $bpsh_query;
		}

		$default_atts = array(
			'title'           => '',
			'order'           => 'DESC',
			'page'            => 1,
			'per_page'        => 20,
			'max'             => '',
			'container_class' => 'notification', // default container,
			'bpsh_query'      => $bpsh_query,
		);
		
		// Apply filter to modify the notifications attributes.
		$notifications_atts = apply_filters( 'bpsh_notification_query_args' , shortcode_atts( $default_atts, $atts ) );
		extract( $notifications_atts );
		
		$_bp_theme_package = get_option( '_bp_theme_package_id' );

		ob_start();
		if(is_user_logged_in() ){
			if ( function_exists( 'bp_is_active' ) && bp_is_active('notifications') ) {
			
				if ( $title != '' ) {
					?>
					<h3 class="section-title"><span><?php echo esc_html( $title ); ?></span></h3>
					<?php
				}
				do_action( 'before_render_notifications_shortcode', $atts );
				if ( $_bp_theme_package == 'legacy' ) {
					$no_template = ( false === shortcodes_for_buddypress_template( 'legacy/notifications/notifications-loop.php' ) ) ? true : false;
				} else {
					$no_template = ( false === shortcodes_for_buddypress_template( 'nouveau/notifications/notifications-loop.php' ) ) ? true : false;
				}
				do_action( 'after_render_notifications_shortcode', $atts );

				/**
				 * If none of the template exists, then display user notice.
				 * @since 2.7.2
				 */
				if ( $no_template ) {
					?>
					<div class="bpsp-feedback bpsp-info">
						<span class="bpsp-icon"></span>
						<p><?php echo esc_html__('No Template Found.', 'shortcodes-for-buddypress'); ?></p>
					</div>
					<?php
				}
			} else {
				
				?>
				<div class="bpsp-feedback bpsp-info">
					<span class="bpsp-icon"></span>
					<p><?php echo esc_html__('Please enable the Notifications Component.', 'shortcodes-for-buddypress'); ?></p>
				</div>
				<?php
			}
		}else{
			?>
			<div class="bpsp-feedback bpsp-info">
				<span class="bpsp-icon" aria-hidden="true"></span>
				<p><?php echo esc_html__('Please Log in to view notifications.', 'shortcodes-for-buddypress'); ?></p>
			</div>
			<?php
		}
		

		$output = ob_get_clean();

		// Apply filter on the output before returning it
		$output = apply_filters( 'modify_notifications_output', $output, $atts, $notifications_atts );
		return $output;
	}

	/**
	 * Set Activity Params when activity elementor widget load on page.
	 * 
	 * @since 1.0.0
	 * @return array $params used for internationalization before AJAX usage.
	 */
	public function shortcodes_for_buddypress_activity_localize_scripts( $params = array() ) {
		global $post;
		$current_component = '';
		if ( isset( $post->ID ) && $post->ID != '' && $post->ID != '0' ) {
			$_elementor_controls_usage = get_post_meta( get_the_ID(), '_elementor_controls_usage', true );
			$current_component         = '';
			if ( ! empty( $_elementor_controls_usage ) ) {
				foreach ( $_elementor_controls_usage as $key => $value ) {
					if ( $key == 'buddypress_shortcode_activity_widget' ) {
						$current_component = 'activity';
						break;
					}
				}
			}
		}
		
		if ( isset( $post->post_content ) && ( has_shortcode( $post->post_content, 'activity-listing' ) ) ) {
			$current_component = 'activity';
		}
		if ( $current_component == '' ) {
			return $params;
		}
				
		$activity_params = array(
			'user_id'    => bp_loggedin_user_id(),
			'object'     => 'user',
			'backcompat' => array(
				'before_post_form'  => (bool) has_action( 'bp_before_activity_post_form' ),
				'post_form_options' => (bool) has_action( 'bp_activity_post_form_options' ),
			),
			'post_nonce' => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
		);

		$user_displayname = bp_get_loggedin_user_fullname();

		if ( buddypress()->avatar->show_avatars ) {
			$width           = bp_core_avatar_thumb_width();
			$height          = bp_core_avatar_thumb_height();
			$activity_params = array_merge(
				$activity_params,
				array(
					'avatar_url'    => bp_get_loggedin_user_avatar(
						array(
							'width'  => $width,
							'height' => $height,
							'html'   => false,
						)
					),
					'avatar_width'  => $width,
					'avatar_height' => $height,
					'user_domain'   => bp_loggedin_user_domain(),
					'avatar_alt'    => sprintf(
						/* translators: %s: member name */
						__( 'Profile photo of %s', 'shortcodes-for-buddypress' ),
						$user_displayname
					),
				)
			);
		}

		/**
		 * Filters the included, specific, Action buttons.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value The array containing the button params. Must look like:
		 * array( 'buttonid' => array(
		 *  'id'      => 'buttonid',                            // Id for your action
		 *  'caption' => __( 'Button caption', 'text-domain' ),
		 *  'icon'    => 'dashicons-*',                         // The dashicon to use
		 *  'order'   => 0,
		 *  'handle'  => 'button-script-handle',                // The handle of the registered script to enqueue
		 * );
		 */
		$activity_buttons = apply_filters( 'bp_nouveau_activity_buttons', array() );

		if ( ! empty( $activity_buttons ) ) {
			$activity_params['buttons'] = bp_sort_by_key( $activity_buttons, 'order', 'num' );

			// Enqueue Buttons scripts and styles
			foreach ( $activity_params['buttons'] as $key_button => $buttons ) {
				if ( empty( $buttons['handle'] ) ) {
					continue;
				}

				if ( wp_style_is( $buttons['handle'], 'registered' ) ) {
					wp_enqueue_style( $buttons['handle'] );
				}

				if ( wp_script_is( $buttons['handle'], 'registered' ) ) {
					wp_enqueue_script( $buttons['handle'] );
				}

				unset( $activity_params['buttons'][ $key_button ]['handle'] );
			}
		}

		// Activity Objects
		if ( ! bp_is_single_item() && ! bp_is_user() ) {
			$activity_objects = array(
				'profile' => array(
					'text'                     => __( 'Post in: Profile', 'shortcodes-for-buddypress' ),
					'autocomplete_placeholder' => '',
					'priority'                 => 5,
				),
			);

			// the groups component is active & the current user is at least a member of 1 group
			if ( bp_is_active( 'groups' ) && bp_has_groups(
				array(
					'user_id' => bp_loggedin_user_id(),
					'max'     => 1,
				)
			) ) {
				$activity_objects['group'] = array(
					'text'                     => __( 'Post in: Group', 'shortcodes-for-buddypress' ),
					'autocomplete_placeholder' => __( 'Start typing the group name...', 'shortcodes-for-buddypress' ),
					'priority'                 => 10,
				);
			}

			/**
			 * Filters the activity objects to apply for localized javascript data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $activity_objects Array of activity objects.
			 */
			$activity_params['objects'] = apply_filters( 'bp_nouveau_activity_objects', $activity_objects );
		}

		$activity_strings = array(
			/* translators: %s: display buddypress user firstname.*/
			'whatsnewPlaceholder' => sprintf( __( "What's new, %s?", 'shortcodes-for-buddypress' ), bp_get_user_firstname( $user_displayname ) ),
			'whatsnewLabel'       => __( 'Post what\'s new', 'shortcodes-for-buddypress' ),
			'whatsnewpostinLabel' => __( 'Post in', 'shortcodes-for-buddypress' ),
			'postUpdateButton'    => __( 'Post Update', 'shortcodes-for-buddypress' ),
			'cancelButton'        => __( 'Cancel', 'shortcodes-for-buddypress' ),
		);

		if ( bp_is_group() ) {
			$activity_params = array_merge(
				$activity_params,
				array(
					'object'  => 'group',
					'item_id' => bp_get_current_group_id(),
				)
			);
		}

		$params['activity'] = array(
			'params'  => $activity_params,
			'strings' => $activity_strings,
		);

		return $params;
	}

	/**
	 * @param $texts
	 *
	 * @return false|string
	 */
	public static function sfb_go_pro_template( $texts ) {
		ob_start();

		?>
		<div class="elementor-nerd-box">
			<div class="elementor-nerd-box-title"><?php echo esc_html( $texts['title'] ); ?></div>
			<?php foreach ( $texts['messages'] as $message ) : ?>
				<div class="elementor-nerd-box-message"><?php echo esc_html( $message ); ?></div>
			<?php endforeach; ?>

			<?php if ( $texts['link'] ) : ?>
				<a class="elementor-button elementor-panel-scheme-title" href="<?php echo esc_url( $texts['link'] ); ?>"
					target="_blank">
					<?php echo esc_html_e( 'Go PRO', 'shortcodes-for-buddypress' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	public function buddypress_shortcodes_bp_nouveau_register_scripts( $scripts_args ) {

		if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
			return $scripts_args;
		}

		if ( isset( $scripts_args['bp-nouveau'] ) ) {
			$scripts_args['bp-nouveau']['file'] = SHORTCODES_FOR_BUDDYPRESS_PLUGIN_URL . 'public/js/buddypress-nouveau%s.js';
		}
		return $scripts_args;
	}

	public function buddypress_shortcodes_activity_heartbeat_strings( $strings = array() ) {
		global $post;
		$flg = false;
		if ( isset( $post->ID ) && $post->ID != '' && $post->ID != '0' ) {
			$_elementor_controls_usage = get_post_meta( $post->ID, '_elementor_controls_usage', true );
			if ( ! empty( $_elementor_controls_usage ) ) {
				foreach ( $_elementor_controls_usage as $key => $value ) {
					if ( $key == 'buddypress_shortcode_activity_widget' ) {
						$flg = true;
						break;
					}
				}
			}
		}
		if ( $flg == true ) {
			unset( $strings['pulse'] );
		}
		return $strings;
	}

	/**
	 * Loader for activity shortcode
	 */
	public function buddypress_shortcodes_load_activities() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bpsp-nonce' ) ) {
			return false;
		}
		$page  = ! empty( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '2';
		$limit = ! empty( $_POST['limit'] ) ? sanitize_text_field( wp_unslash( $_POST['limit'] ) ) : '10';
		if ( bp_has_activities(
			array(
				'per_page' => $limit,
				'page'     => $page,
			)
		) ) {
			ob_start();
			while ( bp_activities() ) :
				bp_the_activity();
				bp_get_template_part( 'activity/entry' );
			endwhile;
			$content = ob_get_clean();
			wp_send_json_success( $content );
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, there was no activity found.', 'shortcodes-for-buddypress' ),
				)
			);
		}
	}

	/**
	 * Load activity meta class
	 */
	public function buddypress_shortcodes_filter_activity_action_wrapper( $r ) {
		global $post;
		if ( is_a( $post, 'WP_Post' ) ) {

			if ( ! empty( $r['container_classes'] ) ) {
				$r['container_classes'] = array_merge( $r['container_classes'], array( 'activity-meta' ) );
			} else {
				$r['container_classes'] = array( 'activity-meta' );
				$r['classes']           = array( 'activity-meta' );
			}
		}
		return apply_filters( 'bp_activity_action_wrapper_classes', $r );
	}


	public function buddypress_shortcode_activity_entry_bubble_buttons( $buttons, $activity_id ) {

		global $activities_template;

		// Pin post action only for allowed posts based on user role.
		if (
		'activity_update' === $activities_template->activity->type &&
		! in_array( $activities_template->activity->privacy, array( 'media', 'document', 'video' ), true ) &&
		(
			(

					bp_current_user_can( 'administrator' ) ||
					(
						bb_is_active_activity_pinned_posts() &&
						(
							groups_is_user_admin( bp_loggedin_user_id(), bp_get_activity_item_id() ) ||
							groups_is_user_mod( bp_loggedin_user_id(), bp_get_activity_item_id() )
						)
					)

			) ||
			(
				bp_current_user_can( 'administrator' ) ||
				(
					'groups' === bp_get_activity_object_name() &&
					bb_is_active_activity_pinned_posts() &&
					(
						groups_is_user_admin( bp_loggedin_user_id(), bp_get_activity_item_id() ) ||
						groups_is_user_mod( bp_loggedin_user_id(), bp_get_activity_item_id() )
					)
				)
			)
		)
		) {

			// Remove for activities related to group for main activity screen.
			$pinned_action_label = bp_is_group_activity() ? esc_html__( 'Pin to Group', 'shortcodes-for-buddypress' ) : ( 'groups' === bp_get_activity_object_name() ? esc_html__( 'Pin to Group', 'shortcodes-for-buddypress' ) : esc_html__( 'Pin to Feed', 'shortcodes-for-buddypress' ) );
			$pinned_action_class = 'pin-activity';
			$pinned_id           = ! empty( $GLOBALS['activities_template']->pinned_id ) ? $GLOBALS['activities_template']->pinned_id : bp_get_option( 'bb_pinned_post', 0 );

			if ( 'groups' === bp_get_activity_object_name() && bp_is_active( 'groups' ) ) {
				$group_id  = bp_get_activity_item_id();
				$pinned_id = groups_get_groupmeta( $group_id, 'bb_pinned_post' );
			}

			if ( ! empty( $pinned_id ) && (int) $activity_id === (int) $pinned_id ) {
				$pinned_action_label = bp_is_group_activity() ? esc_html__( 'Unpin from Group', 'shortcodes-for-buddypress' ) : ( 'groups' === bp_get_activity_object_name() ? esc_html__( 'Unpin from Group', 'shortcodes-for-buddypress' ) : esc_html__( 'Unpin from Feed', 'shortcodes-for-buddypress' ) );
				$pinned_action_class = 'unpin-activity';
			}

			$buttons['activity_pin'] = array(
				'id'                => 'activity_pin',
				'component'         => 'activity',
				'must_be_logged_in' => true,
				'button_element'    => 'a',
				'button_attr'       => array(
					'id'            => '',
					'href'          => '',
					'class'         => 'button item-button bp-secondary-action ' . $pinned_action_class,
					'data-bp-nonce' => '',
				),
				'link_text'         => sprintf(
					'<span class="bp-screen-reader-text">%s</span><span class="delete-label">%s</span>',
					$pinned_action_label,
					$pinned_action_label
				),
			);
		}

		return $buttons;
	}
}
