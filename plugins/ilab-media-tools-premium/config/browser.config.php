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
	"id" => "browser",
	"name" => "Storage Browser",
	"description" => "Tool for browsing cloud storage.",
	"class" => "MediaCloud\\Plugin\\Tools\\Browser\\BrowserTool",
	"exclude" => true,
	"dependencies" => [],
	"env" => "ILAB_MEDIA_STORAGE_BROWSER_ENABLED",  // this is always enabled btw
	"network_settings" => [
		"options-group" => "ilab-media-browser",
		"groups" => [
			"media-cloud-network-browser-settings" => [
				"title" => "Storage Browser Settings",
				"options" => [
					"mcloud-network-browser-hide" => [
						"title" => "Hide Storage Browser",
						"description" => "Hides the storage browser completely on child sites in this network.",
						"type" => "checkbox",
						"default" => false
					],
					"mcloud-network-browser-lock-to-root" => [
						"title" => "Lock to Site Directory",
						"description" => "Lock down the browser to the site's directory and it's subdirectories, preventing the users of the site to see the entire storage bucket.",
						"type" => "checkbox",
						"default" => true
					],
					"mcloud-network-browser-allow-uploads" => [
						"title" => "Allow Uploads",
						"description" => "Allow users to upload through the storage browser.  This requires the Direct Upload feature to be enabled.",
						"type" => "checkbox",
						"default" => true
					],
					"mcloud-network-browser-allow-deleting" => [
						"title" => "Allow Deleting",
						"description" => "Allow users to delete items in the storage browser.",
						"type" => "checkbox",
						"default" => true
					],
				],
			],
		]
	],
	"settings" => [
		"options-group" => "media-cloud-options-group-browser",
	]
];