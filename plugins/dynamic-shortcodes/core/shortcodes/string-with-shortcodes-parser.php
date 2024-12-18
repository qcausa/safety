<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

class StringWithShortcodesParser {
	private $types_regex = '';

	private function escape_regex_pattern( $str ) {
		$replacements = [
			'\\' => '\\\\',
			'/'  => '\/',
			'.'  => '\.',
			'^'  => '\^',
			'$'  => '\$',
			'*'  => '\*',
			'+'  => '\+',
			'?'  => '\?',
			'('  => '\(',
			')'  => '\)',
			'['  => '\[',
			']'  => '\]',
			'{'  => '\{',
			'}'  => '\}',
			'|'  => '\|',
		];

		return strtr( $str, $replacements );
	}

	public function __construct( $types ) {
		$escaped_types     = array_map( [ $this, 'escape_regex_pattern' ], $types );
		$this->types_regex = implode( '|', $escaped_types );
	}

	/**
	 * Finds the start of a shortcode (for example `[form:`) inside a string.
	 *
	 * @param string $str
	 * @param int $pos where to start searching the shortcode
	 * character class (omitting the square brackets) where the search sould
	 * stop.
	 * @return int|false
	 */
	public function find_shortcode_start( $str, $pos ) {
		$res = preg_match( '/(?<beg>\{)(?:' . $this->types_regex . '):/', $str, $matches, PREG_OFFSET_CAPTURE, $pos );
		if ( $res !== 1 ) {
			return false;
		}
		return $matches['beg'][1];
	}

	/**
	 * Return the parsed string and the position where it stopped parsing. The
	 * parse is an sequential array containing strings that should be rendered
	 * as is, and shortcodes.
	 *
	 * @param string $str
	 * @param int $start_pos the position of the string in the original string
	 * @param bool $ignore_syntax_error
	 * @return array<mixed>
	 */
	public function parse( $str, $start_pos = 0, $ignore_syntax_error = true ) {
		$result = [];
		/**
		 * @var int $buffer_pos content of $str after this position still need to
		 * be added to the buffer.
		 */
		$buffer_pos = 0;
		/**
		 * @var int $search_pos start searching for the next shortcode from this
		 * position.
		 */
		$search_pos = 0;
		while ( true ) {
			$notafter = null;
			$res      = $this->find_shortcode_start( $str, $search_pos );
			if ( $res === false ) {
				break;
			}
			$parser    = new ShortcodeParser( $str, $res, $this, $start_pos );
			$shortcode = $parser->parse();
			if ( $shortcode ) {
				// if shortcode syntax is valid:
				// add the content up to the beginning this shortcode to the buffer:
				$result[]   = substr( $str, $buffer_pos, $res - $buffer_pos );
				$result[]   = $shortcode;
				$search_pos = $shortcode['end'];
				$buffer_pos = $shortcode['end'];
			} elseif ( $ignore_syntax_error ) {
					// search for the next shortcode starting after the failed shortcode match:
					$search_pos = $res + 1;
			} else {
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new ParseError( '', $res );
			}
		}
		// add to the buffer the remaining content.
		$result[] = substr( $str, $buffer_pos );
		return $result;
	}
}
