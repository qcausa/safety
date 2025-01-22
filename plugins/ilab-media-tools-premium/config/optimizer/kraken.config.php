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
			"mcloud-optimizer-kraken-key" => [
				"title" => "API Key",
				"display-order" => 1,
				"description" => "The API key you can find on your <a href='https://kraken.io/account/api-credentials' target='_blank'>Kraken API Dashboard</a>.",
				"type" => "text-field",
			],
			"mcloud-optimizer-kraken-secret" => [
				"title" => "API Secret",
				"display-order" => 2,
				"description" => "The API secret you can find on your <a href='https://kraken.io/account/api-credentials' target='_blank'>Kraken API Dashboard</a>.",
				"type" => "password",
			],
			"mcloud-optimizer-upload-image" => [
				"title" => "Upload Image",
				"description" => "When enabled, the image to optimize is uploaded to the service when uploaded to WordPress.  When it's disabled, the URL to the image is sent to the service.",
				"display-order" => 5,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-background-process" => [
				"title" => "Optimize in Background",
				"description" => "When enabled, all image optimizations are queued to a background task that will happen with a few minutes of upload.  If you are having problems with background tasks running on your server, you should disable this.  Otherwise, this is the preferred mode of operation.",
				"display-order" => 6,
				"type" => "checkbox",
				"default" => true,
			],
			"mcloud-optimizer-background-media-library-only" => [
				"title" => "Background Only When Using the Media Library",
				"description" => "When <strong>Optimize in Background</strong> is enabled, only do image optimization in the background when using WordPress's Media Library pages.  On any other pages, the image optimization will be run during the upload.",
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
				"description" => "When enabled, the original master image will be optimized.  This only affects image uploads where the uploaded image is bigger than WordPress 5.3's big image threshold (by default, images larger than 2560px).  These images are never displayed to the end user, so optimizing them would be pointless.",
				"display-order" => 1,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-lossy" => [
				"title" => "Lossy Compression",
				"description" => "Uses lossy compression when optimizing images for better compression savings with some image quality loss.",
				"display-order" => 2,
				"type" => "checkbox",
				"default" => false,
			],
			"mcloud-optimizer-quality" => [
				"title" => "Lossy Quality",
				"description" => "Uses lossy compression when optimizing images for better compression savings with some image quality loss.  Set to <strong>0</strong> to use Kraken's intelligent lossy quality.",
				"display-order" => 3,
				"type" => "number",
				"min" => 0,
				"max" => 100,
				"default" => 0,
			],
			"mcloud-optimizer-chroma" => [
				"title" => "Chroma Subsampling",
				"display-order" => 4,
				"description" => "Controls the chroma subsampling used for lossy compression.",
				"type" => "select",
				"options" => [
					'420' => "4:2:0 (Best Compression)",
					'422' => '4:2:2',
					'444' => '4:4:4 (Highest Quality)',
				],
			],
		]
	],
	"mcloud-optimizer-exif-settings" => [
		"title" => "Exif Settings",
		"dynamic" => true,
		"options" => [
			"mcloud-optimizer-exif-preserve-profile" => [
				"title" => "Preserve ICC Profile",
				"description" => "Will preserve the ICC colour profile. ICC colour profile information adds unnecessary bloat to images. However, preserving it can be necessary in extremely rare cases where removing this information could lead to a change in brightness and/or saturation of the resulting file.",
				"display-order" => 1,
				"type" => "checkbox",
				"default" => false,
			],
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
			"mcloud-optimizer-exif-preserve-orientation" => [
				"title" => "Preserve Orientation",
				"description" => "Will preserve the orientation (rotation) mark.",
				"display-order" => 5,
				"type" => "checkbox",
				"default" => false,
			],
		]
	],
];