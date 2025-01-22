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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\KrakenIO;

use MediaCloud\Plugin\Tools\Optimizer\OptimizerToolSettings;

/**
 * Class OptimizerToolSettings
 * @package MediaCloud\Mux
 *
 * @property bool $uploadImage
 *
 * @property-read string $apiKey
 * @property-read string $apiSecret
 * @property bool $lossy
 * @property int $lossyQuality
 * @property string $subsampling
 * @property bool $preserveProfile
 * @property bool $preserveDate
 * @property bool $preserveCopyright
 * @property bool $preserveGeotag
 * @property bool $preserveOrientation
 */
class KrakenIOSettings extends OptimizerToolSettings {

	public function __construct() {
		$this->settingsMap = array_merge($this->settingsMap, [
			'apiKey' => ['mcloud-optimizer-kraken-key', null, null],
			'apiSecret' => ['mcloud-optimizer-kraken-secret', null, null],
			'uploadImage' => ['mcloud-optimizer-upload-image', null, false],
			'useWebhook' => ['mcloud-optimizer-kraken-use-webhook', null, false],
			'lossy' => ['mcloud-optimizer-lossy', null, false],
			'lossyQuality' => ['mcloud-optimizer-quality', null, 75],
			'subsampling' => ['mcloud-optimizer-chroma', null, '420'],
			'preserveProfile' => ['mcloud-optimizer-exif-preserve-profile', null, false],
			'preserveDate' => ['mcloud-optimizer-exif-preserve-date', null, false],
			'preserveCopyright' => ['mcloud-optimizer-exif-preserve-copyright', null, false],
			'preserveGeotag' => ['mcloud-optimizer-exif-preserve-geotag', null, false],
			'preserveOrientation' => ['mcloud-optimizer-exif-preserve-orientation', null, false],
		]);
	}


}