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
	"mcloud-optimizer-provider-settings" => [
		"title" => "Provider Settings",
		"dynamic" => true,
		"doc_link" => 'https://docs.mediacloud.press/articles/documentation/image-optimization/provider-settings/',
		"options" => [
			"mcloud-optimizer-tinypng-key" => [
				"title" => "API Key",
				"display-order" => 1,
				"description" => "The API key you can find in your <a href='https://tinypng.com/dashboard/api' target='_blank'>TinyPNG account</a>.",
				"type" => "text-field",
			],
			"mcloud-optimizer-upload-image" => [
				"title" => "Upload Image",
				"display-order" => 5,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-background-process" => [
				"title" => "Optimize in Background",
				"display-order" => 6,
				"type" => "checkbox",
				"default" => true,
			],
			"mcloud-optimizer-background-media-library-only" => [
				"title" => "Background Only When Using the Media Library",
				"display-order" => 7,
				"type" => "checkbox",
				"default" => true,
			],
		]
	],
	"mcloud-optimizer-image-settings" => [
		"title" => "Image Settings",
		"dynamic" => true,
		"options" => [
			"mcloud-optimizer-optimize-original" => [
				"title" => "Optimize Original Image",
				"display-order" => 1,
				"type" => "checkbox",
				"default" => false,
			],
		]
	],
	"mcloud-optimizer-exif-settings" => [
		"title" => "Exif Settings",
		"dynamic" => true,
		"options" => [
			"mcloud-optimizer-exif-preserve-date" => [
				"title" => "Preserve Date",
				"description" => "Will preserve image creation date.",
				"display-order" => 2,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-exif-preserve-copyright" => [
				"title" => "Preserve Copyright",
				"description" => "Will preserve copyright entries.",
				"display-order" => 3,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-exif-preserve-geotag" => [
				"title" => "Preserve Location",
				"description" => "Will preserve location-specific information.",
				"display-order" => 4,
				"type" => "checkbox",
				"default" => false,
			],
		]
	],
];