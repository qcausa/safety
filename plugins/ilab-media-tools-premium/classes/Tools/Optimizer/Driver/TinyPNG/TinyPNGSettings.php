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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\TinyPNG;

use MediaCloud\Plugin\Tools\Optimizer\OptimizerToolSettings;

/**
 * Class OptimizerToolSettings
 * @package MediaCloud\Mux
 *
 * @property-read string $apiKey
 * @property bool $preserveDate
 * @property bool $preserveCopyright
 * @property bool $preserveGeotag
 * @property bool $uploadImage
 */
class TinyPNGSettings extends OptimizerToolSettings {

	public function __construct() {
		$this->settingsMap = array_merge($this->settingsMap, [
			'apiKey' => ['mcloud-optimizer-tinypng-key', null, null],
			'uploadImage' => ['mcloud-optimizer-upload-image', null, false],
			'preserveDate' => ['mcloud-optimizer-exif-preserve-date', null, false],
			'preserveCopyright' => ['mcloud-optimizer-exif-preserve-copyright', null, false],
			'preserveGeotag' => ['mcloud-optimizer-exif-preserve-geotag', null, false],
		]);
	}


}