<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

use DynamicShortcodes\Core\Shortcodes\Type as ShortcodeType;

class UnitInterpreter {
	/**
	 * @var Manager
	 */
	public $manager;

	const METHOD_PRIV_ERROR   = 'methods can only be called within Power Shortcodes';
	const PROPERTY_PRIV_ERROR = 'properties can only be accessed within Power Shortcodes';
	const REGULAR_USER_ERROR  = '[Dynamic Shortcodes Error]';

	/**
	 * @var LocalEnv
	 */
	public $local_env;

	public function __construct( $manager, $local_env ) {
		$this->manager   = $manager;
		$this->local_env = $local_env;
	}

	const FILTERS_WHITELIST = [
		'absint' => true,
		'array_keys' => true,
		'array_slice' => true,
		'array_sum' => true,
		'ceil' => true,
		'count' => true,
		'count_user_posts' => true,
		'current_time' => true,
		'date' => true,
		'date_i18n' => true,
		'do_shortcode' => true,
		'empty' => true,
		'end' => true,
		'esc_attr' => true,
		'esc_html' => true,
		'esc_js' => true,
		'esc_textarea' => true,
		'esc_url' => true,
		'esc_url_raw' => true,
		'explode' => true,
		'filter_var' => true,
		'floatval' => true,
		'floor' => true,
		'in_array' => true,
		'get_avatar' => true,
		'get_avatar_url' => true,
		'get_cat_ID' => true,
		'get_cat_name' => true,
		'get_date_from_gmt' => true,
		'get_permalink' => true,
		'get_post_format' => true,
		'get_post_status' => true,
		'get_post_type' => true,
		'get_term_link' => true,
		'get_the_author' => true,
		'get_the_content' => true,
		'get_the_date' => true,
		'get_the_excerpt' => true,
		'get_the_ID' => true,
		'get_the_permalink' => true,
		'get_the_post_thumbnail' => true,
		'get_the_time' => true,
		'get_the_title' => true,
		'htmlentities2' => true,
		'implode' => true,
		'intval' => true,
		'json_decode' => true,
		'json_encode' => true,
		'max' => true,
		'microtime' => true,
		'min' => true,
		'nl2br' => true,
		'number_format' => true,
		'pow' => true,
		'rand' => true,
		'reset' => true,
		'round' => true,
		'rtrim' => true,
		'strsub' => true,
		'sanitize_email' => true,
		'sanitize_file_name' => true,
		'sanitize_html_class' => true,
		'sanitize_key' => true,
		'sanitize_mime_type' => true,
		'sanitize_option' => true,
		'sanitize_text_field' => true,
		'sanitize_title' => true,
		'sanitize_user' => true,
		'single_cat_title' => true,
		'single_tag_title' => true,
		'size_format' => true,
		'sqrt' => true,
		'str_replace' => true,
		'str_shuffle' => true,
		'stripslashes' => true,
		'strlen' => true,
		'strpost' => true,
		'strrev' => true,
		'strtolower' => true,
		'strtotime' => true,
		'strtoupper' => true,
		'strtr' => true,
		'substr' => true,
		'the_ID' => true,
		'the_permalink' => true,
		'time' => true,
		'trim' => true,
		'is_int' => true,
		'ucfirst' => true,
		'ucwords' => true,
		'uniqid' => true,
		'urlencode' => true,
		'wordwrap' => true,
		'wp_count_posts' => true,
		'wp_filter_kses' => true,
		'wp_filter_nohtml_kses' => true,
		'wp_filter_post_kses' => true,
		'wp_get_attachment_image' => true,
		'wp_get_attachment_image_src' => true,
		'wp_get_attachment_link' => true,
		'wp_get_attachment_metadata' => true,
		'wp_get_attachment_thumb_file' => true,
		'wp_get_attachment_thumb_url' => true,
		'wp_get_attachment_url' => true,
		'wp_kses' => true,
		'wp_kses_attr' => true,
		'wp_kses_post' => true,
		'wp_kses_post_deep' => true,
		'wp_list_pluck' => true,
		'wp_logout_url' => true,
		'wp_trim_words' => true,
		'wpautop' => true,
	];

	public function apply_shortcode_method( $obj, $name, $args, $privileges ) {
		if ( $privileges !== 'all' ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( self::METHOD_PRIV_ERROR );
		}
		return Helpers::call_catch_errors(
			'filters method',
			function () use ( $obj, $name, $args ) {
				return $obj->$name( ...$args );
			}
		);
	}

	private function filter_dump( $val, $errkey, $privileges ) {
		$admin     = current_user_can( 'manage_options' );
		$objnopriv = is_object( $val ) && $privileges !== 'all';
		if ( ! $admin && $objnopriv ) {
			$msg = esc_html__( 'If not logged in as admin the dump of objects is only allowed inside Power Shortcodes', 'dynamic-shortcodes' );
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PermissionsError( $msg );
		}
		ob_start();
		if ( $admin && $objnopriv ) {
			echo esc_html__( "Notice: Dumping an object outside a Power Shortcode, this will result in an error to non admin users.\n", 'dynamic-shortcodes' );
		}
		//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		var_export( $val );
		$dump = ob_get_clean();
		if ( $errkey !== false ) {
			$this->manager->add_error( "dump to error (dumperr) $errkey", $dump );
			return $val;
		}
		return $dump;
	}

	/**
	 * Returns the WPML translated object ID (post_type or term) or original if missing
	 *
	 * @param int|string $object_id The ID/s of the objects to check and return
	 * @return string
	 */
	public static function wpml_translate_object_id( $object_id ) {
		$current_language = apply_filters( 'wpml_current_language', null );
		return apply_filters( 'wpml_object_id', intval( $object_id ), get_post_type( $object_id ), true, $current_language );
	}

	public function get_builtin_filters( $privileges ) {
		return [
			'dump' => function ( $val ) use ( $privileges ) {
				return $this->filter_dump( $val, false, $privileges );
			},
			'dumperr' => function ( $val, $errkey = '' ) use ( $privileges ) {
				return $this->filter_dump( $val, $errkey, $privileges );
			},
			'shuffle' => function ( $arr ) {
				$copy = $arr;
				shuffle( $copy );
				return $copy;
			},
			'wpml_translate' => [ $this, 'wpml_translate_object_id' ],
			'wpml' => [ $this, 'wpml_translate_object_id' ],
			'json_encode' => function ( $obj ) use ( $privileges ) {
				if ( is_object( $obj ) && $privileges !== 'all' ) {
					$msg = esc_html__( 'json_encode of objects is only allowed inside Power Shortcodes', 'dynamic-shortcodes' );
					//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new PermissionsError( $msg );
				}
				return wp_json_encode( $obj );
			},
		];
	}

	/**
	 * @param string $filter
	 * @param mixed $args
	 * @param string $privileges
	 * @return mixed
	 */
	public function apply_shortcode_filter( $filter, $args, $privileges ) {
		$builtins = $this->get_builtin_filters( $privileges );
		$builtin  = $builtins[ $filter ] ?? false;
		if ( $builtin ) {
			$filter = $builtin;
		} elseif (
				$privileges !== 'all' &&
				! ( self::FILTERS_WHITELIST[ $filter ] ?? false )
			) {

				// translators: %s is a function name
				$msg = sprintf( esc_html__( 'The filter %s is not whitelisted, it can only be used within a Power Shortcode', 'dynamic-shortcodes' ), $filter );
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new PermissionsError( $msg );
		}
		return Helpers::call_catch_errors(
			'Shortcode filters',
			function () use ( $filter, $args ) {
				return call_user_func_array( $filter, $args );
			}
		);
	}

	private function filter_get( $res, $key_ast, $interpreter_env ) {
		// implement keys like last, last-1, must not work if quoted 'last':
		if (
			is_string( $key_ast ) &&
			preg_match( '/^last(?:-(\d+))?$/', $key_ast, $matches ) &&
			is_countable( $res )
		) {
			$key_ast = count( $res ) - ( 1 + ( $matches[1] ?? 0 ) );
		}
		return Helpers::call_catch_errors(
			'filters Array Access',
			function () use ( $res, $key_ast, $interpreter_env ) {
				return $res[ $this->evaluate_value( $key_ast, $interpreter_env ) ] ?? null;
			}
		);
	}

	/**
	 * @param mixed $res
	 * @param array<mixed> $filters
	 * @param array<string,mixed> $interpreter_env
	 * @return mixed
	 */
	private function apply_shortcode_filters( $res, $filters, $interpreter_env ) {
		foreach ( $filters as $filter ) {
			$type = $filter[1];
			if ( $type === 'get' ) {
				$res = $this->filter_get( $res, $filter[0], $interpreter_env );
				continue;
			}
			if ( $type === 'property' ) {
				if ( $interpreter_env['privileges'] !== 'all' ) {
					//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new PermissionsError( self::PROPERTY_PRIV_ERROR );
				}
				$res = Helpers::call_catch_errors(
					'filters Property Access',
					function () use ( $res, $filter ) {
						return $res->{$filter[0]};
					}
				);
				continue;
			}
			$args = array_map(
				function ( $rawf ) use ( $interpreter_env ) {
					return $this->evaluate_value( $rawf, $interpreter_env );
				},
				$filter[2]
			);
			if ( $type === 'method' ) {
				$res = $this->apply_shortcode_method( $res, $filter[0], $args, $interpreter_env['privileges'] );
				continue;
			}
			if ( $type === 'left' ) {
				array_unshift( $args, $res );
			} elseif ( $type === 'right' ) {
				array_push( $args, $res );
			}
			$res = $this->apply_shortcode_filter( $filter[0], $args, $interpreter_env['privileges'] );
		}
		return $res;
	}

	/**
	 * @param mixed $value
	 * @param array<string,mixed> $interpreter_env
	 * @param $sanitize_literals sanitize literal values (shortcodess are sanitized during evaluation regardless)
	 * @return mixed
	 */
	public function evaluate_value( $value, $interpreter_env, $want_string = false, $sanitize_literals = false ) {
		// there is never a need to sanitize literals in power shortcodes:
		$sanitize_literals = $sanitize_literals && ( $interpreter_env['privileges'] !== 'all' );
		if ( is_string( $value ) ) {
			// no need to sanitize as this is the case of a simple identifier.
			return $value;
		}
		if ( is_numeric( $value ) || is_bool( $value ) ) {
			return $want_string ? (string) $value : $value;
		}
		assert( is_array( $value ) );
		$type = $value['type'];
		if ( $type === 'string-with-shortcodes' ) {
			if ( $sanitize_literals ) {
				return $this->evaluate_shortcodes_in_template_sanitize( $value['value'], $interpreter_env, $sanitize_literals );
			} else {
				return $this->evaluate_shortcodes_in_template( $value['value'], $interpreter_env, $sanitize_literals );
			}
		} elseif ( $type === 'quoted-string' ) {
			$value = $value['value'];
			if ( $sanitize_literals ) {
				$value = self::sanitize( $value );
			}
			return $value;
		}
		return $this->evaluate_shortcode( $value['value'], $want_string, true, $interpreter_env );
	}

	public static function sanitize( $str ) {
		$str = wp_kses_post( $str );
		// Escape shortcodes in shortcodes output:
		return preg_replace( '/{(' . ShortcodeParser::REGEX_TYPE_IDENTIFIER . '):/', '&123;$1:', $str );
	}

	/**
	 * @param mixed $val
	 * @return string
	 */
	public function to_string( $val, $interpreter_env ) {
		if ( $val === null ) {
			return 'Null';
		}
		if ( is_array( $val ) ) {
			$res = 'Array';
			if ( ( $interpreter_env['is_preview_mode'] ?? false ) || Helpers::current_user_is_at_least_contributor() ) {
				$res .= esc_html__( ' (Notice: The result is an Array. You might want to loop over it with the `for` Dynamic Shortcode)', 'dynamic-shortcodes' );
			}
			return $res;
		}
		if ( is_bool( $val ) ) {
			return $val ? 'True' : 'False';
		}
		if ( is_int( $val ) || is_float( $val ) ) {
			$locale = setlocale( LC_NUMERIC, '0' );
			if ( $locale !== false ) {
				setlocale( LC_NUMERIC, 'C' );
			}
			//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			$res = var_export( $val, true );
			if ( false !== $locale ) {
				setlocale( \LC_NUMERIC, $locale );
			}
			return $res;
		}
		if ( is_object( $val ) ) {
			/** @var string $res */
			$res = get_class( $val );
			return $res;
		}
		return $val;
	}

	/**
	 * @param array<mixed> $shortcode
	 * @param bool $want_string
	 * @param bool $nested
	 * @param array<string,mixed> $interpreter_env
	 * @return mixed
	 */
	public function evaluate_shortcode( $shortcode, $want_string, $nested, $interpreter_env ) {
		[ $shortcode_class, $context ] = $this->manager->get_shortcode_type_class( $shortcode['type'] );
		if ( $shortcode_class === false ) {
			throw new EvaluationError( 'found unknow type, this should never happen' );
		}
		if ( ( $interpreter_env['is_preview_mode'] ?? false ) && $shortcode['placeholder'] !== null ) {
			$res = $this->evaluate_value( $shortcode['placeholder'], $interpreter_env );
			if ( ! is_string( $res ) ) {
				$res = $this->to_string( $res, $interpreter_env );
			}
			return self::sanitize( $res );
		}
		$shortcode_interpreter = new $shortcode_class( $shortcode, $this, $interpreter_env, $context, $want_string );
		$res                   = $shortcode_interpreter->evaluate();
		if ( ! $res && isset( $shortcode['fallback'] ) ) {
			$res = $this->evaluate_value( $shortcode['fallback'], $interpreter_env );
		} else {
			$filters = $shortcode['filters'];
			$res     = $this->apply_shortcode_filters( $res, $filters, $interpreter_env );
		}
		if ( $shortcode_interpreter->get_bool_keyarg( 'expand!' ) && is_string( $res ) ) {
			if ( $interpreter_env['privileges'] !== 'all' ) {
				throw new PermissionsError( esc_html__( 'expand! can only be used with full Privileges', 'dynamic-content-for-elementor' ) );
			}
			[ $res, $replacements ] = $this->manager->get_marked_string_and_expanded_replacements( $res );
			$res                    = self::sanitize( $res );
			return strtr( $res, $replacements );
		}
		if ( $want_string ) {
			if ( ! is_string( $res ) ) {
				$res = $this->to_string( $res, $interpreter_env );
			}
			if ( $shortcode_interpreter->should_sanitize() ) {
				$res = self::sanitize( $res );
			}
		}
		return $res;
	}

	public static function format_shortcode_error( $msg ) {
		return '[Dynamic Shortcodes Error: ' . $msg . ']';
	}

	/**
	 * @param array<string|array<mixed>> $ast
	 * @param array<string,mixed> $interpreter_env
	 * @return string
	 */
	private function evaluate_shortcodes_in_template( $ast, $interpreter_env ) {
		$buffer = [];
		foreach ( $ast as $el ) {
			if ( is_string( $el ) ) {
				$buffer[] = $el;
			} else {
				$buffer[] = self::evaluate_shortcode( $el, true, true, $interpreter_env );
			}
		}
		return implode( '', $buffer );
	}

	/**
	 * @param array<string|array<mixed>> $ast
	 * @param array<string,mixed> $interpreter_env
	 * @return string
	 */
	private function evaluate_shortcodes_in_template_sanitize( $ast, $interpreter_env ) {
		$buffer            = [];
		$marked_shortcodes = [];
		$mark_index        = 0;
		foreach ( $ast as $el ) {
			if ( is_string( $el ) ) {
				$buffer[] = $el;
			} else {
				$mark = "__shm_{$mark_index}__";
				++$mark_index;
				$buffer[]                   = $mark;
				$marked_shortcodes[ $mark ] = self::evaluate_shortcode( $el, true, true, $interpreter_env );
			}
		}
		$res = implode( '', $buffer );
		$res = self::sanitize( $res );
		return strtr( $res, $marked_shortcodes );
	}

	/**
	 * This method is only for internal use
	 *
	 * @param array<string|array<mixed>> $ast
	 * @param array<string,mixed> $interpreter_env
	 * @return string
	 */
	public function evaluate_shortcodes( $ast, $interpreter_env ) {
		$buffer = [];
		foreach ( $ast as $el ) {
			if ( is_string( $el ) ) {
				$buffer[] = $el;
			} else {
				try {
					$buffer[] = self::evaluate_shortcode( $el, true, false, $interpreter_env );
				} catch ( EvaluationError $e ) {
					if ( ! ( $interpreter_env['catch_errors'] ?? true ) ) {
						throw $e;
					}
					$msg = $e->getMessage();
					if ( ( $interpreter_env['is_preview_mode'] ?? false ) && ! ( $e instanceof PermissionsError ) ) {
						$msg .= esc_html__( ' - Preview mode is on. Maybe this error is not in the final page? If so, you can use placeholders.', 'dynamic-shortcodes' );
					}
					if ( $interpreter_env['is_preview_mode'] ?? false ) {
						$buffer[] = self::format_shortcode_error( $msg );
					} else {
						$this->manager->add_error( $msg, $el['str'] ?? false );
						if ( Helpers::current_user_is_at_least_contributor() || ( $interpreter_env['debug'] ?? false ) ) {
							$buffer[] = self::format_shortcode_error( $msg );
						} else {
							$buffer[] = '<!-- dsh-error -->';
						}
					}
				}
			}
		}
		return implode( '', $buffer );
	}

	/**
	 * @param array<string|array<mixed>> $ast
	 * @return string
	 */
	public function expand( $ast, $interpreter_env = [] ) {
		$default_interpreter_env = [
			'privileges' => 'none',
		];
		$interpreter_env         = array_merge( $default_interpreter_env, $interpreter_env );
		return $this->evaluate_shortcodes( $ast, $interpreter_env );
	}

	public function get_marked_string_and_expanded_replacements( $ast, $interpreter_env = [] ) {
		$default_interpreter_env = [
			'privileges' => 'none',
		];
		$interpreter_env         = array_merge( $default_interpreter_env, $interpreter_env );
		$buffer                  = [];
		$marked_shortcodes       = [];
		$mark_index              = 0;
		foreach ( $ast as $el ) {
			if ( is_string( $el ) ) {
				$buffer[] = $el;
			} else {
				$mark = "__shm_{$mark_index}__";
				++$mark_index;
				$buffer[]                   = $mark;
				$marked_shortcodes[ $mark ] = $this->evaluate_shortcodes( [ $el ], $interpreter_env );
			}
		}
		$res = implode( '', $buffer );
		return [ $res, $marked_shortcodes ];
	}

	/**
	 * Throws in case of evaluation errors.
	 *
	 * @param array<string|array<mixed>> $ast reprersentation of a single shortcode
	 * @return mixed
	 */
	public function evaluate_single_shortcode( $ast, $interpreter_env = [] ) {
		$default_interpreter_env = [
			'privileges' => 'none',
		];
		$interpreter_env         = array_merge( $default_interpreter_env, $interpreter_env );
		return $this->evaluate_shortcode( $ast, false, true, $interpreter_env );
	}
}
