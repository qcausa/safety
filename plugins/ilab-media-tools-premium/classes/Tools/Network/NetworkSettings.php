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

namespace MediaCloud\Plugin\Tools\Network;

use MediaCloud\Plugin\Tools\ToolSettings;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * @property bool hideMediaCloud
 * @property bool hideTaskManager
 * @property bool hideBatchTools
 */
class NetworkSettings extends ToolSettings {
	/**
	 * Map of property names to setting names
	 * @var string[]
	 */
	protected $settingsMap = [
		"hideMediaCloud" => ["media-cloud-network-hide", null, false],
		"hideTaskManager" => ["media-cloud-task-manager-hide", null, true],
		"hideBatchTools" => ["mcloud-network-hide-batch", null, false],
	];
}