<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

abstract class BaseShortcode {
	/**
	 * @var UnitInterpreter
	 */
	protected $unit_interpreter;
	/**
	 * @var array<mixed>
	 */
	protected $args;
	/**
	 * @var array<string,mixed>
	 */
	protected $keyargs;
	/**
	 * @var array<string,mixed>|null
	 */
	protected $normalized_keyargs;
	/**
	 * @var array<string,mixed>
	 */
	protected $interpreter_env;
	/**
	 * @var string
	 */
	protected $type;
	/**
	 * @var string the raw shortcode string
	 */
	protected $str;
	/**
	 * @var bool whether the finilal result of this shortcode type should be sanitized
	 */
	private $should_sanitize = true;

	/**
	 * @var bool true if raw! and we are in power shortcode.
	 */
	private $want_raw = false;

	/**
	 * @var bool true
	 */
	private $want_string;

	/**
	 * @var mixed
	 */
	protected $context;

	public function __construct( $shortcode, $unit_interpreter, $interpreter_env, $context, $want_string ) {
		$this->unit_interpreter = $unit_interpreter;
		$this->args             = $shortcode['args'];
		$this->keyargs          = $shortcode['keyargs'];
		$this->interpreter_env  = $interpreter_env;
		$this->type             = $shortcode['type'];
		$this->str              = $shortcode['str'];
		$this->context          = $context;
		$this->want_string      = $want_string;
	}

	protected function get_args_count() {
		return count( $this->args );
	}

	public static function make_type_err_message( $actual, $expected, $key ) {
		// translators: 1 and 3 are PHP types, 2 specifies an argument key or index
		$msg = esc_html__( 'expected %1$s for argument %2$s, instead got %3$s', 'dynamic-shortcodes' );
		return sprintf( $msg, $expected, $key, $actual );
	}

	private function check_type( $val, $type, $key ) {
		if ( ! ( 'is_' . $type )( $val ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( self::make_type_err_message( gettype( $val ), $type, $key ) );
		}
	}

	protected function shift( $n = 1 ) {
		$this->args = array_slice( $this->args, $n );
	}

	protected function get_arg_shortcode_type( $index ) {
		$arg = $this->args[ $index ];
		if ( is_array( $arg ) && ( $arg['type'] ?? '' ) === 'shortcode' ) {
			return $arg['value']['type'];
		}
	}

	/**
	 * @param int $index
	 * @param ?string $type if not null is a type that will be check before returning
	 * @return mixed
	 */
	protected function get_arg( $index, $type = null ) {
		$val = $this->unit_interpreter->evaluate_value( $this->args[ $index ], $this->interpreter_env );
		if ( $type !== null ) {
			$this->check_type( $val, $type, $index );
		}
		return $val;
	}

	protected function get_arg_as_string( $index, $sanitize_literals = true ) {
		return $this->unit_interpreter->evaluate_value( $this->args[ $index ], $this->interpreter_env, true, $sanitize_literals );
	}

	public static function make_priv_err_message( $type ) {
		// translators: %s is the name of the shortcode.
		$msg = esc_html__( 'The %s shortcode can only be used within a Power Shortcode', 'dynamic-shortcodes' );
		return sprintf( $msg, $type );
	}

	public static function make_ensure_plugin_err_message( $type, $plugin, $action ) {
		if ( $action === false ) {
			// translators: %$1s is a shortcode type. %$2s a plugin name.
			$msg = esc_html__( 'The %1$s Dynamic Shortcode requires the plugin %2$s', 'dynamic-shortcodes' );
			return sprintf( $msg, $type, $plugin );
		}
		// translators: %$1s is a sentence describing an action. %$2s is a shortcode type. %$3s is a plugin name.
		$msg = esc_html__( '%1$s in a %2$s shortcode requires the plugin %$3s', 'dynamic-shortcodes' );
		return sprintf( $msg, $action, $type, $plugin );
	}

	public static function make_ensure_priv_err_message( $type, $action ) {
		if ( $action === false ) {
			// translators: %$1s is a shortcode type.
			$msg = esc_html__( 'The %1$s Dynamic Shortcode can only be used within a Power Shortcode', 'dynamic-shortcodes' );
			return sprintf( $msg, $type );
		}
		// translators: %$1s is a sentence describing an action. %$2s is a shortcode type.
		$msg = esc_html__( '%1$s in a %2$s shortcode can only be done within a Power Shortcode', 'dynamic-shortcodes' );
		return sprintf( $msg, $action, $type );
	}

	protected function get_literal_arg( $index ) {
		return $this->args[ $index ];
	}

	protected function is_arg_an_identifier( $index ) {
		return is_string( $this->args[ $index ] );
	}

	public static function make_arity_err_message( $type, $min_arity, $max_arity ) {
		if ( $max_arity === false ) {
			return sprintf( 'The shortcode %s accepts at least %d arguments', $type, $min_arity );
		} else {
			return sprintf( 'The shortcode %s accepts between %d and %d arguments', $type, $min_arity, $max_arity );
		}
	}

	public static function make_keyarg_err_message( $keyarg, $type ) {
		return sprintf( 'The keyarg %s is not allowed in shortcode %s ', $keyarg, $type );
	}

	public static function make_keyarg_conflict_err_message( $key1, $key2, $type ) {
		return sprintf( 'The keyargs %s and %s in shortcode %s are not compatible', $key1, $key2, $type );
	}

	public static function make_keyarg_requirment_err_message( $key, $required_key, $type ) {
		return sprintf( 'In shortcode %s, the keyarg %s is also required when the keyarg %s is selected. ', $type, $required_key, $key );
	}

	private static function is_global_keyarg( $key ) {
		$globals = [
			'raw!' => true,
			'expand!' => true,
		];
		return isset( $globals[ $key ] );
	}

	/**
	 * special keyargs ends with !. They are useful for global keyargs and for
	 * those shortcodes that allow arbitrary keyargs but still want to handle
	 * some configuration at the same time.
	 */
	protected static function is_keyarg_special( $s ) {
		return substr( $s, -1 ) === '!';
	}

	/**
	 * Lowercases all the keys and takes care of aliases, so that only the
	 * canonical key names remain. Throws an error if keys not allowed are
	 * found.
	 */
	private function normalize_keyargs( $allowed_keyargs, $specials_only ) {
		$lc_keyargs = array_change_key_case( $this->keyargs );
		if ( $specials_only ) {
			$lc_keyargs = array_filter(
				$lc_keyargs,
				function ( $i ) {
					return self::is_keyarg_special( $i );
				},
				ARRAY_FILTER_USE_KEY
			);
		}
		$normalized_keyargs = [];
		foreach ( $allowed_keyargs as $ak => $aliases ) {
			if ( isset( $lc_keyargs[ $ak ] ) ) {
				$normalized_keyargs[ $ak ] = $lc_keyargs[ $ak ];
				unset( $lc_keyargs[ $ak ] );
			}
			foreach ( $aliases as $alias ) {
				if ( isset( $lc_keyargs[ $alias ] ) ) {
					$normalized_keyargs[ $ak ] = $lc_keyargs[ $alias ];
					unset( $lc_keyargs[ $alias ] );
				}
			}
		}
		// the remaining keys are either global keyargs or not allowed.
		foreach ( $lc_keyargs as $key => $val ) {
			if ( $this->is_global_keyarg( $key ) ) {
				$normalized_keyargs[ $key ] = $val;
			} else {
				$msg = self::make_keyarg_err_message( array_keys( $lc_keyargs )[0], $this->type );
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( $msg );
			}
		}
		return $normalized_keyargs;
	}

	/**
	 * @param array<string,array<string>>|false $allowed_keyargs array of
	 * allowed keyargs with their aliases, false mean that everything is
	 * allowed.
	 * @return void
	 */
	protected function init_keyargs( $allowed_keyargs, $conflicts = [], $requirments = [], $specials_only = false ) {
		if ( $this->normalized_keyargs !== null ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( 'keyargs initialized twice' );
		}
		$this->normalized_keyargs = $this->normalize_keyargs( $allowed_keyargs, $specials_only );
		foreach ( $conflicts as $conflict ) {
			$hits = [];
			foreach ( $conflict as $key ) {
				if ( isset( $this->normalized_keyargs[ $key ] ) ) {
					$hits[] = $key;
				}
				if ( count( $hits ) >= 2 ) {
					$msg = self::make_keyarg_conflict_err_message( $hits[0], $hits[1], $this->type );
					//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new EvaluationError( $msg );
				}
			}
		}
		foreach ( $requirments as $key => $required_keys ) {
			if ( isset( $this->normalized_keyargs[ $key ] ) ) {
				foreach ( $required_keys as $rkey ) {
					if ( ! isset( $this->normalized_keyargs[ $rkey ] ) ) {
						$msg = self::make_keyarg_requirment_err_message( $key, $rkey, $this->type );
						//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
						throw new EvaluationError( $msg );
					}
				}
			}
		}
		if ( $this->get_bool_keyarg( 'raw!' ) ) {
			$this->ensure_all_privileges( 'using raw! keyarg' );
			$this->want_raw = true;
		}
	}

	protected function arity_check( $min, $max = false ) {
		$na = count( $this->args );
		if ( $na < $min || ( $max !== false && $na > $max ) ) {
			$msg = self::make_arity_err_message( $this->type, $min, $max );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new EvaluationError( $msg );
		}
	}

	final public function has_all_privileges() {
		return $this->interpreter_env['privileges'] === 'all';
	}

	final public function ensure_all_privileges( $action = false ) {
		if ( $this->interpreter_env['privileges'] !== 'all' ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( self::make_ensure_priv_err_message( $this->type, $action ) );
		}
	}

	final public function ensure_plugin_dependencies( $plugins, $action = false ) {
		foreach ( $plugins as $plugin ) {
			if ( ! $this->unit_interpreter->manager->check_plugin_dependency( $plugin ) ) {
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new EvaluationError( self::make_ensure_plugin_err_message( $this->type, $plugin, $action ) );
			}
		}
	}

	public function evaluation_error( $msg ) {
		//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new EvaluationError( $msg . " ({$this->type} Shortcode)" );
	}

	protected function want_string() {
		return $this->want_string;
	}

	/**
	 * return wether the output of this shortcode should be escaped.
	 */
	public function should_sanitize() {
		return $this->should_sanitize && ! $this->want_raw;
	}

	protected function should_not_sanitize() {
		$this->should_sanitize = false;
	}

	/**
	 * Returns the key found or false.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function has_keyarg( $key ) {
		if ( is_null( $this->normalized_keyargs ) ) {
			throw new \Exception( 'initialized keyargs required' );
		}
		return isset( $this->normalized_keyargs[ $key ] );
	}

	/**
	 * Before using this you must ensure that the key exists with has_keyarg.
	 *
	 * @param string $key
	 * @param ?string $type if not null is a type that will be check before returning
	 * @return mixed|null
	 */
	protected function get_keyarg( $key, $type = null ) {
		if ( is_null( $this->normalized_keyargs ) ) {
			throw new \Exception( 'initialized keyargs required' );
		}
		$val = $this->normalized_keyargs[ $key ];
		if ( $val !== true ) {
			// if val is true we have a key without a value specified.
			$val = $this->unit_interpreter->evaluate_value( $this->normalized_keyargs[ $key ], $this->interpreter_env );
		}
		if ( $type !== null ) {
			$this->check_type( $val, $type, $key );
		}
		return $val;
	}

	/**
	 * Returns the keyvalue if found or the default value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return string|false
	 */
	protected function get_keyarg_default( $key, $default_value, $type = null ) {
		if ( ! $this->has_keyarg( $key ) ) {
			return $default_value;
		}
		return $this->get_keyarg( $key, $type );
	}

	protected function get_keyarg_as_string( $key, $default_value, $sanitize_literals = true ) {
		if ( ! $this->has_keyarg( $key ) ) {
			return $default_value;
		}
		$val = $this->normalized_keyargs[ $key ];
		if ( $val === true ) {
			// if val is true we have a key without a value specified.
			return $default_value;
		}
		return $this->unit_interpreter->evaluate_value( $val, $this->interpreter_env, true, $sanitize_literals );
	}

	/**
	 * @param string $key
	 * @param bool $default
	 * @return bool
	 */
	public function get_bool_keyarg( $key, $default_value = false ) {
		if ( is_null( $this->normalized_keyargs ) ) {
			throw new \Exception( 'initialized keyargs required' );
		}
		$options = &$this->normalized_keyargs;
		if ( ! isset( $options[ $key ] ) ) {
			return $default_value;
		}
		$val = $options[ $key ];
		if ( $val === true ) {
			return true;
		}
		if ( is_string( $val ) ) {
			if ( $val === 'false' || $val === 'off' || $val === 'no' ) {
				return false;
			}
			return true;
		}
		$val = $this->unit_interpreter->evaluate_value( $val, $this->interpreter_env );
		return (bool) $val;
	}

	/**
	 * Given an array of post ids map them to a string given a rendering
	 * callback.
	 */
	public static function loop_ids_with_callback(
		$post_ids,
		$sep,
		$render_callback,
		$type
	) {
		global $post;
		$local_env     = $type->unit_interpreter->local_env;
		$original_post = $post;
		$buf           = [];
		foreach ( $post_ids as $post_id ) {
			//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = get_post( $post_id );
			if ( $post === null ) {
				$type->evaluation_error( esc_html__( 'One of the posts was not found', 'dynamic-shortcodes' ) );
			}
			$post_type_object = get_post_type_object( $post->post_type );
			if ( post_password_required( $post ) ) {
				continue;
			}
			if ( ! ( is_post_publicly_viewable( $post ) || $type->has_all_privileges() ) ) {
				$msg = esc_html__( 'Posts that are not public can only be looped inside a Power Shortcode', 'dynamic-shortcodes' );
				\DynamicShortcodes\Plugin::instance()->shortcodes_manager->add_error( $msg, $type->str );
				continue;
			}
			setup_postdata( $post );
			$local_env->open_scope();
			try {
				$buf[] = $render_callback();
			} finally {
				$local_env->close_scope();
				wp_reset_postdata();
			}
		}
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $original_post;
		return implode( $sep, $buf );
	}

	protected function add_error_message( $msg ) {
		$this->unit_interpreter->manager->add_error( $msg, $this->str );
	}

	/**
	 * @return array<string> accepted shortcode types
	 */
	abstract public static function get_shortcode_types( $context );

	/**
	 * Returns an array with the evaluate shortcode on index 0 and the filtered
	 * filters on index 1.
	 *
	 * @return mixed
	 */
	abstract public function evaluate();
}
