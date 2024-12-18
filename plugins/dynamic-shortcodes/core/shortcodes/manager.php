<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

use DynamicShortcodes\Core\Settings\Manager as SettingsManager;
use DynamicShortcodes\Core\Shortcodes\Type as ShortcodeType;
use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\Composer;


class Manager {
	/**
	 * @var array<string, array{string, mixed}>?
	 */
	private $shortcode_types = false;

	/**
	 * plugin dependencies as key and wether they are active as value.
	 * @var array<string, bool>
	 */
	private $plugin_depends = [];
	private $default_local_env;
	private $default_interpreter_env;

	/**
	 * @var array<int|string,string> list of all errors produced by shortcodes in the page.
	 */
	public $errors = [];

	const TYPE_CLASSES = [
		Types\Post::class,
		Types\Identity::class,
		Types\Power::class,
		Types\Archive::class,
		Types\Args::class,
		Types\Arithmetic::class,
		Types\ArrayShortcode::class,
		Types\JetEngine::class,
		Types\Vars::class,
		Types\Media::class,
		Types\Loops::class,
		Types\Metabox::class,
		Types\Option::class,
		Types\Date::class,
		Types\Acf::class,
		Types\Pods::class,
		Types\Toolset::class,
		Types\User::class,
		Types\Author::class,
		Types\QueryVar::class,
		Types\Parameter::class,
		Types\Term::class,
		Types\Server::class,
		Types\WooCommerce::class,
		Types\Conditions::class,
		Types\Cookie::class,
		Types\Call::class,
		Types\Demo::class,
		Types\Cache::class,
		Types\Query::class,
		Types\ElementorTemplate::class,
		Types\WpShortcode::class,
		Types\Api::class,
	];

	public function add_error( $msg, $code = false ) {
		do_action( 'qm/error', "Dynamic Shortcodes: $msg in\n$code" );
		if ( $code ) {
			$msg .= esc_html__( ' In the context of: ', 'dynamic-shortcodes' );
			$rows = min( substr_count( $code, "\n" ), 8 );
			$msg .= '<textarea style="color: black;" rows=' . $rows . ' readonly>' . $code . '</textarea>';
		}
		$this->errors[] = $msg;
	}

	public function __construct() {
		$this->default_local_env = new LocalEnv();
		$prefix                  = SettingsManager::OPTIONS_PREFIX;
		if ( ! get_option( $prefix . 'hide_errors_in_frontend', false ) ) {
			add_action( 'wp_footer', [ $this, 'display_errors' ], 1000 );
		}
	}

	private function get_default_interpreter_env() {
		if ( ! isset( $this->default_interpreter_env ) ) {
			$is_preview = false;
			if ( class_exists( 'Elementor\Plugin' ) ) {
				$is_preview = \Elementor\Plugin::$instance->preview->is_preview_mode() ||
				\Elementor\Plugin::$instance->editor->is_edit_mode() ||
				(
					Helpers::current_user_is_at_least_contributor() &&
					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
					( $_REQUEST['action'] ?? '' ) === 'elementor_ajax'
				);
			}
			$this->default_interpreter_env = [
				'is_preview_mode' => $is_preview,
				'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'catch_errors' => true,
			];
		}
		return $this->default_interpreter_env;
	}

	private function init_types() {
		$this->shortcode_types = [];
		foreach ( self::TYPE_CLASSES as $class ) {
			$this->register( $class );
		}
		do_action( 'dynamic-shortcodes/types/register', $this );
	}

	/**
	 * Prints a list of all shortcodes errors in the footer. Only for contributors
	 * or higher. Useful for those shortcodes that don't appear in the page, like
	 * some dynamic tags.
	 */
	public function display_errors() {
		if ( ! current_user_can( 'edit_posts' ) || empty( $this->errors ) ) {
			return;
		}
		$msg    = esc_html__( 'Dynamic Shortcodes errors have been detected! This message is not visible to site visitors.', 'dynamic-shortcodes' );
		$errors = '';
		foreach ( $this->errors as $err ) {
			$errors .= '<li>' . $err;
		}
		//phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
		echo <<<EOD
<details style="background-color: #F8D7DA; color: #721C24; padding: 10px;">
   <summary><strong>$msg</strong></summary>
   <ul>
	  $errors
   </ul>
</details>
EOD;
	}

	private function set_plugin_dependency_status( $plugin ) {
		switch ( $plugin ) {
			case 'acf':
				$this->plugin_depends['acf'] = class_exists( 'ACF' );
				break;
			case 'acf-pro':
				$this->plugin_depends['acf-pro'] = class_exists( 'ACF' ) && defined( 'ACF_PRO' );
				break;
			case 'breakdance':
				$this->plugin_depends['breakdance'] = true;
				break;
			case 'elementor':
				$this->plugin_depends['elementor'] = class_exists( 'Elementor\Plugin' );
				break;
			case 'elementor-pro':
				$this->plugin_depends['elementor-pro'] = class_exists( 'ElementorPro\Plugin' );
				break;
			case 'jet-engine':
				$this->plugin_depends['jet-engine'] = class_exists( 'Jet_Engine' );
				break;
			case 'metabox':
				$this->plugin_depends['metabox'] = class_exists( 'RWMB_Core' );
				break;
			case 'oxygen':
				$this->plugin_depends['oxygen'] = true;
				break;
			case 'pods':
				$this->plugin_depends['pods'] = class_exists( 'Pods' );
				break;
			case 'toolset':
				$this->plugin_depends['toolset'] = defined( 'TYPES_VERSION' );
				break;
			case 'woocommerce':
				$this->plugin_depends['woocommerce'] = class_exists( 'woocommerce' );
				break;
			case 'wpml':
				$this->plugin_depends['wpml'] = class_exists( 'SitePress' );
				break;
			default:
				throw Error( 'bad plugin name' );
		}
	}

	public function check_plugin_dependency( $plugin ) {
		if ( ! isset( $this->plugin_depends[ $plugin ] ) ) {
			$this->set_plugin_dependency_status( $plugin );
		}
		return $this->plugin_depends[ $plugin ];
	}

	public function get_shortcode_type_class( $type ) {
		if ( $this->shortcode_types === false ) {
			$this->init_types();
		}
		return $this->shortcode_types[ $type ] ?? [ false, null ];
	}

	public function get_shortcode_types_ids() {
		if ( $this->shortcode_types === false ) {
			$this->init_types();
		}
		return array_keys( $this->shortcode_types );
	}

	/**
	 * @param string $class
	 * @param mixed $context will be passed to the class at instatiation time
	 * @return void
	 */
	public function register( $sh_class, $context = null ) {
		if ( $this->shortcode_types === false ) {
			throw new \Error( 'register method called too early, register with the dynamic-shortcodes/types/register action' );
		}
		$types = $sh_class::get_shortcode_types( $context );
		foreach ( $types as $type ) {
			$this->shortcode_types[ $type ] = [ $sh_class, $context ];
		}
	}

	public function register_callback( $type, $callback ) {
		$this->register(
			Types\Callback::class,
			[
				'type' => $type,
				'callback' => $callback,
			]
		);
	}

	/**
	 * Evalaute all shortcodes present in the string and return the result of
	 * the last one. There must be at least one. Throws ParseError in case of
	 * syntax errors and also EvaluationError in case of evaluation errors.
	 *
	 * @param string $str
	 * @return mixed
	 */
	public function evaluate_and_return_value( string $str, $interpreter_env = null, $local_env = null ) {
		if ( $local_env === null ) {
			$local_env = $this->default_local_env;
		}
		if ( $interpreter_env === null ) {
			$interpreter_env = $this->get_default_interpreter_env();
		}
		$ast = $this->parse_shortcodes( $str, 0, false );
		$ast = array_filter(
			$ast,
			function ( $value ) {
				return ! is_string( $value );
			}
		);
		if ( count( $ast ) === 0 ) {
			throw new ParseError( '', 0 );
		}
		$interpreter = new UnitInterpreter( $this, $local_env );
		$local_env->open_scope();
		try {
			$last = array_pop( $ast );
			foreach ( $ast as $sh ) {
				$interpreter->evaluate_single_shortcode( $sh, $interpreter_env );
			}
			return $interpreter->evaluate_single_shortcode( $last, $interpreter_env );
		} finally {
			$local_env->close_scope();
		}
	}

	public function get_marked_string_and_expanded_replacements( $str ) {
		$ast             = $this->parse_shortcodes( $str );
		$local_env       = $this->default_local_env;
		$interpreter_env = $this->get_default_interpreter_env();
		$interpreter     = new UnitInterpreter( $this, $local_env );
		$local_env->open_scope();
		try {
			return $interpreter->get_marked_string_and_expanded_replacements( $ast, $interpreter_env );
		} finally {
			$local_env->close_scope();
		}
	}

	public function parse_shortcodes( $str, $start_pos = 0, $ignore_syntax_error = true ) {
		$parser = new StringWithShortcodesParser( $this->get_shortcode_types_ids() );
		return $parser->parse( $str, $start_pos, $ignore_syntax_error );
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function expand_shortcodes(
		string $str,
		array $interpreter_env = [],
		$local_env = null,
		$bindings = []
	) {
		$ast = $this->parse_shortcodes( $str );
		if ( $local_env === null ) {
			$local_env = $this->default_local_env;
		}
		$interpreter_env = $interpreter_env + $this->get_default_interpreter_env();
		$interpreter     = new UnitInterpreter( $this, $local_env );
		$local_env->open_scope();
		foreach ( $bindings as $name => $value ) {
			$local_env->define_var( $name, $value );
		}
		try {
			return $interpreter->expand( $ast, $interpreter_env );
		} finally {
			$local_env->close_scope();
		}
	}
}
