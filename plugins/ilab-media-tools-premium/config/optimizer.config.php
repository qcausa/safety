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
	"id" => "optimizer",
	"name" => "Image Optimization",
	"description" => "Image optimization.",
	"class" => "MediaCloud\\Plugin\\Tools\\Optimizer\\OptimizerTool",
	"dynamic-config-option" => "mcloud-optimizer-provider",
	"dependencies" => [
		"storage",
		"!imgix",
	],
	"env" => "MCLOUD_OPTIMIZER_ENABLED",
	"optimizationDrivers" => [
		'kraken' => [
			'name' => 'Kraken.io',
			'class' => \MediaCloud\Plugin\Tools\Optimizer\Driver\KrakenIO\KrakenIODriver::class,
			'config' => '/optimizer/kraken.config.php',
			'help' => [
				[ 'title' => 'Sign Up For Kraken.io Account', 'external_url' => 'https://kraken.io/pricing?utm=media-cloud' ],
			]
		],
		'shortpixel' => [
			'name' => 'ShortPixel',
			'class' => \MediaCloud\Plugin\Tools\Optimizer\Driver\ShortPixel\ShortPixelDriver::class,
			'config' => '/optimizer/shortpixel.config.php',
			'help' => [
				[ 'title' => 'Sign Up For ShortPixel Account', 'external_url' => 'https://shortpixel.com/free-sign-up?utm=media-cloud' ],
			]
		],
		'imagify' => [
			'name' => 'Imagify',
			'class' => \MediaCloud\Plugin\Tools\Optimizer\Driver\Imagify\ImagifyDriver::class,
			'config' => '/optimizer/imagify.config.php',
			'help' => [
				[ 'title' => 'Sign Up For Imagify Account', 'external_url' => 'https://imagify.io/optimizer/?utm=media-cloud' ],
			]
		],
		'tinypng' => [
			'name' => 'TinyPNG',
			'class' => \MediaCloud\Plugin\Tools\Optimizer\Driver\TinyPNG\TinyPNGDriver::class,
			'config' => '/optimizer/tinypng.config.php',
			'help' => [
				[ 'title' => 'Sign Up For TinyPNG Account', 'external_url' => 'https://tinypng.com/developers' ],
			]
		],
	],
	"settings" => [
		"options-page" => "media-cloud-optimizer",
		"options-group" => "media-cloud-optimizer",
		"watch" => true,
		"groups" => [
			"mcloud-optimizer-stats" => [
				"title" => "Optimization Statistics",
				"custom" => true,
				"callback" => [\MediaCloud\Plugin\Tools\Optimizer\OptimizerTool::class, 'renderStats'],
				"options" => [
					"mcloud-optimizer-stats" => [
						"type" => "custom",
						"callback" => null,
					],
				],
				"hide-save" => true
			],
			"mcloud-optimizer-provider" => [
				"title" => "Image Optimization Provider",
				"help" => [
					'target' => 'footer',
					'watch' => 'mcloud-optimizer-provider',
					'data' => 'providerHelp',
				],
				"options" => [
					"mcloud-optimizer-provider" => [
						"title" => "Image Optimization Provider",
						"type" => "select",
						"description" => "Select the image optimization provider your are using.  If you don't have one you can sign up for a free account at any of the supported providers by clicking the sign up button below.",
						"options" => "providerOptions"
					],
				],
				"hide-save" => true
			],
			"mcloud-optimizer-provider-settings" => [
				"title" => "Provider Settings",
				"dynamic" => true,
				"options" => [],
				"doc_link" => 'https://docs.mediacloud.press/articles/documentation/image-optimization/provider-settings/',
			],
			"mcloud-optimizer-image-settings" => [
				"title" => "Image Settings",
				"dynamic" => true,
				"options" => []
			],
			"mcloud-optimizer-exif-settings" => [
				"title" => "Exif Settings",
				"dynamic" => true,
				"options" => []
			],
		],
	]
];
