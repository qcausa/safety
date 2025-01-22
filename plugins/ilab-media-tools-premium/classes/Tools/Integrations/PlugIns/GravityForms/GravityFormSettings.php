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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool enabled
 * @property bool randomFilename
 * @property bool deleteUploads
 * @property bool deleteFromStorage
 * @property string acl
 */
class GravityFormSettings extends ToolSettings {
	protected $settingsMap = [
		"enabled" => ['mcloud-gravity-forms-enabled', null, true],
		"randomFilename" => ['mcloud-gravity-forms-random-filename', null, false],
		"deleteUploads" => ['mcloud-gravity-forms-delete-uploads', null, false],
		"deleteFromStorage" => ['mcloud-gravity-forms-delete-storage', null, false],
		"acl" => ['mcloud-gravity-forms-privacy', null, 'public-read'],
	];
}