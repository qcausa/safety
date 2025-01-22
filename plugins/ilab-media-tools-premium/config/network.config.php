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

if (!defined('ABSPATH')) { header('Location: /'); die; }

return [
    "id" => "network",
    "name" => "Network",
    "description" => "Settings for WordPress Multisite Network.",
    "class" => "MediaCloud\\Plugin\\Tools\\Network\\NetworkTool",
    "dependencies" => [],
    "env" => "ILAB_MEDIA_CLOUD_NETWORK_ENABLED",
    "settings" => [
        "options-page" => "media-cloud-network-settings",
        "options-group" => "media-cloud-network-settings-group",
        "groups" => [
            "media-cloud-network-general-settings" => [
                "title" => "General Settings",
                "options" => [
	                "media-cloud-network-hide" => [
		                "title" => "Hide Media Cloud",
		                "description" => "Hides Media Cloud completely on child sites in this network.",
		                "type" => "checkbox",
		                "default" => false
	                ],
	                "media-cloud-task-manager-hide" => [
		                "title" => "Hide Task Manager",
		                "description" => "Hides Media Cloud's Task Manager completely on child sites in this network.",
		                "type" => "checkbox",
		                "default" => true
	                ],
	                "mcloud-network-hide-batch" => [
		                "title" => "Hide Batch Tools",
		                "description" => "Hides all batch tools on child sites in this network.",
		                "type" => "checkbox",
		                "default" => false
	                ],
                ],
            ],
        ]
    ]
];
