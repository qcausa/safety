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
	"id" => "image-sizes",
	"name" => "Image Sizes",
	"description" => "Tool for managing image sizes.",
	"class" => "MediaCloud\\Plugin\\Tools\\ImageSizes\\ImageSizeTool",
	"exclude" => true,
	"dependencies" => [],
	"env" => "ILAB_MEDIA_IMAGESIZES_ENABLED",  // this is always enabled btw
	"CLI" => [
		\MediaCloud\Plugin\Tools\ImageSizes\CLI\ImageSizeCommands::class
	],
	"settings" => [
		"options-group" => "media-cloud-options-group-image-sizes",
	]
];