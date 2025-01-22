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

namespace MediaCloud\Plugin\Tools\Assets;


use MediaCloud\Plugin\Tools\ToolSettings;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * @property bool queuePush
 * @property bool storeCSS
 * @property bool storeJS
 * @property bool processLive
 * @property bool logWarnings
 * @property bool gzip
 * @property string cacheControl
 * @property string cdnBase
 * @property string cdnBaseRaw
 * @property bool useVersion
 * @property string expiresSeconds
 * @property string expires
 * @property string version
 * @property bool processContent
 * @property bool stripBucket
 */
class AssetsToolSettings extends ToolSettings {
	protected $settingsMap = [
		"queuePush" => ['mcloud-assets-queue-push', null, true],
		"storeCSS" => ['mcloud-assets-store-css', null, false],
		"storeJS" => ['mcloud-assets-store-js', null, false],
		"processLive" => ['mcloud-assets-process-live', null, true],
		"logWarnings" => ['mcloud-assets-log-warnings', null, true],
		"gzip" => ['mcloud-assets-gzip', null, false],
		"cacheControl" => ['mcloud-assets-cache-control', null, null],
		"cdnBaseRaw" => ['mcloud-assets-cdn-base', null, false],
		"useVersion" => ['mcloud-assets-use-build-version', null, false],
		"expiresSeconds" => ['mcloud-assets-expires', null, false],
		"version" => ['mcloud-assets-version', null, false],
		"processContent" => ['mcloud-assets-process-content', null, false],
		"stripBucket" => ['mcloud-assets-asset-strip-bucket-name', null, false],
	];

	public function __get($name) {
		if ($name === 'expires') {
			if (!empty($this->expiresSeconds)) {
				return gmdate('D, d M Y H:i:s \G\M\T', time() + ((int)$this->expiresSeconds * 60));
			}

			return null;
		}

		if ($name === 'cdnBase') {
			if (!empty($this->cdnBaseRaw)) {
				return trim($this->cdnBaseRaw,'/');
			}

			return null;
		}

		if ($name === 'version') {
			$val = parent::__get($name);
			if (empty($val)) {
				$val = $this->version = time();
			}

			return $val;
		}

		return parent::__get($name);
	}

	public function __isset($name) {
		if (in_array($name, ['expires', 'cdnBase', 'version'])) {
			return true;
		}

		return parent::__isset($name);
	}
}