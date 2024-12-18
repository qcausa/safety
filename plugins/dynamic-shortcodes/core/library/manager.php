<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library;

use DynamicShortcodes\Plugin;
use DynamicShortcodes\Core\Shortcodes\Composer;

class Manager {

	/**
	 * @var array<string,string>
	 */
	protected $types = [];

	public function __construct() {
		$this->types = [
			'post' => [
				'class' => 'Post',
			],
			'acf' => [
				'class' => 'Acf',
			],
			'call' => [
				'class' => 'Call',
			],
			'date' => [
				'class' => 'Date',
			],
			'user' => [
				'class' => 'User',
			],
			'author' => [
				'class' => 'Author',
			],
			'cookie' => [
				'class' => 'Cookie',
			],
			'jet' => [
				'class' => 'JetEngine',
			],
			'metabox' => [
				'class' => 'Metabox',
			],
			'option' => [
				'class' => 'Option',
			],
			'param-get' => [
				'class' => 'ParameterGet',
			],
			'param-post' => [
				'class' => 'ParameterPost',
			],
			'pods' => [
				'class' => 'Pods',
			],
			'server' => [
				'class' => 'Server',
			],
			'term' => [
				'class' => 'Term',
			],
			'archive' => [
				'class' => 'Archive',
			],
			'toolset' => [
				'class' => 'Toolset',
			],
			'query' => [
				'class' => 'Query',
			],
			'query-var' => [
				'class' => 'QueryVar',
			],
			'woo' => [
				'class' => 'WooCommerce',
			],
		];
	}

	public function get_types() {
		return $this->types;
	}


	public function get_fields( $settings ) {
		if ( empty( $settings ) ) {
			return;
		}
		$type   = $settings['type'];
		$format = $settings['format'];

		$class_name = '\DynamicShortcodes\Core\Library\Types\\' . $this->get_types()[ $type ]['class'];
		if ( ! class_exists( $class_name ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( "The class {$class_name} doesn't exist" );
		}

		switch ( $format ) {
			case 'list':
				$method = 'get_fields_list';
				break;
			case 'composer':
				$method = 'get_fields_composer';
				break;
			default:
				throw new \InvalidArgumentException( "Parameter 'what' not valid" );
		}

		if ( ! method_exists( $class_name, $method ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( "Method {$method} doesn't exist in the class {$class_name}" );
		}

		return call_user_func( [ $class_name, $method ] );
	}

	public function is_not_hidden_field( $settings, $field ) {
		$type = $settings['type'];

		$class_name = '\DynamicShortcodes\Core\Library\Types\\' . $this->get_types()[ $type ]['class'];
		if ( ! class_exists( $class_name ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( "The class {$class_name} doesn't exist" );
		}

		$method = 'field_is_not_hidden';

		if ( ! method_exists( $class_name, $method ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( "Method {$method} doesn't exist in the class {$class_name}" );
		}

		return call_user_func_array( [ $class_name, $method ], [ $field ] );
	}
}
