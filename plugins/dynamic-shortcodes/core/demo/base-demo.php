<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Demo;

use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\ShortcodeParser;
use DynamicShortcodes\Core\Shortcodes\Composer;

abstract class BaseDemo {

	protected $type;
	protected $examples = [];

	/**
	 * Render List
	 *
	 * @return void
	 */
	public function render_list() {
		ob_start();
		$this->start_wrapper();
		// translators: %s is a shortcode type.
		$msg = esc_html__( 'Demo for %s Dynamic Shortcode', 'dynamic-shortcodes' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<h1>' . sprintf( $msg, '<strong>' . esc_html( $this->type ) . '</strong>' ) . '</h1>';

		$type_label = ucfirst( $this->type ) . ' - Dynamic Shortcodes';
		// translators: %s is a shortcode type.
		$full_doc_text = sprintf( esc_html__( 'Full documentation about %s Dynamic Shortcodes.', 'dynamic-shortcodes' ), $this->type );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p><a target="_blank" href="https://help.dynamic.ooo/en/?q=' . esc_attr( $type_label ) . '">' . $full_doc_text . '</a></p>';

		$this->render_description();

		$this->render_first_steps_examples();

		if ( $this->prerequisites_satisfied_for_current_element() ) {
			$this->render_examples_for_current_element();
		} else {
			$this->render_error_missing_prerequisites();
		}

		$this->maybe_render_examples_for_other_elements();

		$this->end_wrapper();

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	protected function render_examples_for_current_element() {
		$fields = Plugin::instance()->library_manager->get_fields(
			[
				'type' => $this->type,
				'format' => 'composer',
			]
		);

		$this->add_examples( $fields );

		if ( empty( $this->examples ) ) {
			$this->render_no_examples_message();
			return;
		}

		$this->start_table();
		$this->render_examples();
		$this->end_table();
		$this->empty_examples();
	}

	protected function maybe_render_examples_for_other_elements() {
		// Generate examples with |ID
		$fields_with_id = $this->generate_fields_with_id();

		if ( ! empty( $fields_with_id ) ) {
			echo '<h3>' . esc_html__( 'Examples for other elements', 'dynamic-shortcodes' ) . '</h3>';
			$this->add_examples( $fields_with_id );
			$this->start_table();
			$this->render_examples();
			$this->end_table();
		}
	}

	public function render_error_missing_plugin( $plugin ) {
		ob_start();

		$this->start_wrapper();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<h1>' . $this->type . '</h1>';
		// translators: %s: Name of the plugin
		printf( esc_html__( 'Demo not available. %s plugin is missing.', 'dynamic-shortcodes' ), '<strong>' . esc_html( $plugin ) . '</strong>' );
		$this->end_wrapper();

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function prerequisites_satisfied_for_current_element() {
		return true;
	}

	public function render_error_missing_prerequisites() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="error-message">' . $this->get_missing_prerequisites_message() . '</p>';
	}

	protected function render_no_examples_message() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="error-message">' . esc_html__( 'There are no examples for the content of this page.', 'dynamic-shortcodes' ) . '</p>';
	}

	public function get_missing_prerequisites_message() {
		return '';
	}

	/**
	 * Flag to indicate if the type requires all privileges
	 *
	 * @return boolean
	 */
	public function requires_all_privileges() {
		return false;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get First Steps Examples
	 *
	 * @return string
	 */
	public function get_first_steps_examples() {}

	/**
	 * Show the type description
	 *
	 * @return void
	 */
	final public function render_description() {
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . $this->get_description();

		if ( $this->requires_all_privileges() ) {
			echo '<br /><a target="_blank" href="' . esc_attr( admin_url( 'admin.php?page=dynamic-shortcodes-power' ) ) . '">' . esc_html__( 'This type must be used inside a Power Shortcode.', 'dynamic-shortcodes' ) . '</a>';
		}

		echo '</p>';
	}

	/**
	 * Show First Steps Examples
	 *
	 * @return string|void
	 */
	final public function render_first_steps_examples() {
		if ( ! $this->get_first_steps_examples() ) {
			return;
		}

		echo '<h4>' . esc_html__( 'First Steps', 'dynamic-shortcodes' ) . '</h4>';

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . $this->get_first_steps_examples() . '</p>';
	}

	public function generate_sample_id() {
		return [];
	}

	final protected function add_examples( $new_examples ) {
		$this->examples = array_merge( $this->examples, $new_examples );
	}

	final protected function empty_examples() {
		$this->examples = [];
	}

	/**
	 * Calls a function with provided parameters if available.
	 *
	 * @param array $function
	 * @return mixed
	 */
	private function call_function_with_parameters( $func ) {
		return ! empty( $func['parameters'] ) ? call_user_func( $func['name'], call_user_func( $func['parameters'] ) ) : call_user_func( $func['name'] );
	}

	/**
	 * Applies an array of filters to a given input.
	 *
	 * @param array $filters
	 * @param mixed $input
	 * @return mixed
	 */
	private function apply_filters( array $filters, $input ) {
		foreach ( $filters as $filter ) {
			$input = $this->add_filter( $filter, $input );
		}
		return $input;
	}


	/**
	 * Render Examples
	 *
	 * @return void
	 */
	final protected function render_examples() {
		foreach ( $this->examples as $example ) {
			if ( ( $example['show_in_demo'] ?? true ) === false ) {
				continue;
			}
			$this->render_example( $example );
		}
	}

	/**
	 * Get Parameter
	 *
	 * @param mixed $field
	 * @return string
	 */
	final protected function get_args( $field ) {
		switch ( gettype( $field ) ) {
			case 'array':
				return $field['args'] ?? '';
			default:
				return $field;
		}
	}

	/**
	 * Generate Fields with ID
	 *
	 * @return array<string>
	 */
	final protected function generate_fields_with_id() {
		$examples = [];

		$generators = $this->generate_sample_id();
		if ( empty( $generators ) ) {
			return $examples;
		}

		foreach ( $generators as $generator ) {
			$id_list = $this->call_function_with_parameters( $generator );

			if ( empty( $id_list ) ) {
				continue;
			}

			$examples = $this->generate_examples_from_id_list( $examples, $generator, $id_list );
		}

		return $examples;
	}

	/**
	 * Generates examples from an ID list.
	 *
	 * @param array $examples
	 * @param array $generator
	 * @param array $id_list
	 * @return array
	 */
	private function generate_examples_from_id_list( array $examples, array $generator, array $id_list ) {
		foreach ( $id_list as $id ) {
			$example = [
				'args' => [ $generator['arg'] ],
				'keyargs' => [
					$generator['keyarg'] => $id,
				],
			];

			if ( isset( $generator['result_types'] ) ) {
				$example['result_types'] = $generator['result_types'];
			}

			$examples[] = $example;
		}

		return $examples;
	}

	/**
	 * Start Wrapper
	 *
	 * @return void
	 */
	final protected function start_wrapper() {
		echo '<div class="dynamic-shortcodes-demo">';
	}

	/**
	 * End Wrapper
	 *
	 * @return void
	 */
	final protected function end_wrapper() {
		echo '</div>';
	}

	/**
	 * Start the table
	 *
	 * @return void
	 */
	final protected function start_table() {
		echo '<table>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'What', 'dynamic-shortcodes' ) . '</th>';
		echo '<th>' . esc_html__( 'Dynamic Shortcode', 'dynamic-shortcodes' ) . '</th>';
		echo '<th>' . esc_html__( 'Result', 'dynamic-shortcodes' ) . '</th>';
		echo '</tr>';
	}

	/**
	 * End the table
	 *
	 * @return void
	 */
	final protected function end_table() {
		echo '</table>';
	}

	/**
	 * Remove filters from a shortcode
	 *
	 * @param string $field
	 * @return string
	 */
	final protected function remove_filters( string $field ) {
		return explode( '|', $field )[0];
	}

	public static function escape_code( $code ) {
		$code = str_replace( '{', '&#123;', $code );
		$code = str_replace( '}', '&#125;', $code );
		return $code;
	}

	/**
	 * Render single row of the table
	 *
	 * @param string $field
	 * @param string $shortcode
	 * @return void
	 */
	final protected function render_example( $example ) {
		if ( empty( $example ) ) {
			return;
		}

		$shortcode_code = $this->get_shortcode_code( $example );
		$result         = $this->get_result( $shortcode_code );
		$require_power  = $result['require_power'];

		echo '<tr>';

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td class="what">' . $this->get_what( $example ) . '</td>';

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td class="code"><code>' . self::escape_code( $shortcode_code ) . '</code>';

		if ( isset( $example['filters'] ) ) {
			if ( '|' === ( $example['filters'][0]['type'] ?? '' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<br /><small>' . esc_html__( 'The first argument passed to the function used as a filter is the result of the shortcode', 'dynamic-shortcodes' ) . '</a></small>';
			} elseif ( '|-' === ( $example['filters'][0]['type'] ?? '' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<br /><small>' . esc_html__( 'The last argument passed to the function used as a filter is the result of the shortcode', 'dynamic-shortcodes' ) . '</a></small>';
			}
		}
		if ( $require_power ) {
			$power_description = esc_html__( 'Within Power Shortcodes you do not have the security restrictions normally placed on Dynamic Shortcodes. When writing Power Shortcodes keep in mind that they can be written only by administrators but then also used by contributors', 'dynamic-shortcodes' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<br /><small title="' . $power_description . '"><a target="_blank" href="' . admin_url( 'admin.php?page=dynamic-shortcodes-power' ) . '">' . esc_html__( 'Only inside Power Shortcodes', 'dynamic-shortcodes' ) . '</a></small>';
		}
		$this->render_copy_button( $shortcode_code );
		echo '</td>';

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<td><div class=result>' . $this->maybe_render_result( $result['value'], $example ) . '</div></td>';
		echo '</tr>';
	}

	final protected function get_what( $example ) {
		$what = $example['args'][0] ?? '';

		if ( ! empty( $example['keyargs'] ) ) {
			$key   = key( $example['keyargs'] );
			$value = $example['keyargs'][ $key ];
			$what .= ' with <i>' . $key . '</i>';
			$what .= ' <strong>' . $value . '</strong>';
		}

		if ( ! empty( $example['filters'] ) ) {
			foreach ( $example['filters'] as $key => $value ) {
				$what .= ' with <i>filter</i> <strong>' . $value['name'] . '</strong>';
			}
		}

		return wp_kses_post( $what );
	}

	/**
	 * Get Shortcode
	 *
	 * @param mixed $parameter
	 * @return void
	 */
	final protected function get_shortcode_code( $example ) {
		$example = $this->add_type( $example );

		return Composer::compose( [ $example ] );
	}

	final protected function add_type( $example ) {
		$type = [
			'type' => $this->type,
		];
		return array_merge( $type, $example );
	}

	/**
	 * Maybe Render Shortcode
	 *
	 * @param mixed $shortcode
	 * @return void
	 */
	final protected function maybe_render_result( $result_value, $parameter ) {
		if ( empty( $parameter ) ) {
			return;
		}

		$arg = $parameter['args'][0];

		if ( isset( $parameter['force_result_types'] ) && empty( $result_value ) ) {
			$available_types = []; // Don't set types if the result is empty
		} else {
			$available_types = $parameter['force_result_types'] ?? $this->get_available_result_types( $arg, $result_value );
		}
		$list_available_types = $this->get_list_available_result_types();

		$types = [];
		foreach ( $available_types as $type ) {
			if ( array_key_exists( $type, $list_available_types ) ) {
				$types[ $type ] = $list_available_types[ $type ];
			}
		}

		if ( empty( $types ) ) {
			return $result_value;
		}

		$original_type = $this->get_default_result_type( $available_types );

		return $this->render_result_with_examples( $parameter, $types, $original_type );
	}

	final protected function get_result( $shortcode ) {
		$require_power = false;

		try {
			$result = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $shortcode );
		} catch ( \DynamicShortcodes\Core\Shortcodes\PermissionsError $e ) {
			$require_power = true;
			$result        = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $shortcode, [ 'privileges' => 'all' ] );
		}
		return [
			'value' => $result,
			'require_power' => $require_power,
		];
	}

	protected function get_list_available_result_types() {
		return [
			'empty' => esc_html__( 'Empty String', 'dynamic-shortcodes' ),
			'array-acf-repeater' => esc_html__( 'ACF Repeater', 'dynamic-shortcodes' ),
			'array-post-id' => esc_html__( 'Array of Post ID', 'dynamic-shortcodes' ),
			'array-media-id' => esc_html__( 'Array of Media ID', 'dynamic-shortcodes' ),
			'array-user-id' => esc_html__( 'Array of User ID', 'dynamic-shortcodes' ),
			'array-term-id' => esc_html__( 'Array of Term ID', 'dynamic-shortcodes' ),
			'array-wp_term' => esc_html__( 'Array of WP_Term Objects', 'dynamic-shortcodes' ),
			'array-wp_post' => esc_html__( 'Array of WP_Post Objects', 'dynamic-shortcodes' ),
			'array-wp_user' => esc_html__( 'Array of WP_User Objects', 'dynamic-shortcodes' ),
			'array' => 'Array',
			'array-of-arrays' => esc_html__( 'Array of Arrays', 'dynamic-shortcodes' ),
			'wp_term' => esc_html__( 'WP_Term Object', 'dynamic-shortcodes' ),
			'wp_user' => esc_html__( 'WP_User Object', 'dynamic-shortcodes' ),
			'wp_post' => esc_html__( 'WP_Post Object', 'dynamic-shortcodes' ),
			'post-id' => esc_html__( 'Post ID', 'dynamic-shortcodes' ),
			'user-id' => esc_html__( 'User ID', 'dynamic-shortcodes' ),
			'term-id' => esc_html__( 'Term ID', 'dynamic-shortcodes' ),
			'media-id' => esc_html__( 'Media ID', 'dynamic-shortcodes' ),
			'object' => esc_html__( 'Object', 'dynamic-shortcodes' ),
			'int' => esc_html__( 'Integer', 'dynamic-shortcodes' ),
			'float' => esc_html__( 'Float', 'dynamic-shortcodes' ),
			'double' => esc_html__( 'Double', 'dynamic-shortcodes' ),
			'string' => esc_html__( 'String', 'dynamic-shortcodes' ),
			'boolean' => esc_html__( 'Boolean', 'dynamic-shortcodes' ),
			'date' => esc_html__( 'Date', 'dynamic-shortcodes' ),
			'timestamp' => 'Timestamp',
			'json' => 'JSON',
			'null' => 'Null',
		];
	}


	/**
	 * Identifies and returns applicable result types based on the argument and the result value.
	 *
	 * This function analyzes the result value and its associated argument to determine the possible
	 * result types. It supports a range of types including standard WordPress objects like posts, terms,
	 * users, as well as basic data types such as strings, integers, arrays, and more. The function is
	 * designed to facilitate the dynamic handling of shortcode outputs by categorizing the result into
	 * recognizable types for further processing.
	 *
	 * @param string $arg The argument related to the result, used to identify specific types like '_thumbnail_id'.
	 * @param mixed $result The result value obtained from shortcode processing or other operations, whose type is to be identified.
	 * @return array An array of strings representing the identified result types.
	 */

	protected function get_available_result_types( $arg, $result ) {
		$types = [];

		if ( '_thumbnail_id' === $arg ) {
			$types[] = 'media-id';
		}
		if ( $result === 0 ) {
			$types[] = 'int';
		}
		if ( ! is_bool( $result ) && empty( $result ) ) {
			$types[] = 'empty';
		}
		if ( is_array( $result ) ) {
			$values = array_values( $result );
			if ( ! empty( $values ) ) {

				// ACF Repeater
				if ( ! empty( $values ) && is_array( $values[0] ) ) {
					$keys            = array_keys( $values[0] );
					$is_acf_repeater = true;
					foreach ( $keys as $key ) {
						if ( substr( $key, 0, 6 ) !== 'field_' ) {
							$is_acf_repeater = false;
							break;
						}
					}
					if ( $is_acf_repeater ) {
						$types[] = 'array-acf-repeater';
					}
				}

				if ( $this->is_numeric_array( $values ) ) {
					$first_value = $values[0] ?? '';
					if ( get_post( $first_value ) !== null && get_post_type( $first_value ) !== 'attachment' ) {
						$types[] = 'array-post-id';
					}
					if ( wp_attachment_is( 'image', $first_value ) ) {
						$types[] = 'array-media-id';
					}
					if ( get_userdata( $first_value ) !== false ) {
						$types[] = 'array-user-id';
					}
					if ( term_exists( $first_value ) ) {
						$types[] = 'array-term-id';
					}
				} elseif ( $this->array_contains_only( 'WP_Term', $values ) ) {
					$types[] = 'array-wp_term';
				} elseif ( $this->array_contains_only( 'WP_Post', $values ) ) {
					$types[] = 'array-wp_post';
				} elseif ( $this->array_contains_only( 'WP_User', $values ) ) {
					$types[] = 'array-wp_user';
				}

				if ( $this->is_array_of_arrays( $values ) ) {
					$types[] = 'array-of-arrays';
				}
			}
			$types[] = 'array';
		}
		if ( is_object( $result ) ) {
			if ( $result instanceof \WP_Term ) {
				$types[] = 'wp_term';
			} elseif ( $result instanceof \WP_User ) {
				$types[] = 'wp_user';
			} elseif ( $result instanceof \WP_Post ) {
				$types[] = 'wp_post';
			}
			$types[] = 'object';
		}
		if ( $result !== 0 && ( is_int( $result ) ||
								( is_string( $result ) && ctype_digit( $result ) ) ) ) {
			if ( 'publish' === get_post_status( $result ) ) {
				$types[] = 'post-id';
			}
			if ( get_userdata( $result ) ) {
				$types[] = 'user-id';
			}
			if ( get_term( $result ) instanceof WP_Term ) {
				$types[] = 'term-id';
			}
			if ( wp_attachment_is( 'image', $result ) ) {
				$types[] = 'media-id';
			}
			if ( $this->is_valid_unix_timestamp( $result ) ) {
				$types[] = 'timestamp';
			}
			$types[] = 'int';
		}
		if ( is_float( $result ) ) {
			$types[] = 'float';
		}
		if ( is_bool( $result ) ) {
			$types[] = 'boolean';
		}
		if ( is_string( $result ) ) {
			if ( false !== strtotime( $result ) ) {
				$types[] = 'date';
			}
			if ( ! ctype_digit( $result ) && json_decode( $result ) !== null ) {
				$types[] = 'json';
			}

			$types[] = 'string';
		}

		return array_unique( $types );
	}

	protected function is_array_of_arrays( $arr ) {
		foreach ( $arr as $element ) {
			if ( ! is_array( $element ) ) {
				return false;
			}
		}

		return true;
	}

	protected function is_valid_unix_timestamp( $str ) {
		if ( strlen( $str ) !== 10 ) {
			return false;
		}

		$timestamp = intval( $str );

		// Between 1970 and 2038
		return ( $timestamp >= 0 && $timestamp <= 2147483647 );
	}

	protected function array_contains_only( string $type, $arr ) {
		foreach ( $arr as $element ) {
			if ( ! is_a( $element, $type ) ) {
				return false;
			}
		}
		return true;
	}

	protected function is_numeric_array( array $arr ) {
		foreach ( $arr as $element ) {
			if ( ! is_numeric( $element ) ) {
				return false;
			}
		}
		return true;
	}

	protected function get_default_result_type( $available_types ) {
		return $available_types[0] ?? 'string';
	}

	protected function render_notice_with_examples( $original, $original_type, $notice ) {
		$examples = $this->render_button_expand( $original, $original_type, $additions );
		$notice   = '<span class="notice">' . $notice . '</span>';
		return $notice . $examples;
	}

	protected function get_notices( $type ) {
		$notices = [
			'array' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-of-arrays' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-acf-repeater' => esc_html__( 'The result is an ACF Repeater', 'dynamic-shortcodes' ),
			'array-post-id' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-user-id' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-media-id' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-term-id' => esc_html__( 'The result is an array', 'dynamic-shortcodes' ),
			'array-wp_term' => esc_html__( 'The result is an array of WP_Term objects', 'dynamic-shortcodes' ),
			'array-wp_post' => esc_html__( 'The result is an array of WP_Post objects', 'dynamic-shortcodes' ),
			'array-wp_user' => esc_html__( 'The result is an array of WP_User objects', 'dynamic-shortcodes' ),
			'object' => esc_html__( 'The result is an object', 'dynamic-shortcodes' ),
			'wp_post' => esc_html__( 'The result is a WP_Post Object', 'dynamic-shortcodes' ),
			'wp_user' => esc_html__( 'The result is a WP_User Object', 'dynamic-shortcodes' ),
			'wp_term' => esc_html__( 'The result is a WP_Term Object', 'dynamic-shortcodes' ),
			'empty' => esc_html__( 'The result is an empty string', 'dynamic-shortcodes' ),
			'boolean' => esc_html__( 'The result is a boolean value', 'dynamic-shortcodes' ),
		];

		return $notices[ $type ] ?? [];
	}

	protected function should_expand_examples() {
		return true;
	}

	protected function render_result_with_examples( $original, $available_types, $original_type ) {
		$notice = $this->get_notices( $original_type ?? [ 'empty' ] );

		if ( empty( $notice ) ) {
			$shortcode = $this->get_shortcode_code( $original );
			$result    = Plugin::instance()->shortcodes_manager->evaluate_and_return_value( $shortcode, [ 'privileges' => 'all' ] );
		} else {
			$result = '<span class="notice">' . $notice . '</span>';
		}

		if ( $this->should_expand_examples() && ! ( isset( $original['force_result_types'] ) && is_array( $original['force_result_types'] ) && empty( $original['force_result_types'] ) ) ) {
			$examples = $this->render_button_expand( $original, $available_types, $original_type );
			return $result . $examples;
		}

		return $result;
	}

	protected function render_button_expand( $original, $available_types, $original_type ) {
		$text = esc_html__( 'Expand examples', 'dynamic-shortcodes' );

		$original = $this->add_type( $original );
		return '<button
			class="expand-examples"
			data-id_examples=' . get_the_ID() . '
			data-original="' . htmlspecialchars( wp_json_encode( $original ) ) . '"
			data-available_types="' . htmlspecialchars( wp_json_encode( $available_types ) ) . '"
			data-default_type=' . wp_json_encode( $original_type ) . '
				>' . $text . '</button>';
	}

	/**
	 * Render the copy button
	 *
	 * @param string $shortcode
	 * @return void
	 */
	final protected function render_copy_button() {
		echo '<button class="copy-button">' . esc_html__( 'Copy', 'dynamic-shortcodes' ) . '</button>';
	}

	/**
	 * Add a filter to a shortcode
	 *
	 * @param string $filter
	 * @param array<string> $fields
	 * @return array<string>
	 */
	final protected function add_filter( string $filter, array $fields ) {
		if ( empty( $filter ) ) {
			return $fields;
		}
		$fields_with_filter = [];
		foreach ( $fields as $key => $value ) {
			$fields_with_filter[ $key . '|' . $filter ] = '';
		}
		return $fields_with_filter;
	}

	/**
	 * Check plugin dependencies
	 *
	 * @return boolean
	 */
	protected function check_plugin_dependencies() {
		return true;
	}

	/**
	 * Generate posts id useful for demo on shortcode post
	 *
	 * @return array<string>|false
	 */
	protected static function generate_post_id() {
		$args = [
			'posts_per_page' => 5,
			'post_status' => 'publish',
			'fields' => 'ids',
		];
		return get_posts( $args );
	}

	/**
	 * Generate products id useful for demo on shortcode product
	 *
	 * @return array<string>|false
	 */
	protected static function generate_product_id() {
		$args = [
			'posts_per_page' => 5,
			'post_type' => 'product',
			'post_status' => 'publish',
			'fields' => 'ids',
		];
		return get_posts( $args );
	}

	/**
	 * Generate users id useful for demo on shortcode user
	 *
	 * @return array<string>|false
	 */
	protected static function generate_user_id() {
		$args = [
			'number' => 5,
			'fields' => 'ids',
		];
		return get_users( $args );
	}

	/**
	 * Generate terms id useful for demo on shortcode term
	 *
	 * @return array<string>|false
	 */
	protected static function generate_term_id() {
		$args = [
			'number' => 5,
			'fields' => 'ids',
		];

		return get_terms( $args );
	}
}
