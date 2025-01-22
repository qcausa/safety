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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool enabled
 * @property bool deleteUploads
 * @property bool realtimeProcessing
 */
class BuddyPressSettings extends ToolSettings {
	protected $settingsMap = [
		"enabled" => ['mcloud-buddypress-enabled', null, true],
		"deleteUploads" => ['mcloud-buddypress-delete-uploads', null, false],
		"realtimeProcessing" => ['mcloud-buddypress-realtime', null, true],
	];
}