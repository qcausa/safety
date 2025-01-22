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
			"mcloud-optimizer-imagify-key" => [
				"title" => "Access Token",
				"display-order" => 1,
				"description" => "The access token you can find in your <a href='https://app.imagify.io/#/api' target='_blank'>Imagify account</a>.",
				"type" => "text-field",
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
			"mcloud-optimizer-imagify-lossy" => [
				"title" => "Lossy Compression",
				"description" => "The compression algorithm to use.",
				"display-order" => 2,
				"type" => "select",
				"options" => [
					'normal' => 'Lossless',
					'aggressive' => 'Agressive',
					'ultra' => 'Ultra',
				],
				"default" => 'normal'
			],
		]
	],
	"mcloud-optimizer-exif-settings" => [
		"title" => "Exif Settings",
		"dynamic" => true,
		"options" => [
			"mcloud-optimizer-exif-preserve" => [
				"title" => "Keep EXIF Data",
				"display-order" => 1,
				"type" => "checkbox",
				"default" => false,
			],
		]
	],
];