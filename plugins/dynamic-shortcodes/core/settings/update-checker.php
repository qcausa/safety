<?php
// Forked and modified from original code by Misha Rudrastyh
// SPDX-FileCopyrightText: Misha Rudrastyh
// SPDX-FileCopyrightText: Ovation S.r.l.
// SPDX-License-Identifier: GPL-3.0-or-later
namespace DynamicShortcodes\Core\Settings;

use DynamicShortcodes\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Based on
 * @copyright Misha Rudrastyh
 * @license GPLv3
 * @author Misha Rudrastyh
 * @filesource https://github.com/rudrastyh/misha-update-checker/blob/main/misha-update-checker.php
 * @version 1.0
 */
class UpdateChecker {

	public $version;
	public $cache_key;
	public $update_url;

	public function __construct() {
		$this->version    = DYNAMIC_SHORTCODES_VERSION;
		$this->cache_key  = DYNAMIC_SHORTCODES_PREFIX . '_update_checker';
		$this->update_url = DYNAMIC_SHORTCODES_LICENSE_URL . '/dynamic-shortcodes/info.php';

		add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
		add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
		add_action( 'upgrader_process_complete', [ $this, 'purge_after_upgrade' ], 10, 2 );

		add_filter(
			'plugin_row_meta',
			//phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			function ( $plugin_meta, $plugin_file, $plugin_data, $status ) {
				if ( $plugin_file === 'dynamic-shortcodes/dynamic-shortcodes.php' ) {
					$plugin_meta[] = sprintf(
						'<a href="%s">%s</a>',
						admin_url( 'admin-ajax.php?action=check_dynamic_shortcodes_updates' ),
						__( 'Check for updates', 'dynamic-shortcodes' )
					);
				}

				return $plugin_meta;
			},
			20,
			4
		);

		add_action(
			'wp_ajax_check_dynamic_shortcodes_updates',
			function () {
				$this->purge_cache();
				$this->update( get_site_transient( 'update_plugins' ) );
				wp_safe_redirect( admin_url( 'plugins.php' ) );
				exit;
			}
		);
	}

	/**
	 * Request update checker
	 *
	 * @return mixed
	 */
	public function request() {

		$remote = get_transient( $this->cache_key );

		if ( false === $remote ) {

			$license_facade = Plugin::instance()->license_facade;

			$remote = wp_remote_get(
				$this->update_url,
				[
					'timeout' => 10,
					'headers' => [
						'Accept' => 'application/json',
					],
					'body' => [
						's' => $license_facade->get_current_domain(),
						'v' => DYNAMIC_SHORTCODES_VERSION,
						'k' => $license_facade->get_license_key(),
						'beta' => get_option( DYNAMIC_SHORTCODES_PREFIX . '_beta', false ) ? 'true' : 'false',
					],
				]
			);

			if (
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			set_transient( $this->cache_key, $remote, 12 * HOUR_IN_SECONDS );

		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;
	}

	/**
	 * Info
	 *
	 * @param false|object|array $res
	 * @param string $action
	 * @param object $args
	 * @return array
	 */
	public function info( $res, $action, $args ) {
		// do nothing if you're not getting plugin information right now
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		// do nothing if it is not our plugin
		if ( DYNAMIC_SHORTCODES_PLUGIN_BASE !== $args->slug ) {
			return $res;
		}

		// get updates
		$remote = $this->request();

		if ( ! $remote ) {
			return $res;
		}

		$res                = $remote;
		$res->download_link = $remote->download_url ?? '';

		$res->sections = array(
			'description' => $remote->sections->description ?? '',
			'installation' => $remote->sections->installation ?? '',
			'changelog' => $remote->sections->changelog ?? '',
		);

		if ( ! empty( $remote->banners ) ) {
			$res->banners = array(
				'low' => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}

		return $res;
	}

	/**
	 * Update
	 *
	 * @param mixed $transient
	 * @return object
	 */
	public function update( $transient ) {
		if ( ! isset( $transient->checked[ DYNAMIC_SHORTCODES_PLUGIN_BASE ] ) ) {
			return $transient;
		}

		$remote = $this->request();

		if (
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {

			$res                                 = new \stdClass();
			$res->slug                           = DYNAMIC_SHORTCODES_PLUGIN_BASE;
			$res->plugin                         = DYNAMIC_SHORTCODES_PLUGIN_BASE;
			$res->new_version                    = $remote->version;
			$res->tested                         = $remote->tested;
			$res->package                        = $remote->download_url ?? '';
			$transient->response[ $res->plugin ] = $res;
		}

		return $transient;
	}

	/**
	 * Purge transient cache
	 *
	 * @param \WP_Upgrader $upgrader
	 * @param array $options
	 * @return void
	 */
	public function purge_after_upgrade( $upgrader, $options ) {
		if (
			'update' === $options['action']
			&& 'plugin' === $options['type']
		) {
			// just clean the cache when new plugin version is installed
			$this->purge_cache();
		}
	}

	/**
	 * Delete transient cache
	 *
	 * @return void
	 */
	public function purge_cache() {
		delete_transient( $this->cache_key );
	}
}
