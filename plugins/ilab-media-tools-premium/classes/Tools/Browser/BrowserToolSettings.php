<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Browser;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool multisiteHide
 * @property bool multisiteAllowUploads
 * @property bool multisiteAllowDeleting
 * @property bool multisiteLockToRoot
 * @property string multisiteRoot
 */
class BrowserToolSettings extends ToolSettings {
	/**
	 * Map of property names to setting names
	 * @var string[]
	 */
	protected $settingsMap = [
		'multisiteHide' => ['mcloud-network-browser-hide', null, false],
		'multisiteAllowUploads' => ['mcloud-network-browser-allow-uploads', null, true],
		'multisiteAllowDeleting' => ['mcloud-network-browser-allow-deleting', null, true],
		'multisiteLockToRoot' => ['mcloud-network-browser-lock-to-root', null, true],
	];

	public function __get($name) {
		if ($name == 'multisiteRoot') {
			if (!isset($this->settings['multisiteRoot'])) {
				if (is_multisite() && $this->multisiteLockToRoot) {
					$dir = wp_upload_dir(null, true);
					$uploadRoot = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'uploads';
					$this->settings['multisiteRoot'] = trailingslashit(ltrim(str_replace($uploadRoot, '', $dir['basedir']),DIRECTORY_SEPARATOR));
				} else {
					$this->settings['multisiteRoot'] = null;
				}
			}

			return $this->settings['multisiteRoot'];
		}

		return parent::__get($name);
	}


	public function __isset($name) {
		if (in_array($name, ['multisiteRoot'])) {
			return true;
		}

		return parent::__isset($name);
	}

}
