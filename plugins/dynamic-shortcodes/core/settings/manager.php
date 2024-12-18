<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Settings;

use DynamicShortcodes\Core\Shortcodes\ShortcodeParser;
use DynamicShortcodes\Core\Shortcodes\StringWithShortcodesParser;
use DynamicShortcodes\Core\Shortcodes\ParseError;
use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\LicenseFacade;

class Manager {
	const MAIN_PAGE_ID            = 'dynamic-shortcodes';
	const OPTIONS_PREFIX          = 'dynamic_shortcodes_';
	const OPTION_POWER_SHORTCODES = self::OPTIONS_PREFIX . '_power_shortcodes';

	/**
	 * @var AdminPages\Manager;
	 */
	public $admin_pages;


	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'wp_ajax_dynamic_shortcodes_power_shortcodes_save', [ $this, 'power_shortcodes_save_ajax' ] );
		add_action( 'wp_ajax_dsh_get_posts', [ $this, 'get_posts_ajax_callback' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		$this->admin_pages = new AdminPages\Manager();
		add_action( 'admin_init', [ $this, 'redirect_after_activation' ] );
		if ( get_option( self::OPTIONS_PREFIX . 'enabled_in_post_title' ) === false ) {
			// we know dce license system is already be loaded after init:
			add_action( 'init', [ $this, 'initialize_settings_with_activation' ] );
		}
	}

	public function initialize_settings_with_activation() {
		$license = Plugin::instance()->license_facade->get_active_license();
		if ( ! $license ) {
			return;
		}
		$options = $this->get_setting_options();
		foreach ( $options as $option ) {
			$active = $option['default'];
			if (
				$license === LicenseFacade::DCE_LICENSE &&
				( ! isset( $option['with-license'] ) )
			) {
				$active = false;
			}
			update_option( self::OPTIONS_PREFIX . $option['id'], $active );
		}
	}

	public function redirect_after_activation() {
		if ( ! get_transient( 'dsh_do_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'dsh_do_activation_redirect' );
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}
		if ( get_option( 'dsh_onboarding_done', false ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dynamic-shortcodes-license' ) );
		exit;
	}

	// Code from https://rudrastyh.com/wordpress/select2-for-metaboxes-with-ajax.html
	public function get_posts_ajax_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}
		$return = [];
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = [
			's' => $_GET['q'],
			'post_status' => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page' => 50,
		];
		if ( $_GET['dsh_post_type'] ?? false ) {
			$args['post_type'] = $_GET['dsh_post_type'];
		}
		//phpcs:enable
		$search_results = new \WP_Query( $args );
		while ( $search_results->have_posts() ) {
			$search_results->the_post();
			// shorten the title a little
			$title    = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
			$return[] = [ $search_results->post->ID, $title ];
		}
		echo wp_json_encode( $return );
		die;
	}

	public function enqueue_admin_scripts( $hook_suffix ) {
		wp_register_style(
			'dynamic-shortcodes-admin-notice',
			DYNAMIC_SHORTCODES_URL . 'assets/css/admin-notice.css',
			[],
			DYNAMIC_SHORTCODES_VERSION,
		);
		wp_enqueue_style( 'dynamic-shortcodes-admin-notice' );

		if ( strpos( $hook_suffix, self::MAIN_PAGE_ID ) !== 0 ) {
			return;
		}

		// Only on DSH pages

		wp_register_style( 'dsh-select2', plugins_url( 'assets/lib/select2/select2.min.css', DYNAMIC_SHORTCODES__FILE__ ), [], DYNAMIC_SHORTCODES_VERSION );
		wp_register_script( 'dsh-select2', plugins_url( 'assets/lib/select2/select2.min.js', DYNAMIC_SHORTCODES__FILE__ ), [ 'jquery' ], DYNAMIC_SHORTCODES_VERSION, true );
		wp_register_style(
			'dynamic-shortcodes-admin',
			DYNAMIC_SHORTCODES_URL . 'assets/css/admin.css',
			[],
			DYNAMIC_SHORTCODES_VERSION,
		);
		wp_enqueue_style( 'dynamic-shortcodes-admin' );

		wp_register_script(
			'dynamic-shortcodes-admin',
			DYNAMIC_SHORTCODES_URL . 'assets/js/admin.js',
			[ 'dsh-select2' ],
			DYNAMIC_SHORTCODES_VERSION,
			true
		);
		wp_enqueue_script( 'dynamic-shortcodes-admin' );
		wp_enqueue_style( 'dsh-select2' );

		wp_register_script(
			'dynamic-shortcodes-demo',
			DYNAMIC_SHORTCODES_URL . 'assets/js/demo.js',
			[ 'wp-util', 'jquery' ],
			DYNAMIC_SHORTCODES_VERSION,
			true
		);
		wp_register_style(
			'dynamic-shortcodes-demo',
			DYNAMIC_SHORTCODES_URL . 'assets/css/demo.css',
			[],
			DYNAMIC_SHORTCODES_VERSION,
			false
		);
		wp_enqueue_script( 'dynamic-shortcodes-demo' );
		wp_enqueue_style( 'dynamic-shortcodes-demo' );

		if ( self::MAIN_PAGE_ID . '_page_dynamic-shortcodes-collection' === $hook_suffix ) {
			wp_register_script(
				'dynamic-shortcodes-collection',
				DYNAMIC_SHORTCODES_URL . 'assets/js/collection.js',
				[ 'jquery' ],
				DYNAMIC_SHORTCODES_VERSION,
				true
			);
			wp_register_style(
				'dynamic-shortcodes-collection',
				DYNAMIC_SHORTCODES_URL . 'assets/css/collection.css',
				[],
				DYNAMIC_SHORTCODES_VERSION,
				'all'
			);

			wp_enqueue_script( 'dynamic-shortcodes-collection' );
			wp_enqueue_style( 'dynamic-shortcodes-collection' );
		}
	}

	public function parse_shortcodes( $shortcodes ) {
		$errors            = [];
		$parsed_shortcodes = [];
		foreach ( $shortcodes as $index => $shortcode ) {
			$ok = preg_match( '/^\s*(' . ShortcodeParser::REGEX_IDENTIFIER . ')\s*$/', $shortcode['name'], $matches );
			if ( ! $ok ) {
				$errors[] = [
					'index' => $index,
					'message' => 'Invalid syntax for the name',
				];
			}
			$name = $matches[1] ?? false;
			if ( $name && isset( $parsed_shortcodes[ $name ] ) ) {
				$errors[] = [
					'index' => $index,
					'message' => esc_html__( 'This name has already been used.', 'dynamic-shortcodes' ),
				];
			}
			$shortcodes_manager = \DynamicShortcodes\Plugin::instance()->shortcodes_manager;
			try {
				$ast = $shortcodes_manager->parse_shortcodes( $shortcode['code'], 0, false );
				if ( $name ) {
					$parsed_shortcodes[ $name ] = $ast;
				}
			} catch ( ParseError $e ) {
				$errors[] = [
					'index' => $index,
					'message' => esc_html__( 'There is a syntax error in the shortcode code', 'dynamic-shortcodes' ),
				];
			}
		}
		if ( ! empty( $errors ) ) {
			return [ false, $errors ];
		}
		return [ true, $parsed_shortcodes ];
	}

	public function save_power_shortcodes( $data ) {
		[ $ok, $res ] = $this->parse_shortcodes( $data );
		if ( ! $ok ) {
			return $res;
		}
		update_option( self::OPTION_POWER_SHORTCODES, $res );
		return true;
	}

	/**
	 * If there are no errors two options are saved on the DB, one for the
	 * parsed shortcodes one for the json as sent by the frontend. This is useful
	 * for recreating the interface.
	 */
	public function power_shortcodes_save_ajax() {
		if ( ! current_user_can( 'manage_options' ) ||
			 ! wp_verify_nonce( $_POST['nonce'], 'power_shortcodes_save' ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'There was a problem while saving the Powers, you might want to copy your changes and reload the page before saving again.', 'dynamic-shortcodes' ),
				]
			);
		}
		$shortcodesjson = stripslashes( $_POST['power_shortcodes'] ?? '' );
		$shortcodes     = json_decode( $shortcodesjson, true );
		if ( ! is_array( $shortcodes ) ) {
			wp_send_json_error( [ 'message' => 'Malformed data' ] );
		}
		$res = $this->save_power_shortcodes( $shortcodes );
		if ( $res !== true ) {
			wp_send_json_error(
				[
					'message' => 'There was an error saving the shortcodes',
					'items_errors' => $res,
				]
			);
		}
		update_option( self::OPTIONS_PREFIX . '_power_shortcodes_json', $shortcodesjson );
		wp_send_json_success(
			[
				'message' => esc_html__( 'Power Shortcodes were saved successfully', 'dynamic-shortcodes' ),
			]
		);
	}

	/**
	 * 'default' refers to what happens when settings are changed on
	 *  activation, otherwise options are always disabled by default.
	 */
	public function get_setting_options() {
		$bricks_label = esc_html__( 'Bricks', 'dynamic-shortcodes' );
		if ( defined( 'BRICKS_VERSION' ) ) {
			$version = preg_replace( '/-(beta|dev|rc)\d*$/', '', BRICKS_VERSION );
			if ( version_compare( $version, '1.9.9', '<' ) ) {
				$bricks_label .= esc_html__( ' (your Bricks version is not supported! Bricks 1.9.9 or later is required) ', 'dynamic-shortcodes' );
			}
		}
		return [
			[
				'id' => 'enabled_in_post_title',
				'type' => 'checkbox',
				'label' => esc_html__( 'Post Title', 'dynamic-shortcodes' ),
				'default' => false,
			],
			[
				'id' => 'enabled_in_post_content',
				'type' => 'checkbox',
				'label' => esc_html__( 'Post Content', 'dynamic-shortcodes' ),
				'default' => false,
			],
			[
				'id' => 'enabled_in_post_excerpt',
				'type' => 'checkbox',
				'label' => esc_html__( 'Post Excerpt', 'dynamic-shortcodes' ),
				'default' => false,
			],
			[
				'id' => 'enabled_in_blocks_content',
				'type' => 'checkbox',
				'label' => esc_html__( 'Gutenberg - Blocks Content', 'dynamic-shortcodes' ),
				'default' => true,
			],
			[
				'id' => 'enabled_in_elementor_dynamic_tag',
				'type' => 'checkbox',
				'label' => esc_html__( 'Elementor - Dynamic Tag', 'dynamic-shortcodes' ),
				'default' => true,
				'with-license' => LicenseFacade::DCE_LICENSE,
			],
			[
				'id' => 'enabled_in_breakdance',
				'type' => 'checkbox',
				'label' => esc_html__( 'Breakdance', 'dynamic-shortcodes' ),
				'default' => true,
			],
			[
				'id' => 'enabled_in_oxygen',
				'type' => 'checkbox',
				'label' => esc_html__( 'Oxygen', 'dynamic-shortcodes' ),
				'default' => true,
			],
			[
				'id' => 'enabled_as_wp_shortcode',
				'type' => 'checkbox',
				'label' => esc_html__( 'WordPress Shortcode (dsh)', 'dynamic-shortcodes' ),
				'default' => true,
			],
			[
				'id' => 'enabled_in_bricks',
				'type' => 'checkbox',
				'label' => $bricks_label,
				'default' => true,
			],
		];
	}

	public function get_setting_fields() {
		$fields = [
			[
				'id' => 'locations',
				'label' => esc_html__( 'Where to enable Dynamic Shortcodes', 'dynamic-shortcodes' ),
				'subfields' => $this->get_setting_options(),
			],
			[
				'id' => 'more',
				'label' => esc_html__( 'More Settings', 'dynamic-shortcodes' ),
				'subfields' => [
					[
						'id' => 'hide_errors_in_frontend',
						'type' => 'checkbox',
						'label' => esc_html__( 'Hide errors and warnings in frontend', 'dynamic-shortcodes' ),
						'default' => false,
						'no-license-checks' => true,
					],
				],
			],
		];
		return $fields;
	}

	public function render_field( $field ) {
		$subfields = $field['subfields'] ?? false;
		if ( $subfields ) {
			foreach ( $subfields as $field ) {
				$this->render_field( $field );
			}
			return;
		}
		$license  = Plugin::instance()->license_facade->get_active_license();
		$readonly = false;
		if ( ! isset( $field['no-license-checks'] ) ) {
			$readonly = ! $license ||
				( ( ! isset( $field['with-license'] ) ) &&
				  $license !== LicenseFacade::DSH_LICENSE );
		}
		switch ( $field['type'] ) {
			case 'checkbox':
				$field_id = self::OPTIONS_PREFIX . $field['id'];
				$value    = get_option( $field_id );
				echo '<label>';
				$checked  = checked( $value, true, false );
				$disabled = $readonly ? 'disabled' : '';
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "<input type=checkbox value=1 name='$field_id' $checked $disabled>";
				echo $field['label'];
				echo '</label><br>';
				// phpcs:enable
				break;
			case 'custom':
				$field['render_callback']();
				break;
		}
	}

	public function register_setting( $page_id, $prefix, $field ) {
		if ( ! isset( $field['sanitize_callback'] ) ) {
			register_setting( self::MAIN_PAGE_ID, $prefix . $field['id'] );
		} else {
			register_setting( self::MAIN_PAGE_ID, $prefix . $field['id'], [ 'sanitize_callback' => $field['sanitize_callback'] ] );
		}
	}

	public function register_settings() {
		foreach ( $this->get_setting_fields() as $field ) {
			$subfields = $field['subfields'] ?? false;
			$prefix    = self::OPTIONS_PREFIX;
			if ( ! $subfields ) {
				$this->register_setting( self::MAIN_PAGE_ID, $prefix, $field );
			} else {
				foreach ( $subfields as $subfield ) {
					$this->register_setting( self::MAIN_PAGE_ID, $prefix, $subfield );
				}
			}
			add_settings_field(
				$prefix . $field['id'],
				$field['label'],
				function () use ( $field ) {
					$this->render_field( $field );
				},
				self::MAIN_PAGE_ID,
				'dynamic_shortcodes_general'
			);
		}
	}

	public function print_license_notice( $messages_id ) {
		$license_active = Plugin::instance()->license_facade->get_active_license();
		if ( $license_active === LicenseFacade::DSH_LICENSE ) {
			return;
		}
		$license_url = esc_attr( admin_url( '/admin.php?page=dynamic-shortcodes-license' ) );
		if ( ! $license_active ) {
			// translators: 1 and 2 are html tags.
			$msg = esc_html__( 'You are not able to change the settings because the license is not active. Please %1$sactivate the license%2$s.', 'dynamic-shortcodes' );
		} elseif ( $license_active === LicenseFacade::DCE_LICENSE ) {
			// translators: 1 and 2 are html tags.
			$msg = esc_html__( 'Your license is provided by Dynamic Content for Elementor. Contrary to the normal license not all settings can be changed. Check the %1$slicense page%2$s for more information.', 'dynamic-shortcodes' );
		} else {
			throw new \LogicException( 'Unreachable' );
		}
		$msg            = sprintf( $msg, "<a href=$license_url>", '</a>' );
		$license_notice = $msg;
		add_settings_error( $messages_id, $messages_id, $license_notice, 'warning' );
	}

	public function render_main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$messages_id = self::MAIN_PAGE_ID . '_messages';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( $messages_id, $messages_id, esc_html__( 'Settings Saved', 'dynamic-shortcodes' ), 'updated' );
		}
		$this->print_license_notice( $messages_id );
		// show error/update messages
		settings_errors( $messages_id );
		echo '<div class=wrap>';
		$title = esc_html( get_admin_page_title() );
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<h1>$title</h1>";
		echo '<form action=options.php method=post>';
		settings_fields( self::MAIN_PAGE_ID );
		echo '<table class=form-table>';
		do_settings_fields( self::MAIN_PAGE_ID, 'dynamic_shortcodes_general' );
		echo '</table>';
		submit_button( esc_html__( 'Save Settings', 'dynamic-shortcodes' ) );
		echo '</form>';
		echo '</div>';
	}

	public function render_power_shortcodes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$license_active = Plugin::instance()->license_facade->get_active_license();

		$id          = self::OPTIONS_PREFIX . '_power_shortcodes_json';
		$value       = esc_attr( get_option( $id, '' ) );
		$ajax_url    = admin_url( 'admin-ajax.php' );
		$ajax_action = 'dynamic_shortcodes_power_shortcodes_save';
		$nonce       = wp_create_nonce( 'power_shortcodes_save' );
		$title       = esc_html( get_admin_page_title() );
		// translators: specifiers are html tags.
		$info = esc_html__( 'Power Shortcodes are a type of Dynamic Shortcodes that come without the usual security restrictions. Please note that Power Shortcodes can only be created by administrators, but can be utilized by contributors as well.', 'dynamic-shortcodes' );

		$info .= '<h2>' . esc_html__( 'Examples of Power Shortcodes', 'dynamic-shortcodes' ) . '</h2>';
		// translators: %1$s: Dynamic Shortcode code, %3$s: Dynamic Shortcode description
		$info .= esc_html__( 'By default, the Dynamic Shortcode %1$s is not accessible due to security restrictions. However, you can use a Power Shortcode to bypass these restrictions. To do this, you can create a Power Shortcode called %3$s and include %1$s within it.', 'dynamic-shortcodes' );
		$info .= '<br />';
		// translators: %1$s: Dynamic Shortcode code, %2$s: Dynamic Shortcode code
		$info .= esc_html__( 'Once this is done, you can call the shortcode %2$s which in turn will call %1$s this will allow the user\'s email address to be displayed without violating the security restrictions normally applied to Dynamic Shortcodes.', 'dynamic-shortcodes' );
		$info  = sprintf( $info, '<code>{user:email}</code>', '<code>{power:myemailaddress}</code>', '<code>myemailaddress</code>' );

		$help_links                     = [
			[
				'title' => __( 'How to Build a Power Shortcode', 'dynamic-shortcodes' ),
				'url' => 'https://dnmc.ooo/dsh-doc-power',
			],
			[
				'title' => __( 'The Syntax of Dynamic Shortcodes', 'dynamic-shortcodes' ),
				'url' => 'https://dnmc.ooo/dsh-doc-syntax',
			],
		];
		$data_license_active            = $license_active ? 'yes' : 'no';
		$maybe_disabled_because_license = $license_active ? '' : 'disabled';
		$html                           = <<<END
<div class=wrap>
  <h1>$title</h1>
  {{license_notice}}
  <template id=power-shortcodes-template>
	<li class=power-shortcodes-repeater-item>
	  <ul class='power-shortcodes-item-error hidden'></ul>
	  <div class=power-shortcodes-name-field>
	<label><span class=power-field-label>Name</span> <input type="text" class=power-shortcodes-name placeholder="Enter a custom name for this Power Shortcode, so you can easily recall it later"></label>
	  </div>
	  <div class=power-shortcodes-code-field>
	<label><span class=power-field-label>Content</span>
	  <div class=power-shortcodes-code-wrapper>
	  <textarea cols=80 rows=4 spellcheck=false class=power-shortcodes-code placeholder="Insert text with Dynamic Shortcodes"></textarea>
	  <button class=power-shortcodes-format type=button>Indent</button>
	  </div>
	</label>
	  </div>
	  <div class='power-shortcodes-usage'></div>
	  <button class=power-shortcodes-del type=button>Delete</button>
	</li>
  </template>
  <div class="content-wrapper">
	<form id=power-shortcodes-form class=dynamic-shortcodes-tab action='$ajax_url' data-license-active='$data_license_active'>
		<input type=hidden name=nonce value='$nonce'>
		<input type=hidden name=action value="$ajax_action">
		<input type=hidden name=power_shortcodes value='$value'>
		<div class=power-shortcodes-info>$info</div>
		<button class="button button-primary power-shortcodes-save" type=button $maybe_disabled_because_license>Save Changes</button>
		<div id=power-shortcodes-form-message class="notice hidden"></div>
		<div id=power-shortcodes-wrapper>
		<ul id=power-shortcodes-repeater></ul>
		<button type=button id=power-shortcodes-add $maybe_disabled_because_license>New</button>
		</div>
		<button class="button button-primary power-shortcodes-save" type=button $maybe_disabled_because_license>Save Changes</button>
	</form>
	<div id="dsh-help">
	  <h2>Documentation</h2>
	  <ul>
		{{help_links}}
	  </ul>
	</div>
</div>
END;

		$link_html = '';

		foreach ( $help_links as $link ) {
			$link_html .= '<li><a href="' . $link['url'] . '">' . $link['title'] . '</a></li>';
		}

		$html = str_replace( '{{help_links}}', $link_html, $html );

		$license_notice = '';
		if ( ! $license_active ) {
			$license_url = esc_attr( admin_url( '/admin.php?page=dynamic-shortcodes-license' ) );
			// translators: 1 and 2 are html tags.
			$msg            = esc_html__( 'You are not able to edit the Power Shortcodes because the license is not active. Please %1$sactivate the license%2$s.', 'dynamic-shortcodes' );
			$msg            = sprintf( $msg, "<a href=$license_url>", '</a>' );
			$license_notice = '<div class="notice notice-error">' . $msg . '</div>';
		}
		$html = str_replace( '{{license_notice}}', $license_notice, $html );
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	public function render_getting_started_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'dsh_onboarding_done', true );
		$backend_types = Plugin::instance()->demo_manager->get_backend_types();

		$title = esc_html( get_admin_page_title() );
		// translators: specifiers are html tags.
		$info  = esc_html__( 'This plugin is a tool that allows you to create and use "shortcodes" within your content. You can think of Dynamic Shortcodes as a placeholder for a dynamically generated value.', 'dynamic-shortcodes' );
		$info .= '<h2>' . esc_html__( 'Examples of Dynamic Shortcodes', 'dynamic-shortcodes' ) . '</h2>';
		// translators: %1$s: Dynamic Shortcode,
		$info .= '<p>' . sprintf( esc_html__( 'The %1$s shortcode can be used to retrieve and display the title of the current post. When you insert this shortcode into your content, the Dynamic Shortcodes plugin will recognize it and automatically replace the shortcode with the title of the post where it\'s located.', 'dynamic-shortcodes' ), '<code>{post:title}</code>' ) . '</p>';
		$info .= '<p>' . esc_html__( 'Examples of use', 'dynamic-shortcodes' ) . '</p>';
		$info .= '<pre>
		Hello, you\'re reading "{post:title}"!
		</pre>';
		$info .= '<p>' . esc_html__( 'If the current post\'s title was "Snorkeling Guide", the result would be:', 'dynamic-shortcodes' ) . '</p>';
		$info .= '<pre>
		Hello, you\'re reading "Snorkeling Guide"!
		</pre>';
		// translators: %1$s: Dynamic Shortcode code,
		$info     .= '<p>' . sprintf( esc_html__( 'The %1$s shortcode can be used to retrieve and display the nickname of the current user. When you insert this shortcode into your content, the Dynamic Shortcodes plugin will recognize it and automatically replace the shortcode with the nickname of the user viewing the content.', 'dynamic-shortcodes' ), '<code>{user:nickname}</code>' ) . '</p>';
		$info     .= '<p>' . esc_html__( 'Examples of use', 'dynamic-shortcodes' ) . '</p>';
		$info     .= '<pre>
		Welcome, {user:nickname}!
		</pre>';
		$info     .= '<p>' . esc_html__( 'If the current user\'s nickname was "Charles", the result would be:', 'dynamic-shortcodes' ) . '</p>';
		$info     .= '<pre>
		Welcome, Charles!
		</pre>';
		$info     .= '<p>' . esc_html__( 'These are just a few examples of how you can use the Dynamic Shortcodes plugin to create dynamic and customized content. ', 'dynamic-shortcodes' ) . '</p>';
		$demos_url = esc_attr( admin_url( '/admin.php?page=dynamic-shortcodes-demo' ) );
		// translators: 1 and 2 are html tags.
		$to_demos = esc_html__( 'You can check the %1$sDemo Shortcodes%2$s page for more examples.', 'dynamic-shortcodes' );
		$info    .= '<p>' . sprintf( $to_demos, "<a href=$demos_url>", '</a>' );

		$help_links = [
			[
				'title' => __( 'Where Dynamic Shortcodes Can Be Used', 'dynamic-shortcodes' ),
				'url' => 'https://dnmc.ooo/dsh-doc-where',
			],
			[
				'title' => __( 'The Syntax of Dynamic Shortcodes', 'dynamic-shortcodes' ),
				'url' => 'https://dnmc.ooo/dsh-doc-syntax',
			],
		];

		$html = <<<END
<div class=wrap>
  <h1>$title</h1>

  <div class="content-wrapper">
	<div class=dynamic-shortcodes-tab>$info</div>
	<div id="dsh-help">
	  <h2>Documentation</h2>
	  <ul>
		{help_links}
	  </ul>
	</div>
</div>
END;

		$link_html = '';

		foreach ( $help_links as $link ) {
			$link_html .= '<li><a href="' . $link['url'] . '">' . $link['title'] . '</a></li>';
		}

		$html = str_replace( '{help_links}', $link_html, $html );

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	public function add_menu_pages() {
		add_menu_page(
			esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' ),
			esc_html__( 'Dynamic Shortcodes', 'dynamic-shortcodes' ),
			'manage_options',
			self::MAIN_PAGE_ID,
			[ $this, 'render_main_page' ],
			'data:image/svg+xml;base64,' . self::get_dynamic_ooo_icon_svg_base64(),
			59
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG . ' - ' . esc_html__( 'Settings', 'dynamic-shortcodes' ),
			esc_html__( 'Settings', 'dynamic-shortcodes' ),
			'manage_options',
			self::MAIN_PAGE_ID,
			[ $this, 'render_main_page' ],
			1
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG . ' - ' . esc_html__( 'Power Shortcodes', 'dynamic-shortcodes' ),
			esc_html__( 'Power Shortcodes', 'dynamic-shortcodes' ),
			'manage_options',
			'dynamic-shortcodes-power',
			[ $this, 'render_power_shortcodes_page' ],
			2
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG . ' - ' . esc_html__( 'Getting Started', 'dynamic-shortcodes' ),
			esc_html__( 'Getting Started', 'dynamic-shortcodes' ),
			'manage_options',
			'dynamic-shortcodes-getting-started',
			[ $this, 'render_getting_started_page' ],
			3
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG . ' - ' . esc_html__( 'Demo Shortcodes', 'dynamic-shortcodes' ),
			esc_html__( 'Demo Shortcodes', 'dynamic-shortcodes' ),
			'manage_options',
			'dynamic-shortcodes-demo',
			[ $this, 'demos_page' ],
			4
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			esc_html__( 'Collection', 'dynamic-shortcodes' ),
			esc_html__( 'Collection', 'dynamic-shortcodes' ),
			'manage_options',
			'dynamic-shortcodes-collection',
			[ Plugin::instance()->settings_manager->admin_pages->collection, 'render' ],
			5
		);
		add_submenu_page(
			self::MAIN_PAGE_ID,
			DYNAMIC_SHORTCODES_PRODUCT_NAME_LONG . ' - ' . esc_html__( 'License', 'dynamic-shortcodes' ),
			esc_html__( 'License', 'dynamic-shortcodes' ),
			'manage_options',
			'dynamic-shortcodes-license',
			[ Plugin::instance()->settings_manager->admin_pages->license, 'render_license_page' ],
			6
		);
	}

	public function render_select2( $selected_post ) {
		echo '<div class=demos-preview-select-wrapper style="display: none"><label for=dsh-demos-post-selector>';
		echo esc_html__( 'Select Preview Post', 'dynamic-shortcodes' );
		echo '</label><br />';
		echo '<select id=dsh-demos-post-selector>';
		if ( $selected_post ) {
			echo '<option value="' . esc_attr( $selected_post->ID ) . '">';
			echo esc_html( $selected_post->post_title ) . '</option>';
		}
		echo '</select></div>';
	}

	public function demos_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		$demo_manager = Plugin::instance()->demo_manager;
		$preview_post = null;
		if ( isset( $_GET['demos_post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$preview_post = get_post( $_GET['demos_post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$preview_post = get_posts( [ 'posts_per_page' => 1 ] )[0] ?? null;
		}
		if ( $preview_post ) {
			$GLOBALS['post'] = $preview_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $GLOBALS['post'] );
		}
		echo '<div class="wrap dynamic-shortcodes-demo"><h1>' . esc_html__( 'Demo Shortcodes', 'dynamic-shortcodes' ) . '</h1>';

		echo '<ul id=demo-tabs-bar>';
		foreach ( $demo_manager->get_backend_types() as $type ) {
			$class         = $demo_manager->check_plugin_depends( $type ) ? 'disabled' : 'enabled';
			$show_selector = $demo_manager->should_show_preview_post_selector( $type ) ? 'yes' : 'no';
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<li class=$class><a href='#tab-$type' data-show-post-selector=$show_selector>$type</a></li>";
		}
		echo '</ul>';
		echo '<div class="content-wrapper">';
		echo '<div class="dynamic-shortcodes-demo-tab">';
		$this->render_select2( $preview_post );
		foreach ( $demo_manager->get_backend_types() as $type ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<div class=dsh-tab id='tab-$type'>";
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Plugin::instance()->shortcodes_manager->expand_shortcodes( "{demo:$type}" );
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	/**
	 * @return string
	 */
	public static function get_dynamic_ooo_icon_svg_base64() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 88.74 71.31"><path d="M35.65,588.27h27.5c25.46,0,40.24,14.67,40.24,35.25v.2c0,20.58-15,35.86-40.65,35.86H35.65Zm27.81,53.78c11.81,0,19.65-6.51,19.65-18v-.2c0-11.42-7.84-18-19.65-18H55.41v36.26Z" transform="translate(-35.65 -588.27)" fill="#a8abad"/><path d="M121.69,609.94a33.84,33.84,0,0,0-7.56-11.19,36.51,36.51,0,0,0-11.53-7.56A37.53,37.53,0,0,0,88,588.4a43.24,43.24,0,0,0-5.4.34,36.53,36.53,0,0,1,20.76,10,33.84,33.84,0,0,1,7.56,11.19,35.25,35.25,0,0,1,2.7,13.79v.2a34.79,34.79,0,0,1-2.75,13.79,35.21,35.21,0,0,1-19.19,18.94,36.48,36.48,0,0,1-9.27,2.45,42.94,42.94,0,0,0,5.39.35,37.89,37.89,0,0,0,14.67-2.8,35.13,35.13,0,0,0,19.19-18.94,34.79,34.79,0,0,0,2.75-13.79v-.2A35.25,35.25,0,0,0,121.69,609.94Z" transform="translate(-35.65 -588.27)" fill="#a8abad" /></svg>';
		//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $svg );
	}
}
