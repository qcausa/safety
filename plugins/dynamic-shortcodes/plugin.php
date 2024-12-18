<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes;

use DynamicShortcodes\Core\Settings\Manager as SettingsManager;
use DynamicShortcodes\Core\Settings\UpdateChecker;
use DynamicShortcodes\Core\Settings\Cron;

class Plugin {
	/**
	 * @var ?Plugin
	 */
	public static $instance;

	/**
	 * @var Core\Shortcodes\Manager
	 */
	public $shortcodes_manager;

	/**
	 * @var Core\DbUpgrades\Manager
	 */
	public $db_upgrades_manager;

	/**
	 * @var Core\Settings\Manager
	 */
	public $settings_manager;

	/**
	 * @var Core\LicenseFacade
	 */
	public $license_facade;

	/**
	 * @var Core\Settings\Cron
	 */
	public $cron;
	public $update_checker;
	public $demo_manager;
	public $library_manager;
	public $collection_manager;

	private function __construct() {
		self::$instance = $this;
		spl_autoload_register( [ $this, 'autoload' ] );
		$this->db_upgrades_manager = new Core\DbUpgrades\Manager();
		$this->shortcodes_manager  = new Core\Shortcodes\Manager();
		$this->license_facade      = new Core\LicenseFacade();
		$this->settings_manager    = new Core\Settings\Manager();
		$this->update_checker      = new UpdateChecker();
		$this->cron                = new Cron();
		$this->demo_manager        = new Core\Demo\Manager();
		$this->library_manager     = new Core\Library\Manager();
		$this->collection_manager  = new Core\Collection\Manager();

		$prefix = SettingsManager::OPTIONS_PREFIX;
		if ( get_option( $prefix . 'enabled_in_elementor_dynamic_tag', false ) ) {
			new Core\Elementor\Manager();
		}
		if ( get_option( $prefix . 'enabled_in_breakdance', false ) ) {
			new Core\Breakdance\Manager();
		}
		if ( get_option( $prefix . 'enabled_in_oxygen', false ) ) {
			new Core\Oxygen\Manager();
		}
		if ( true || get_option( $prefix . 'enabled_in_bricks', false ) ) {
			new Core\Bricks\Manager();
		}
		if ( ! is_admin() ) {
			$this->add_frontend_filters();
		}
		$this->add_dce_pdf_filter();
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		add_action(
			'init',
			function () {
				do_action( 'dynamic-shortcodes/init', $this );
			}
		);
	}

	private function add_frontend_filters() {
		$prefix = SettingsManager::OPTIONS_PREFIX;
		$filter = [ $this->shortcodes_manager, 'expand_shortcodes' ];
		if ( get_option( $prefix . 'enabled_in_post_content', false ) ) {
			add_filter( 'the_content', $filter );
			add_filter( 'the_content_feed', $filter );
		}
		if ( get_option( $prefix . 'enabled_in_post_title', false ) ) {
			add_filter( 'the_title', $filter );
			add_filter( 'the_title_rss', $filter );
		}
		if ( get_option( $prefix . 'enabled_in_post_excerpt', false ) ) {
			add_filter( 'get_the_excerpt', $filter );
			add_filter( 'the_excerpt', $filter );
		}
		// this option is no longer in the dashboard, but it was before:
		if ( get_option( $prefix . 'enabled_in_widget_text', false ) ) {
			add_filter( 'widget_text', $filter );
		}
		if ( get_option( $prefix . 'enabled_as_wp_shortcode', false ) ) {
			$this->add_dsh_shortcode();
		}

		if ( get_option( $prefix . 'enabled_in_blocks_content', false ) ) {
			add_filter(
				'render_block',
				function ( $content, $block, $instance ) use ( $filter ) {
					// filter only not-nested blocks (those that don't have a parent).
					if ( $instance->block_type && $instance->block_type->parent === null ) {
						return $filter( $content );
					}
					return $content;
				},
				10,
				3
			);
		}
	}

	public function add_dsh_shortcode() {
		$filter = [ $this->shortcodes_manager, 'expand_shortcodes' ];
		add_shortcode(
			'dsh',
			function ( $_atts, $content ) use( $filter ) {
				return $filter( $content );
			}
		);
	}

	private function add_dce_pdf_filter() {
		$prefix = SettingsManager::OPTIONS_PREFIX;
		$filter = [ $this->shortcodes_manager, 'expand_shortcodes' ];
		add_filter(
			'dynamicooo/html-pdf/html-template',
			function ( $content ) use ( $filter ) {
				return $filter( $content );
			},
			10,
			3
		);
	}

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			new self();
		}
		/** @var Plugin self::$instance */
		return self::$instance;
	}

	/**
	 * @param string $search_class
	 * @return void
	 */
	public function autoload( $search_class ) {
		if ( 0 !== strpos( $search_class, __NAMESPACE__ ) ) {
			return;
		}
		if ( ! class_exists( $search_class ) ) {
			if ( preg_match( '/Error$/', $search_class ) ) {
				$filename = 'core/shortcodes/errors';
			} else {
				$filename = strtolower(
					preg_replace(
						[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
						[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
						$search_class
					) ?? ''
				);
			}
			$filename = DYNAMIC_SHORTCODES_PATH . $filename . '.php';

			if ( is_readable( $filename ) ) {
				include $filename;
			}
		}
	}

	/**
	 * Uninstall fired by 'register_deactivation_hook'
	 * @return void
	 */
	public static function deactivate() {
		self::instance()->cron->clear_all_tasks();
	}

	/**
	 * Uninstall fired by 'register_uninstall_hook'
	 * @return void
	 */
	public static function uninstall() {
		self::instance()->license_sytem->dsh_license->deactivate_license();
		// If the deactivation request returns an error the license key is not removed, so it's better to remove the key manually
		delete_option( DYNAMIC_SHORTCODES_PREFIX . '_license_key' );

		self::instance()->cron->clear_all_tasks();
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( 'dynamic-shortcodes/dynamic-shortcodes.php' === $plugin_file ) {
			$row_meta = [
				'docs' => '<a href="https://help.dynamic.ooo/" aria-label="' . esc_attr( __( 'View Documentation', 'dynamic-shortcodes' ) ) . '" target="_blank">' . __( 'Docs', 'dynamic-shortcodes' ) . '</a>',
				'community' => '<a href="https://facebook.com/groups/dynamic.ooo" aria-label="' . esc_attr( __( 'Facebook Community', 'dynamic-shortcodes' ) ) . '" target="_blank">' . __( 'FB Community', 'dynamic-shortcodes' ) . '</a>',
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}
}

Plugin::instance();
