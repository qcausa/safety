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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WPAllImportPro;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool enabled
 * @property int taskDelay
 */
class WPAllImportProSettings extends ToolSettings {
	protected $settingsMap = [
		"enabled" => ['mcloud-wp-all-import-enabled', null, true],
		"taskDelay" => ['mcloud-wp-all-import-task-delay', null, 1],
	];
}