<?php

namespace Jet_WC_Product_Table\Components\Style_Manager;

class Parser {

	protected $data;
	protected $selectors = [];

	public function __construct( $data ) {
		$this->set_data( $data );
	}

	/**
	 * Set input data to parse CSS by.
	 *
	 * @param array $data Array of stored CSS options.
	 */
	public function set_data( array $data = [] ) {
		$this->data = $data;
	}

	/**
	 * Get data to parse CSS by.
	 *
	 * @return array
	 */
	public function get_data() {
		return apply_filters( 'jet-wc-product-table/style-manager/parser/data', $this->data, $this );
	}

	/**
	 * Get selectors to parse CSS for.
	 *
	 * @return array
	 */
	public function get_selectors() {
		return apply_filters( 'jet-wc-product-table/style-manager/parser/selectors', $this->selectors, $this );
	}

	/**
	 * Add new selector to array of prased data.
	 * Format of data:
	 * [
	 *     'selector' => '.css-selector',
	 *     'rules'    => [
	 *         'css-property: %%value_macros%%',
	 *     ],
	 * ]
	 *
	 * Format of macros:
	 * %%style_option.nested_value|default_value%%
	 * - style_option - name of the style option to get value from.
	 * - nested_value - if style option is array - nested property to get from this array.
	 * - default_value - value to use if 1st part returned an empty result.
	 *
	 * @param array $selector_data Selector data.
	 */
	public function add_selector( array $selector_data = [] ) {
		$this->selectors[] = $selector_data;
	}

	/**
	 * Get parsed CSS string
	 *
	 * @param bool $wrap Wrap result into <style> tag or not.
	 * @return string
	 */
	public function get_parsed_css( $wrap = true ) {

		$css = $this->parse_css();

		if ( $wrap ) {
			return sprintf( '<style>%1$s</style>', $css );
		} else {
			return $css;
		}
	}

	/**
	 * Stringify given data value.
	 *
	 * @param array $value Value to stringify (probabaly padding, margin or border).
	 * @return string
	 */
	public function stringify_data_value( array $value = [] ): string {

		if ( empty( $value ) ) {
			return '';
		}

		return new Props_Stringify( $value );
	}

	/**
	 * Get value from macros converted into array trail
	 *
	 * @param  array $trail Macros trail.
	 * @param  array $data  Data to get current trail prop from.
	 * @return string
	 */
	public function get_value_from_trail( array $trail = [], array $data = [] ) {

		$prop  = array_shift( $trail );
		$value = isset( $data[ $prop ] ) ? $data[ $prop ] : false;

		if ( is_array( $value ) && ! empty( $trail ) ) {
			/**
			 * If value is array and we have more items in trail - keep extracting
			 */
			return $this->get_value_from_trail( $trail, $value );
		} elseif ( is_array( $value ) && empty( $trail ) ) {
			/**
			 * If value is array anf trail is finished - we need to stringify it's value,
			 * probably this is padding or border.
			 */
			return $this->stringify_data_value( $value );
		} elseif ( ! empty( $value ) && ! is_array( $value ) ) {
			/**
			 * If value not is array in any case returning it, because we can't extract other trail level.
			 */
			return $value;
		} else {
			/**
			 * In any unexpected case - return an empty result.
			 */
			return '';
		}
	}

	/**
	 * Get appropriate value for the macros from the data.
	 *
	 * @param  string $macros Macros string to get value for.
	 * @return string
	 */
	public function get_macros_value( string $macros = '' ) {

		$value = '';

		$parts         = explode( '|', $macros );
		$default_value = isset( $parts[1] ) ? $parts[1] : false;
		$macros_trail  = explode( '.', $parts[0] );
		$data          = $this->get_data();
		$macros_value  = $this->get_value_from_trail( $macros_trail, $data );

		if ( ! $macros_value && false !== $default_value ) {
			$macros_value = $default_value;
		}

		$value = $macros_value;

		return $value;
	}

	/**
	 * Extract macros from rule and found matched value for each macros in the data.
	 *
	 * @param  string $rule Rule to parse.
	 * @return array
	 */
	public function get_rule_macros_values( string $rule = '' ) {

		preg_match_all( '/%%(.*?)%%/', $rule, $matches );

		$macros_values = [];

		if ( ! empty( $matches ) ) {
			foreach ( $matches[1] as $index => $match ) {
				$macros_value = $this->get_macros_value( $match );
				$macros_values[ $matches[0][ $index ] ] = $macros_value;
			}
		}

		return $macros_values;
	}

	/**
	 * Parse single rule - replace macros with actual data.
	 * Rule can contain multiple macros - if all of the are empy - will be reutrned empty result.
	 * If at least single macros returns some value - will be returned parsed rule.
	 *
	 * @param string $rule CSS rule with macros to parse.
	 * @return string
	 */
	public function parse_rule( $rule ) {

		$parsed_rule  = '';
		$found_macros = $this->get_rule_macros_values( $rule );
		$with_values  = array_filter( $found_macros );

		if ( ! empty( $with_values ) ) {
			$parsed_rule = str_replace( array_keys( $found_macros ), array_values( $found_macros ), $rule );
		}

		return $parsed_rule;
	}

	/**
	 * Apply the data for given list of the rules.
	 * Only rules where is the data is not empty will be kept in the list.
	 *
	 * @param  array $rules Rules list to parse.
	 * @return array
	 */
	public function parse_rules_list( $rules ) {

		$parsed_rules = [];

		foreach ( $rules as $rule ) {

			$parsed_rule = $this->parse_rule( $rule );

			if ( ! empty( $parsed_rule ) ) {
				$parsed_rules[] = $parsed_rule;
			}
		}

		return $parsed_rules;
	}

	/**
	 * Put values from data to selector rules.
	 * If requested part of the data is empty or not exists -
	 * appropriate rule will be removed from the rules set.
	 *
	 * @return array
	 */
	public function prepare_selectors() {

		$parsed_selectors = [];

		foreach ( $this->get_selectors() as $selector_data ) {

			if ( empty( $selector_data['rules'] ) ) {
				continue;
			}

			$rules = $this->parse_rules_list( $selector_data['rules'] );

			if ( ! empty( $rules ) ) {
				$parsed_selectors[] = [
					'selector' => $selector_data['selector'],
					'rules'    => $rules,
				];
			}
		}

		return $parsed_selectors;
	}

	/**
	 * Generate array of styles grouped by selectors
	 *
	 * @param  array $selectors Raw parsed selectors data.
	 * @return array
	 */
	public function generate_css_array( array $selectors = [] ) {

		$styles = [];

		foreach ( $selectors as $selector_data ) {

			if ( empty( $selector_data['rules'] ) ) {
				continue;
			}

			if ( empty( $styles[ $selector_data['selector'] ] ) ) {
				$styles[ $selector_data['selector'] ] = '';
			}

			foreach ( $selector_data['rules'] as $rule ) {
				$styles[ $selector_data['selector'] ] .= rtrim( trim( $rule ), ';' ) . ';';
			}
		}

		return $styles;
	}

	/**
	 * Perform CSS parsing for given selectors by given data.
	 *
	 * @return string
	 */
	public function parse_css() {

		/**
		 * Fires beore CSS parsing. You can use it to change $data or $selectors properties.
		 */
		do_action( 'jet-wc-product-table/style-manager/parser/before-parse-css', $this );

		$selectors = $this->prepare_selectors();
		$css_array = $this->generate_css_array( $selectors );

		$css = '';

		foreach ( $css_array as $selector => $rules ) {
			$css .= sprintf( '%1$s {%2$s}', $selector, $rules );
		}

		return $css;
	}
}
