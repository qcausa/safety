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
			"mcloud-optimizer-shortpixel-key" => [
				"title" => "API Key",
				"display-order" => 1,
				"description" => "The API key you can find in your <a href='https://shortpixel.com/show-api-key' target='_blank'>Short Pixel account</a>.",
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
			"mcloud-optimizer-shortpixel-lossy" => [
				"title" => "Lossy Compression",
				"description" => "Controls whether the image compression will use a lossy or lossless algorithm. <strong>Lossy</strong> has a better compression rate than lossless compression. <strong>Glossy</strong> is a lossy compression that is produces near identical to the original, best option where quality is more important than compression.  <strong>Lossless</strong> will be identical, but the compression savings compared to the others might not be much.",
				"display-order" => 2,
				"type" => "select",
				"options" => [
					'lossy' => 'Lossy',
					'glossy' => 'Glossy',
					'lossless' => 'Lossless',
				],
				"default" => 'lossy'
			],
		]
	],
	"mcloud-optimizer-exif-settings" => [
		"title" => "Exif Settings",
		"dynamic" => true,
		"options" => [
			"mcloud-optimizer-exif-preserve" => [
				"title" => "Keep EXIF Data",
				"description" => "Retains all of the EXIF data in the original image.",
				"display-order" => 1,
				"type" => "checkbox",
				"default" => false,
			],
		]
	],
];