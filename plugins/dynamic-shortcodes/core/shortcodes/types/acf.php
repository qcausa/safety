<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes\Types;

use DynamicShortcodes\Core\Shortcodes\BaseShortcode;
use DynamicShortcodes\Core\Shortcodes\EvaluationError;
use DynamicShortcodes\Plugin;

class Acf extends BaseShortcode {
	public static function get_shortcode_types( $context ) {
		return [
			'acf',
			'acf-loop',
			'acf-row',
		];
	}

	public function evaluate() {
		$this->ensure_plugin_dependencies( [ 'acf' ] );
		switch ( $this->type ) {
			case 'acf':
				return $this->evaluate_acf();
			case 'acf-loop':
				return $this->evaluate_loop();
			case 'acf-row':
				return $this->evaluate_row();
		}
	}

	private function select_id() {
		$id = null;
		if ( $this->has_keyarg( 'option' ) ) {
			$id = 'option';
		} elseif ( $this->has_keyarg( 'user' ) ) {
			if ( $this->has_keyarg( 'id' ) ) {
				$this->ensure_all_privileges( esc_html__( 'Reading User ACF fields of a specific user', 'dynamic-shortcodes' ) );
				$id = 'user_' . $this->get_keyarg( 'id', 'numeric' );
			} else {
				$id = 'user_' . get_current_user_id();
			}
		} elseif ( $this->has_keyarg( 'term' ) ) {
			if ( $this->has_keyarg( 'id' ) ) {
				$term_id = $this->get_keyarg( 'id', 'numeric' );
			} else {
				$term_id = get_queried_object_id();
			}
			if ( ! term_exists( $term_id ) ) {
				$this->evaluation_error( esc_html__( 'Cannot find a term to use', 'dynamic-shortcodes' ) );
			}
			$id = 'category_' . $term_id;
		} elseif ( $this->has_keyarg( 'id' ) ) {
			$id = $this->get_keyarg( 'id' );
			if ( ! is_post_publicly_viewable( $id ) ) {
				$this->ensure_all_privileges( esc_html__( 'Reading ACF Field on not public post', 'dynamic-shortcodes' ) );
			}
		}
		return $id;
	}

	private function get_field_object( $id, $name, $format ) {
		$obj = get_sub_field_object( $name, $format );
		if ( $obj ) {
			return $obj;
		}
		return get_field_object( $name, $id, $format );
	}

	private function evaluate_relationship_loop( $obj, $sep ) {
		$value = $obj['value'];
		if ( is_array( $value ) ) {
			$me       = $this;
			$callback = function () use ( $me ) {
				return $me->get_arg_as_string( 1, true );
			};
			return self::loop_ids_with_callback(
				$value,
				$sep,
				$callback,
				$this,
			);
		} else {
			return '';
		}
	}

	private function evaluate_loop() {
		$this->should_not_sanitize();
		$this->arity_check( 2, 2 );
		$this->init_keyargs(
			[
				'id' => [],
				// ACF locations:
				'post' => [],
				'user' => [],
				'term' => [],
				'option' => [],
				'separator' => [ 'sep' ],
			],
			[
				[ 'post', 'user', 'term', 'option' ],
				[ 'option', 'id' ],
			]
		);
		$id   = $this->select_id();
		$name = $this->get_arg( 0, 'string' );
		$sep  = $this->get_keyarg_default( 'separator', '', 'string' );
		$obj  = $this->get_field_object( $id, $name, false );
		if ( $obj && $obj['type'] === 'relationship' ) {
			return $this->evaluate_relationship_loop( $obj, $sep );
		}
		$buf       = [];
		$local_env = $this->unit_interpreter->local_env;
		while ( have_rows( $name, $id ) ) {
			the_row();
			$local_env->open_scope();
			try {
				$buf[] = $this->get_arg_as_string( 1, true );
			} finally {
				$local_env->close_scope();
			}
		}
		return implode( $sep, $buf );
	}

	/**
	 * when getting an acf repeater with no formatting the resulting item
	 * arrays are indexed by the acf subfield keys instead of the user-chosen
	 * acf field names. This fixes that, recursively.
	 */
	public function repeater_keys_to_names( $value ) {
		$data_with_keys = [];
		foreach ( $value as $single_row ) {
			$row_data = array();
			foreach ( $single_row as $subfield_key => $sub_value ) {
				if ( strpos( $subfield_key, 'field_' ) === 0 ) {
					$sub_object = get_field_object( $subfield_key, false );
					$user_key   = $sub_object['name'];
					$type       = $sub_object['type'];
					if ( $type === 'repeater' || $type === 'flexible_content' ) {
						if ( ! $sub_value ) {
							$sub_value = [];
						} else {
							$sub_value = $this->repeater_keys_to_names( $sub_value );
						}
					}
					$row_data[ $user_key ] = $sub_value;
				} else {
					$row_data[ $subfield_key ] = $sub_value;
				}
			}
			$data_with_keys[] = $row_data;
		}
		return $data_with_keys;
	}

	public function evaluate_acf() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs(
			[
				'id' => [],
				'format' => [ 'fmt' ], // enable acf formatting of field.
				'subfield' => [ 'sub' ], // needed for subfields
				// ACF locations:
				'post' => [],
				'user' => [],
				'term' => [],
				'option' => [],
				'setting' => [],
			],
			[
				[ 'post', 'user', 'term', 'option' ],
				[ 'option', 'id' ],
				[ 'subfield', 'id' ],
				[ 'setting', 'format' ],
			],
			[
				'term' => [ 'id' ],
			],
		);
		$name = $this->get_arg( 0, 'string' );
		$id   = $this->select_id();

		$format = $this->get_bool_keyarg( 'format' );
		$obj    = $this->get_field_object( $id, $name, $format );
		if ( $format && ( $obj['return_format'] ?? '' ) === 'object' ) {
			$this->ensure_all_privileges( 'formatting an ACF field that returns an object.' );
		}
		if ( empty( $obj ) ) {
			$msg = esc_html__( 'ACF Field not found', 'dynamic-shortcodes' );
			Plugin::instance()->shortcodes_manager->add_error( $msg, $this->str );
			return null;
		}
		$setting = $this->get_keyarg_default( 'setting', false, 'string' );
		if ( $setting ) {
			if ( isset( $obj[ $setting ] ) ) {
				return $obj[ $setting ];
			} else {
				$this->evaluation_error( "Setting `$setting` not found" );
			}
		}
		$type = $obj['type'];
		if ( $type === 'wysiwyg' ) {
			// force formatting for wysiwyg fields.
			$obj = $this->get_field_object( $id, $name, true );
		}
		$value = $obj['value'];
		if ( $type === 'repeater' || $type === 'flexible_content' ) {
			if ( ! $value ) {
				$value = [];
			} elseif ( ! $format ) {
				$value = $this->repeater_keys_to_names( $value );
			}
		}
		return $value;
	}

	public function evaluate_row() {
		$this->arity_check( 1, 1 );
		$this->init_keyargs( [] );
		switch ( $this->get_arg( 0, 'string' ) ) {
			case 'layout':
				return get_row_layout();
			case 'index':
				return get_row_index();
			default:
				$this->evaluation_error( esc_html__( 'unrecognized argument', 'dynamic-shortcodes' ) );
		}
	}

	/**
	 * @return array<string,string>
	 */
	public function get_meta_aliases() {
		return [];
	}
}
