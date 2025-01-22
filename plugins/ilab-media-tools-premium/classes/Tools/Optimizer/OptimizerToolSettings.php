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

namespace MediaCloud\Plugin\Tools\Optimizer;

use MediaCloud\Plugin\Tools\ToolSettings;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;

/**
 * Class OptimizerToolSettings
 * @package MediaCloud\Mux
 *
 * @property-read bool $optimizeOriginal
 * @property-read bool $useWebhook
 * @property-read string $provider
 * @property-read string $webhookUrl
 * @property-read bool $backgroundMode
 * @property-read bool $backgroundOnlyMediaLibrary
 */
class OptimizerToolSettings extends ToolSettings {
	private $_webhookUrl = null;
	private $_backgroundMode = null;

	/**
	 * Map of property names to setting names
	 * @var string[]
	 */
	protected $settingsMap = [
		'optimizeOriginal' => ['mcloud-optimizer-optimize-original', null, false],
		'useWebhook' => ['mcloud-optimizer-use-webhook', null, false],
		'provider' => ['mcloud-optimizer-provider', null, 'kraken'],
		'webhookUrl' => ['mcloud-optimizer-webhook', null, null],
		'backgroundOnlyMediaLibrary' => ['mcloud-optimizer-background-media-library-only', null, true],
	];

	public function __get($name) {
		if ($name === 'webhookUrl') {
			if ($this->_webhookUrl === null) {
				$this->_webhookUrl = Environment::Option($this->settingsMap['webhookUrl'][0], null, null);
				if (empty($this->_webhookUrl)) {
					$this->_webhookUrl = home_url('/__mediacloud/hooks/optimize');
				} else if (strpos($this->_webhookUrl, '/') === 0) {
					$this->_webhookUrl = home_url($this->_webhookUrl);
				}

				return $this->_webhookUrl;
			}
		}

		if ($name === 'backgroundMode') {
			if ($this->_backgroundMode === null) {
				$this->_backgroundMode = Environment::Option('mcloud-optimizer-background-process', null, true);

				if (!empty($this->_backgroundMode)) {
					if ($this->backgroundOnlyMediaLibrary) {
						$this->_backgroundMode = false;
						$validUrls = [
							admin_url('media-new.php'),
							admin_url('upload.php'),
							admin_url('upload.php?page=media-tools-storage-browser'),
						];

						foreach($validUrls as $validUrl) {
							if (strpos(arrayPath($_SERVER, 'HTTP_REFERER', ''), $validUrl) === 0) {
								$this->_backgroundMode = true;
								break;
							}
						}
					}
				} else {
					$this->_backgroundMode = false;
				}

			}

			Logger::info("Background mode:".($this->_backgroundMode ? 'true' : 'false'), [], __METHOD__, __LINE__);
			return $this->_backgroundMode;
		}

		return parent::__get($name);
	}

	public function __isset($name) {
		if ($name === 'webhookUrl') {
			return true;
		}

		return parent::__isset($name);
	}
}