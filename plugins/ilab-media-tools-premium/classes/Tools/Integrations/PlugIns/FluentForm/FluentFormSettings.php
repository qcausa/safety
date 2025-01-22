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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * @property bool enabled
 * @property bool randomFilename
 * @property string acl
 */
class FluentFormSettings extends ToolSettings {
	protected $settingsMap = [
		"enabled" => ['mcloud-fluentforms-enabled', null, true],
		"randomFilename" => ['mcloud-fluentforms-random-filename', null, true],
		"acl" => ['mcloud-fluentforms-privacy', null, 'public-read'],
	];
}