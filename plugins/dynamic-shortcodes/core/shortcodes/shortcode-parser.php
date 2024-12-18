<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

class ShortcodeParser {
	const REGEX_IDENTIFIER      = '_*[a-zA-Z][a-zA-Z0-9_-]*';
	const REGEX_TYPE_IDENTIFIER = '[a-zA-Z][a-zA-Z0-9_-]*|\+|\/|\-|\*';
	const WHITESPACE_REGEX      = "[\s\u{00A0}]";
	/**
	 * @var string
	 */
	private $str;
	/**
	 * @var int
	 */
	private $current = 0;
	/**
	 * @var array<mixed>
	 */
	private $captures = [];
	/**
	 * @var array<mixed>
	 */
	private $filters = [];
	/**
	 * @var array<mixed>
	 */
	private $arguments = [];
	/**
	 * @var null|string|int|array<mixed>
	 */
	private $fallback;
	/**
	 * @var null|string|int|array<mixed>
	 */
	private $placeholder;
	/**
	 * @var string
	 */
	private $type;
	/**
	 * @var StringWithShortcodesParser
	 */
	private $string_parser;
	/**
	 * @var array<string, mixed>
	 */
	private $keyargs = [];
	/**
	 * @var int
	 */
	private $str_start_pos;
	/**
	 * @var int
	 */
	private $start_pos;
	/**
	 * @param string $str
	 * @param int $str_start_pos the position of $str in the original string
	 * @param int $pos
	 * @param StringWithShortcodesParser $string_parser
	 * @param int $str_start_pos
	 */
	public function __construct( $str, $pos, $string_parser, $str_start_pos ) {
		$this->str           = $str;
		$this->current       = $pos;
		$this->string_parser = $string_parser;
		$this->start_pos     = $pos;
		$this->str_start_pos = $str_start_pos;
	}

	/**
	 * @return string
	 */
	private function peek() {
		if ( $this->is_at_end() ) {
			return 'EOF';
		}
		return $this->str[ $this->current ];
	}

	/**
	 * @param string $c
	 * @return bool
	 */
	private function match( $c ) {
		if ( $this->is_at_end() ) {
			return false;
		}
		if ( $this->str[ $this->current ] === $c ) {
			++$this->current;
			return true;
		}
		return false;
	}

	/**
	 * @param string $regex
	 * @param bool $return_capture
	 * @return ( $return_capture is true ? string|false : bool )
	 */
	private function match_regex( $regex, $return_capture = false ) {
		$matches = [];
		// regex should always be anchored at the beginning of the string
		$regex = $regex[0] . '\G' . substr( $regex, 1 );
		if ( ! preg_match( $regex, $this->str, $matches, 0, $this->current ) ) {
			return false;
		}
		$this->current += strlen( $matches[0] );

		if ( $return_capture ) {
			return $matches[1];
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private function is_at_end() {
		return $this->current >= strlen( $this->str );
	}

	/**
	 * @return string
	 */
	private function advance() {
		return $this->str[ $this->current++ ];
	}

	/**
	 * @return false|array<mixed>
	 */
	private function string_with_shortcodes() {
		$pos = $this->current;
		// match balanced brackets, ignore escaped brackets:
		$res = preg_match( '/\G(\[(?:\\\\.|[^\[\]\\\\]+|\g<1>)*+\])/', $this->str, $matches, 0, $pos );
		if ( ! $res ) {
			return false;
		}
		$str_start_pos  = $this->str_start_pos + $this->current + 1;
		$this->current += strlen( $matches[0] );
		return [
			'type'  => 'string-with-shortcodes',
			'value' => $this->string_parser->parse( substr( $matches[0], 1, -1 ), $str_start_pos ),
		];
	}

	/**
	 * @param string $quote_ch
	 * @return false|string
	 */
	private function quoted_string( $quote_ch ) {
		$esc  = false;
		$qstr = '';
		while ( 1 ) {
			if ( $this->is_at_end() ) {
				return false;
			}
			$c = $this->advance();
			if ( $esc ) {
				$ec = '\\' . $c;
				if ( $quote_ch === '"' ) {
					switch ( $c ) {
						case 'n':
							$ec = "\n";
							break;
						case 't':
							$ec = "\t";
							break;
						case 'r':
							$ec = "\r";
							break;
						default:
							$ec = $c;
							break;
					}
				}
				$qstr .= $ec;
				$esc   = false;
			} elseif ( '\\' === $c ) {
					$esc = true;
			} elseif ( $c === $quote_ch ) {
				return $qstr;
			} else {
				$qstr .= $c;
			}
		}
	}

	/**
	 * @return false|array<string, true| array{0: mixed}>

	 */
	private function keyarg() {
		$key = $this->keyarg_identifier();
		if ( $key === false ) {
			return false;
		}
		if ( ! $this->match( '=' ) ) {
			return [ $key, true ];
		}
		$val = $this->value();
		if ( $val === false ) {
			return false;
		}
		return [ $key, $val ];
	}

	/**
	 * @return string
	 */
	private function fn_keyargs() {
		while ( 1 ) {
			$this->skip_spaces();
			$res = $this->keyarg();
			if ( $res === false ) {
				break;
			}
			$this->keyargs[ $res[0] ] = $res[1];
		}
		if ( empty( $this->keyargs ) ) {
			return 'error';
		}
		if ( empty( $this->filters ) && $this->match( '|' ) ) {
			return 'fn_filter_name';
		}
		if ( $this->match( '?' ) ) {
			return 'fn_fallback';
		}
		if ( $this->match( '}' ) ) {
			return 'done';
		}
		return 'error';
	}

	/**
	 * @return false|string
	 */
	private function identifier() {
		$res = $this->match_regex( '/(' . self::REGEX_IDENTIFIER . ')/', true );
		return $res;
	}

	/**
	 * @return false|string
	 */
	private function keyarg_identifier() {
		$res = $this->match_regex( '/(' . self::REGEX_IDENTIFIER . '!?)/', true );
		return $res;
	}

	/**
	 * @return false|string
	 */
	private function type_identifier() {
		$res = $this->match_regex( '/(' . self::REGEX_TYPE_IDENTIFIER . ')/', true );
		return $res;
	}

	/**
	 * @return void
	 */
	private function skip_spaces() {
		$this->match_regex( '/' . self::WHITESPACE_REGEX . '*/' );
	}

	/**
	 * @return false|string|int|array<mixed>
	 */
	private function value() {
		$id = $this->identifier();
		if ( $id !== false ) {
			return $id;
		}
		$num = $this->match_regex( '/([+-]?[0-9]*\.?[0-9]+)/', true );
		if ( $num !== false ) {
			if ( preg_match( '/^[a-zA-Z]$/', $this->peek() ) ) {
				return false;
			}
			return ( strpos( $num, '.' ) !== false ) ? floatval( $num ) : intval( $num );
		}
		if ( $this->match( '$' ) ) {
			$id = $this->identifier();
			if ( $id === false ) {
				return false;
			}
			return [
				'type' => 'shortcode',
				'value' => [
					'args'           => [ $id ],
					'filters'        => [],
					'keyargs'        => [],
					'fallback'       => null,
					'placeholder'    => null,
					'end'            => $this->current,
					'type'           => 'get',
					'str' => '$' . $id,
				],
			];
		}
		if ( $this->peek() === '{' ) {
			$parser    = new ShortcodeParser( $this->str, $this->current, $this->string_parser, $this->str_start_pos );
			$shortcode = $parser->parse();
			if ( $shortcode === false ) {
				return false;
			}
			$this->current = $shortcode['end'];
			return [
				'type'  => 'shortcode',
				'value' => $shortcode,
			];
		}
		if ( $this->peek() === '[' ) {
			$res = $this->string_with_shortcodes();
			if ( $res === false ) {
				return false;
			}
			return $res;
		}
		$quote_ch = false;
		if ( $this->match( '"' ) ) {
			$quote_ch = '"';
		} elseif ( $this->match( "'" ) ) {
			$quote_ch = "'";
		}
		if ( $quote_ch ) {
			$qstr = $this->quoted_string( $quote_ch );
			if ( $qstr === false ) {
				return false;
			}
			return [
				'type' => 'quoted-string',
				'value' => $qstr,
			];
		}
		return false;
	}

	/**
	 * @return string
	 */
	private function fn_arguments() {
		$value = $this->value();
		if ( $value !== false ) {
			$this->arguments[] = $value;
		}
		if ( $this->match_regex( '/' . self::WHITESPACE_REGEX . '/' ) !== false ) {
			return 'fn_arguments';
		}
		if ( $this->match( '@' ) ) {
			return 'fn_keyargs';
		}
		if ( $this->match( '|' ) ) {
			return 'fn_filter_name';
		}
		if ( $this->match( '?' ) ) {
			return 'fn_fallback';
		}
		if ( $this->match( '}' ) ) {
			return 'done';
		}
		return 'error';
	}

	/**
	 * @return string
	 */
	private function fn_start() {
		if ( ! $this->match( '{' ) ) {
			return 'error';
		}
		$type = $this->type_identifier();
		if ( $type === false ) {
			return 'error';
		}
		$this->type = $type;
		if ( $this->match( ':' ) ) {
			return 'fn_arguments';
		}
		return 'error';
	}

	/**
	 * @return string
	 */
	private function fn_fallback() {
		$this->skip_spaces();
		$placeholder = false;
		$prop        = &$this->fallback;
		if ( $this->match( '?' ) ) {
			$placeholder = true;
			$prop        = &$this->placeholder;
		}
		if ( $prop !== null ) {
			return 'error';
		}
		$value = $this->value();
		if ( $value === false ) {
			return 'error';
		}
		$prop = $value;
		$this->skip_spaces();

		if ( $this->match( '?' ) ) {
			return 'fn_fallback';
		} elseif ( $this->match( '}' ) ) {
			return 'done';
		}
		return 'error';
	}

	/**
	 * @return string
	 */
	private function fn_filter_get() {
		$this->skip_spaces();
		$val = $this->value();
		if ( $val === false ) {
			return 'error';
		}
		$this->filters[] = [ $val, 'get', [] ];
		return 'fn_filter_end';
	}

	/**
	 * @return string
	 */
	private function fn_filter_end() {
		$this->skip_spaces();
		if ( empty( $this->keyargs ) && $this->match( '@' ) ) {
			return 'fn_keyargs';
		}
		if ( $this->match( '|' ) ) {
			return 'fn_filter_name';
		}
		if ( $this->match( '?' ) ) {
			return 'fn_fallback';
		}
		if ( $this->match( '}' ) ) {
			return 'done';
		}
		return 'error';
	}

	/**
	 * @return string
	 */
	private function fn_filter_name() {
		$type = 'left';
		if ( $this->match( '-' ) ) {
			$type = 'right';
		} elseif ( $this->match( '.' ) ) {
			$type = 'property';
		} elseif ( $this->match( '|' ) ) {
			return 'fn_filter_get';
		}
		$this->skip_spaces();
		// The following is the precise regex for a function name.
		$name = $this->match_regex( '/\s*([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\s*/', true );
		if ( $name === false ) {
			return 'error';
		}
		if ( $this->match( '(' ) ) {
			if ( $type === 'property' ) {
				$type = 'method';
			}
			$this->captures[] = $name;
			$this->captures[] = $type;
			return 'fn_filter_arg';
		}
		$this->filters[] = [ $name, $type, [] ]; // no arguments.
		return 'fn_filter_end';
	}
	/**
	 * @return string
	 */
	private function fn_filter_arg() {
		$this->skip_spaces();
		$value = $this->value();
		if ( $value !== false ) {
			$this->captures[] = $value;
			$this->skip_spaces();
			if ( $this->match( ',' ) ) {
				return 'fn_filter_arg';
			}
		}
		if ( $this->match( ')' ) ) {
			$name            = array_shift( $this->captures );
			$type            = array_shift( $this->captures );
			$this->filters[] = [ $name, $type, $this->captures ];
			$this->captures  = [];
			return 'fn_filter_end';
		}
		return 'error';
	}

	/**
	 * @return false|array<mixed>
	 */
	public function parse() {
		$nf = 'fn_start';
		while ( true ) {
			$nf = $this->{$nf}();
			if ( ! $nf ) {
				return false;
			}
			if ( 'error' === $nf ) {
				return false;
			}
			if ( 'done' === $nf ) {
				return [
					'args'           => $this->arguments,
					'filters'        => $this->filters,
					'keyargs'        => $this->keyargs,
					'fallback'       => $this->fallback,
					'placeholder'       => $this->placeholder,
					'end'            => $this->current,
					'type'           => $this->type,
					'str' => substr( $this->str, $this->start_pos, $this->current - $this->start_pos ),
				];
			}
		}
	}
}
