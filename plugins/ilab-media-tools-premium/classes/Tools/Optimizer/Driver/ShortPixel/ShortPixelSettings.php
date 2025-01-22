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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\ShortPixel;

use MediaCloud\Plugin\Tools\Optimizer\OptimizerToolSettings;

/**
 * Class OptimizerToolSettings
 * @package MediaCloud\Mux
 *
 * @property-read string $apiKey
 * @property string $lossy
 * @property bool $preserveExif
 * @property bool $uploadImage
 */
class ShortPixelSettings extends OptimizerToolSettings {

	public function __construct() {
		$this->settingsMap = array_merge($this->settingsMap, [
			'apiKey' => ['mcloud-optimizer-shortpixel-key', null, null],
			'uploadImage' => ['mcloud-optimizer-upload-image', null, false],
			'lossy' => ['mcloud-optimizer-shortpixel-lossy', null, 'lossy'],
			'preserveExif' => ['mcloud-optimizer-exif-preserve', null, false],
		]);
	}


}