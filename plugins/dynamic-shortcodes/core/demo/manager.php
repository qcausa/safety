<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo;

use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\Composer;

class Manager {
	/**
	 * @var array<string>
	 */
	public $types_accepted = [
		'post',
		'user',
		'date',
		'query',
		'author',
		'term',
		'option',
		'archive',
		'woo',
		'acf',
		'metabox',
		'jet',
		'toolset',
		'pods',
		'call',
		'cookie',
		'param-get',
		'param-post',
		'server',
		'query-var',
	];

	/**
	 * @var array<string>
	 */
	public $backend_types = [
		'post',
		'user',
		'date',
		'query',
		'author',
		'term',
		'option',
		'woo',
		'acf',
		'metabox',
		'jet',
		'toolset',
		'pods',
		'call',
		'cookie',
	];

	/**
	 * @var array<string,BaseDemo>
	 */
	protected $demo;

	public function __construct() {
		$this->demo = [
			'post' => [
				'demo_class' => 'Post',
				'type_class' => 'Post',
				'show_preview_post_selector' => true,
			],
			'acf' => [
				'demo_class' => 'Acf',
				'type_class' => 'Acf',
				'plugin_depends' => 'acf',
				'show_preview_post_selector' => true,
			],
			'date' => [
				'demo_class' => 'Date',
				'type_class' => 'Date',
			],
			'user' => [
				'demo_class' => 'User',
				'type_class' => 'User',
			],
			'author' => [
				'demo_class' => 'Author',
				'type_class' => 'Author',
				'show_preview_post_selector' => true,
			],
			'cookie' => [
				'demo_class' => 'Cookie',
				'type_class' => 'Cookie',
			],
			'identity' => [
				'demo_class' => 'Identity',
				'type_class' => 'Identity',
			],
			'jet' => [
				'demo_class' => 'JetEngine',
				'type_class' => 'JetEngine',
				'plugin_depends' => 'jet-engine',
				'show_preview_post_selector' => true,
			],
			'metabox' => [
				'demo_class' => 'Metabox',
				'type_class' => 'Metabox',
				'plugin_depends' => 'metabox',
				'show_preview_post_selector' => true,
			],
			'option' => [
				'demo_class' => 'Option',
				'type_class' => 'Option',
			],
			'param-get' => [
				'demo_class' => 'ParameterGet',
				'type_class' => 'Parameter',
			],
			'param-post' => [
				'demo_class' => 'ParameterPost',
				'type_class' => 'Parameter',
			],
			'pods' => [
				'demo_class' => 'Pods',
				'type_class' => 'Pods',
				'plugin_depends' => 'pods',
				'show_preview_post_selector' => true,
			],
			'server' => [
				'demo_class' => 'Server',
				'type_class' => 'Server',
			],
			'archive' => [
				'demo_class' => 'Archive',
				'type_class' => 'Archive',
			],
			'term' => [
				'demo_class' => 'Term',
				'type_class' => 'Term',
			],
			'toolset' => [
				'demo_class' => 'Toolset',
				'type_class' => 'Toolset',
				'plugin_depends' => 'toolset',
				'show_preview_post_selector' => true,
			],
			'query' => [
				'demo_class' => 'Query',
				'type_class' => 'Query',
			],
			'query-var' => [
				'demo_class' => 'QueryVar',
				'type_class' => 'QueryVar',
			],
			'woo' => [
				'demo_class' => 'WooCommerce',
				'type_class' => 'WooCommerce',
				'plugin_depends' => 'woocommerce',
				'show_preview_post_selector' => true,
			],
			'call' => [
				'demo_class' => 'Call',
				'type_class' => 'Call',
			],
		];

		add_action( 'wp_ajax_dsh_expand_list_ajax', [ $this, 'admin_expand_list_ajax' ] );
		add_action( 'wp_ajax_dsh_create_shortcode', [ $this, 'create_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets() {
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
	}

	/**
	 * Render
	 *
	 * @param string $type
	 * @return void
	 */
	public function render( $type ) {
		$demo_class = '\DynamicShortcodes\Core\Demo\Types\\' . $this->demo[ $type ]['demo_class'];

		if ( ! class_exists( $demo_class ) ) {
			return;
		}

		$demo_object = new $demo_class();
		return $demo_object->render_list();
	}

	public function render_all_types() {
		$content = '';
		$title   = '<h1><strong>Dynamic Shortcodes</strong> Demo</h1>';
		foreach ( $this->types_accepted as $type ) {
			$content .= '<details>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$content .= '<summary>' . $type . '</summary>';
			$content .= Plugin::instance()->shortcodes_manager->expand_shortcodes( "{demo:$type}" );
			$content .= '</details>';
		}

		return $title . $content;
	}

	/**
	 * returns false if ok, or the missing plugin if not
	 */
	public function check_plugin_depends( $type ) {
		$demo = $this->demo[ $type ];
		if ( ! isset( $demo['plugin_depends'] ) ) {
			return false;
		}
		$plugin = $demo['plugin_depends'];
		if ( ! Plugin::instance()->shortcodes_manager->check_plugin_dependency( $plugin ) ) {
			return $plugin;
		}
		return false;
	}

	public function should_show_preview_post_selector( $type ) {
		return $this->demo[ $type ]['show_preview_post_selector'] ?? false;
	}

	public function get_demo() {
		return $this->demo;
	}

	/**
	 * Get Types Accepted
	 *
	 * @return array<string>
	 */
	public function get_types_accepted() {
		return $this->types_accepted;
	}

	/**
	 * Get Backend Types
	 *
	 * @return array<string>
	 */
	public function get_backend_types() {
		return $this->backend_types;
	}

	/**
	 * ajax accepts a list of string with shortcodes as json, returns the list
	 * with the shortcodes expanded.
	 */
	public function admin_expand_list_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}

		global $post;

		// phpcs:ignore WordPress.Security.NonceVerification
		$post_id = $_POST['post_id'];
		// phpcs:ignore WordPress.Security.NonceVerification
		$result_type = $_POST['result_type'];
		// phpcs:ignore WordPress.Security.NonceVerification
		$original = $_POST['original'];

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$post = get_post( $post_id );
		setup_postdata( $post );

		$result = array_map(
			function ( $item ) {
				$code   = stripcslashes( $item['code'] );
				$result = [
					'result' => Plugin::instance()->shortcodes_manager->expand_shortcodes( $code, [ 'privileges' => 'all' ] ),
					'code' => $code,
				];
				return array_merge( $item, $result );
			},
			$this->add_examples( $result_type, $original )
		);

		wp_send_json_success( $result );
	}

	public function create_shortcode() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$settings = $_POST['settings'];

		if ( ! $settings ) {
			wp_send_json_error( 'Settings not provided or invalid' );
			return;
		}

		$shortcode = \DynamicShortcodes\Core\Elementor\DynamicTags\Wizard::process_settings( $settings );

		if ( ! $shortcode ) {
			wp_send_json_error( 'Missing settings to create the shortcode. Please check your settings and try again.' );
			return;
		}

		wp_send_json_success( $shortcode );
	}

	private function get_examples( $type ) {
		$examples = [
			'string' => [
				[
					'description' => esc_html__( 'Set it in uppercase', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'strtoupper',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Set it in lowercase', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'strtolower',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Extract the first 5 characters', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'substr(0,5)',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Uppercase the first character of each word', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'ucwords',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'String length', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'strlen',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Remove all occurrences of the "a" character', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|-',
								'name' => "str_replace('a','')",
							],
						],
					],
				],
			],

			'int' => [
				[
					'description' => esc_html__( 'Add 1', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => '+',
								'args' => [
									'1',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Subtract 1', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => '-',
								'args' => [
									'1',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Multiply by 2', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => '*',
								'args' => [
									'2',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Divide by 2', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => '/',
								'args' => [
									'2',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Square root', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'sqrt',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Num raised to the power of 3', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'pow(3)',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Format a number with grouped thousands (English notation)', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'number_format(2)',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Format a number with grouped thousands (French notation)', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => "number_format(2, ',', ' ')",
							],
						],
					],
				],
			],

			'array' => [
				[
					'description' => esc_html__( 'Cycle the array in a list', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{get:value}',
										],
									],
								],
								'keyargs' => [],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array and separate each element with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'{get:value}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-of-arrays' => [
				[
					'description' => esc_html__( 'Cycle the array of arrays in a list with keys and values', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'key',
									'value',
									[
										'type' => 'template',
										'data' => [
											'<h4>{get:key}</h4> {for:item {get:value} [<li>{get:item}]}',
										],
									],
								],
								'keyargs' => [],
							],
							'args_position' => 2,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array of arrays with keys and values separated by comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'key',
									'value',
									[
										'type' => 'template',
										'data' => [
											"<strong>{get:key}</strong>: {for:item {get:value} [{get:item}] @sep=', '} </ br>",
										],
									],
								],
								'keyargs' => [],
							],
							'args_position' => 2,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-acf-repeater' => [
				[
					'description' => esc_html__( 'Cycle the ACF Repeater in a list and retrieve the subfield', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'acf-loop',
								'args' => [
									[
										'type' => 'template',
										'data' => [
											'<li>{acf:*placeholder-field-name*@sub}',
										],
									],
								],
							],
							'args_position' => 0,
							'args_without_type' => true,
							'function_for_placeholder' => 'get_first_subfield_name',
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the ACF Repeater and separate the subfield with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'acf-loop',
								'args' => [
									[
										'type' => 'template',
										'data' => [
											'{acf:*placeholder-field-name*@sub}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 0,
							'args_without_type' => true,
							'function_for_placeholder' => 'get_first_subfield_name',
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-post-id' => [
				[
					'description' => esc_html__( 'Loop over posts and retrieve the post title', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'loop',
								'args' => [
									[
										'type' => 'template',
										'data' => [
											'<li>{post:title}',
										],
									],
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Loop over posts and retrieve the post title and permalink', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'loop',
								'args' => [
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{post:permalink}">{post:title}</a>',
										],
									],
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Loop over posts, retrieve the post title and separate each post with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'loop',
								'args' => [
									[
										'type' => 'template',
										'data' => [
											'{post:title}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-user-id' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the nickname for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{user:nickname @ID={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the nickname and author URL for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{user:ID @ID={get:value}|get_author_posts_url}<br />{user:nickname @ID={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array, retrieve the nickname and author URL and separate each element with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'{user:ID @ID={get:value}|get_author_posts_url} - {user:nickname @ID={get:value}}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-term-id' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the name for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{term:name @ID={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the name and the permalink for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{term:permalink @ID={get:value}}">{term:name @ID={get:value}}</a>',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array, retrieve the name and separate each element with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'{term:name @ID={get:value}}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-media-id' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the title and permalink for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{media:permalink @ID={get:value}}">{media:title @ID={get:value}}</a>',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array and retrieve the image for each ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'{media:image @ID={get:value} size=thumbnail}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Display structured information about the array. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'array-wp_post' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the title for each WP_Post', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{post:title @ID={get:value |. ID}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the title and permalink for each WP_Post', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{post:permalink @ID={get:value |. ID}}">{post:title @ID={get:value |. ID}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array, retrieve the name and the archive link for each WP_Term and separate with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{post:permalink @ID={get:value |. ID}}">{post:title @ID={get:value |. ID}}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
			],

			'array-wp_user' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the nickname for each WP_User', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{user:nickname @object={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the nickname and the author URL for each WP_User', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{user:ID @object={get:value}|get_author_posts_url}">{user:nickname @object={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array, retrieve the nickname and the author URL for each WP_Post and separate with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<a href="{user:ID @object={get:value}|get_author_posts_url}">{user:nickname @object={get:value}}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
			],

			'array-wp_term' => [
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the name for each WP_Term', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li>{term:name @object={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array in a list and retrieve the name and the archive link for each WP_Term', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<li><a href="{term:permalink @object={get:value}}">{term:name @object={get:value}}',
										],
									],
								],
							],
							'args_position' => 1,
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the array, retrieve the name and the archive link for each WP_Term and separate with a comma', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'<a href="{term:permalink @object={get:value}}">{term:name @object={get:value}}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],
				],
			],

			'object' => [
				[
					'description' => esc_html__( 'Display structured information about the object. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
			],

			'wp_term' => [
				[
					'description' => esc_html__( 'Display structured information about the object. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Term Name', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'term',
								'args' => [
									'name',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Term Slug', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'term',
								'args' => [
									'slug',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Term ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'term',
								'args' => [
									'id',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
			],

			'wp_user' => [
				[
					'description' => esc_html__( 'Display structured information about the object. It\'s useful for debug', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Nickname', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'user',
								'args' => [
									'nickname',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'User ID', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'user',
								'args' => [
									'id',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Email', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'user',
								'args' => [
									'email',
								],
							],
							'position_for_original' => [
								'keyargs' => 'object',
							],
						],
					],
				],
			],

			'media-id' => [
				[
					'description' => esc_html__( 'URL', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'url',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'HTML Tag (Thumbnail Size)', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'image',
								],
								'keyargs' => [
									'size' => 'thumbnail',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'HTML Tag (Medium Size)', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'image',
								],
								'keyargs' => [
									'size' => 'medium',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'HTML Tag (Full Size - Default)', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'image',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Title of the Media File', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'title',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Alternative Text of the Media File', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'alt-text',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Caption of the Media File', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'caption',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Description of the Media File', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'media',
								'args' => [
									'description',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
			],

			'post-id' => [
				[
					'description' => esc_html__( 'Title', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'post',
								'args' => [
									'title',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Featured Image', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'post',
								'args' => [
									'featured-image-id',
								],
								'filters' => [
									[
										'type' => '|',
										'name' => 'wp_get_attachment_image(thumbnail)',
									],
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Post Date', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'post',
								'args' => [
									'date',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Permalink', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'post',
								'args' => [
									'permalink',
								],
							],
							'position_for_original' => [
								'keyargs' => 'id',
							],
						],
					],
				],
			],

			'user-id' => [
				[
					'description' => esc_html__( 'Display Name', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'user',
								'args' => [
									'display_name',
								],
							],
							'position_for_original' => [
								'keyargs' => 'ID',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Avatar', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'user',
								'args' => [
									'avatar',
								],
							],
							'position_for_original' => [
								'keyargs' => 'ID',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Author Posts URL', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'get_author_posts_url',
							],
						],
					],
				],
			],

			'term-id' => [
				[
					'description' => esc_html__( 'Term Link', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'get_term_link',
							],
						],
					],
				],
			],

			'date' => [
				[
					'description' => esc_html__( '-1 day', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'date',
								'args' => [
									'-1 day',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( '-2 days with format d/m/Y H:i:s', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'date',
								'args' => [
									'-2 days',
								],
								'keyargs' => [
									'format' => 'd/m/Y H:i:s',
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( '+1 month with format d/m/Y', 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'date',
								'args' => [
									'+1 month',
								],
								'keyargs' => [
									'format' => 'd/m/Y',
								],
							],
							'args_position' => 0,
						],
					],
				],
			],

			'float' => [
				[
					'description' => esc_html__( 'Square root', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'sqrt',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Num raised to the power of 3', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'pow(3)',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Format a number with grouped thousands (English notation)', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'number_format(2)',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Format a number with grouped thousands (French notation)', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => "number_format(2, ',', ' ')",
							],
						],
					],
				],
			],

			'json' => [
				[
					'description' => esc_html__( 'Decode and dump', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'json_decode',
							],
							[
								'type' => '|',
								'name' => 'dump',
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Cycle the JSON as array and separate each element with a comma', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|',
								'name' => 'json_decode(true)',
							],
						],
					],
					'wrap' => [
						[
							'data' => [
								'type' => 'for',
								'args' => [
									'value',
									[
										'type' => 'template',
										'data' => [
											'{get:value}',
										],
									],
								],
								'keyargs' => [
									'sep' => ', ',
								],
							],
							'args_position' => 1,
						],
					],

				],
			],

			'boolean' => [
				[
					'description' => esc_html__( "Shows 'The condition is true' if the condition is true, otherwise returns 'The condition is false'", 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'if',
								'args' => [
									esc_html__( 'The condition is true', 'dynamic-shortcodes' ),
									esc_html__( 'The condition is false', 'dynamic-shortcodes' ),
								],
							],
							'args_position' => 0,
						],
					],
				],
				[
					'description' => esc_html__( "Shows 'The condition is true' if the condition is true, otherwise doesn\'t return anything'", 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'if',
								'args' => [
									esc_html__( 'The condition is true', 'dynamic-shortcodes' ),
								],
							],
							'args_position' => 0,
						],
					],
				],
			],

			'timestamp' => [
				[
					'description' => esc_html__( 'Convert to date with format d/m/Y H:i:s', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|-',
								'name' => "date('d/m/Y H:i:s')",
							],
						],
					],
				],
				[
					'description' => esc_html__( 'Convert to date with format d/m/Y', 'dynamic-shortcodes' ),
					'merge' => [
						'filters' => [
							[
								'type' => '|-',
								'name' => "date('d/m/Y')",
							],
						],
					],
				],
			],

			'empty' => [
				[
					'description' => esc_html__( 'Set a fallback when the result is empty', 'dynamic-shortcodes' ),
					'merge' => [
						'fallback' => esc_html__( 'Ehi! This is a fallback', 'dynamic-shortcodes' ),
					],
				],
				[
					'description' => esc_html__( "Shows 'The result is NOT empty' if the condition is true, otherwise returns 'The result is empty'. In this case the string is obviously empty.", 'dynamic-shortcodes' ),
					'wrap' => [
						[
							'data' => [
								'type' => 'if',
								'args' => [
									esc_html__( 'The result is NOT empty', 'dynamic-shortcodes' ),
									esc_html__( 'The result is empty', 'dynamic-shortcodes' ),
								],
							],
							'args_position' => 0,
						],
					],
				],
			],
		];

		return $examples[ $type ] ?? [];
	}

	private function process_wraps( $original, $wraps ) {
		$processed = $original;
		foreach ( $wraps as $wrap ) {
			$processed = $this->add_wrap( $processed, $wrap );
		}
		return $processed;
	}

	protected function add_wrap( $original, $wrap ) {
		$parent                   = $wrap['data'];
		$position                 = $wrap['args_position'] ?? '';
		$position_for_original    = $wrap['position_for_original'] ?? 'args';
		$args_without_type        = $wrap['args_without_type'] ?? false;
		$function_for_placeholder = $wrap['function_for_placeholder'] ?? '';

		if ( $args_without_type ) {
			$original = $original['args'][0];
		} else {
			$original = [
				'type' => 'shortcode',
				'data' => $original,
			];
		}

		if ( ! empty( $function_for_placeholder ) ) {
			$name                         = call_user_func( [ $this, $function_for_placeholder ], $original );
			$parent['args'][0]['data'][0] = str_replace( '*placeholder-field-name*', $name, $parent['args'][0]['data'][0] );
		}

		if ( 'args' === $position_for_original ) {
			if ( isset( $position ) ) {
				array_splice( $parent['args'], $position, 0, [ $original ] );
			} else {
				$parent['args'][] = $original;
			}
		} elseif ( ! empty( $position_for_original['keyargs'] ) ) {
			$keyarg                       = $position_for_original['keyargs'];
			$parent['keyargs'][ $keyarg ] = $original;
		}

		return $parent;
	}

	private function add_examples( $type, $original ) {
		$additions = $this->get_examples( $type );
		$examples  = [];

		foreach ( $additions as $addition ) {
			$example = [
				'merge' => $addition['merge'] ?? null,
				'wrap' => $addition['wrap'] ?? null,
				'description' => $addition['description'],
			];

			if ( ! empty( $addition['merge'] ) ) {
				$original_with_addition = array_merge( $original, $addition['merge'] );

				if ( isset( $original['filters'] ) && isset( $addition['merge']['filters'] ) ) {
					$original_with_addition['filters'] = array_merge( $original['filters'], $addition['merge']['filters'] );
				}

				$example['parameters'] = $original_with_addition;

			} else {
				$original_with_addition = $original;
			}

			if ( ! empty( $addition['wrap'] ) ) {
				$original_with_parent  = $this->process_wraps( $original_with_addition, $addition['wrap'] );
				$example['parameters'] = $original_with_parent;
			}

			$example['code'] = Composer::compose( [ $example['parameters'] ] );
			$examples[]      = $example;
		}

		return $examples;
	}

	/**
	 * Retrieve the first subfield name from an ACF Repeater
	 *
	 * @param string $repeater_name
	 * @return string|null
	 */
	private function get_first_subfield_name( $repeater_name ) {
		$first_subfield_name = null;

		if ( have_rows( $repeater_name ) ) {
			while ( have_rows( $repeater_name ) ) {
				the_row();

				$subfields = get_row( true );

				if ( ! empty( $subfields ) ) {
					$first_subfield_name = key( $subfields );
					break;
				}
			}
		}
		reset_rows();

		return $first_subfield_name;
	}
}
