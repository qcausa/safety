<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core;

use DynamicShortcodes\Plugin;

class LicenseFacade {
	const DSH_LICENSE = 'dsh';
	const DCE_LICENSE = 'dce';

	public $dsh_license;
	private $dce_license_system = false;

	public function __construct() {
		$this->dsh_license = new License\License(
			[
				'prefix' => DYNAMIC_SHORTCODES_PREFIX,
				'beta_option' => DYNAMIC_SHORTCODES_PREFIX . '_beta',
				'product_unique_id' => DYNAMIC_SHORTCODES_PRODUCT_UNIQUE_ID,
				'version' => DYNAMIC_SHORTCODES_VERSION,
				'license_url' => DYNAMIC_SHORTCODES_LICENSE_URL,
			]
		);
		add_action( 'dynamic-content-for-elementor/elementor-init', [ $this, 'dce_init' ] );
	}

	public function is_dce_active() {
		return $this->dce_license_system !== false;
	}

	public function dce_init( $dce_plugin ) {
		$this->dce_license_system = $dce_plugin->license_system;
	}

	public function get_license_key() {
		if ( $this->get_active_license() === self::DSH_LICENSE ) {
			return $this->dsh_license->get_license_key();
		} elseif ( $this->get_active_license() === self::DCE_LICENSE ) {
			return $this->dce_license_system->get_license_key();
		}
	}

	public function get_current_domain() {
		if ( $this->get_active_license() === self::DSH_LICENSE ) {
			return $this->dsh_license->get_current_domain();
		} elseif ( $this->get_active_license() === self::DCE_LICENSE ) {
			return $this->dce_license_system->get_current_domain();
		}
	}

	public function get_active_license() {
		if ( $this->dsh_license->is_license_active() ) {
			return self::DSH_LICENSE;
		}
		if ( $this->dce_license_system && $this->dce_license_system->is_license_active() ) {
			return self::DCE_LICENSE;
		}
		return false;
	}
}
