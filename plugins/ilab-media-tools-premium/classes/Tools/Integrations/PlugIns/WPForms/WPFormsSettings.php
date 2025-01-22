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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WPForms;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool enabled
 * @property bool randomFilename
 * @property bool deleteUploads
 * @property bool deleteFromStorage
 * @property string acl
 * @property string prefix
 */
class WPFormsSettings extends ToolSettings {
	protected $settingsMap = [
		"enabled" => ['mcloud-wp-forms-enabled', null, true],
		"prefix" => ['mcloud-wp-forms-prefix', null, null],
		"randomFilename" => ['mcloud-wp-forms-random-filename', null, false],
		"deleteUploads" => ['mcloud-wp-forms-delete-uploads', null, false],
		"deleteFromStorage" => ['mcloud-wp-forms-delete-storage', null, false],
		"acl" => ['mcloud-wp-forms-privacy', null, 'public-read'],
	];
}