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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns;

use MediaCloud\Plugin\Tools\Storage\StorageConstants;
use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class MetaSliderIntegeration {
	/** @var StorageTool|null $storageTool */
	private $storageTool = null;

	/** @var DynamicImagesTool|null $dynamicImagesTool */
	private $dynamicImagesTool = null;

	public function __construct() {
		add_action('metaslider_after_resize_image', [$this, 'handleImageResize'], 1000, 4);
		add_filter('metaslider_resized_image_url', [$this, 'handleResizedImageUrl'], 1000, 2);

		$this->storageTool = ToolsManager::instance()->tools['storage'];
		$this->dynamicImagesTool = DynamicImagesTool::currentDynamicImagesTool();
	}

	public function handleImageResize($imageId, $width, $height, $url) {
		$path = parse_url($url, PHP_URL_PATH);
		$uploadDir = wp_get_upload_dir();

		$localFile = $uploadDir['basedir'].$path;
		if (file_exists($localFile)) {
			$key = ltrim($path, '/');
			if (!$this->storageTool->client()->exists($key)) {
				$this->storageTool->client()->upload($key, $localFile, StorageConstants::ACL_PUBLIC_READ);
			}
		}
	}

	public function handleResizedImageUrl($destUrl, $sourceUrl) {
		if (!empty($this->dynamicImagesTool)) {
			if (preg_match('/(-[0-9x]+)\.(?:jpg|gif|png)/m', $destUrl, $sizeMatches)) {
				$cleanedUrl = str_replace($sizeMatches[1], '', $destUrl);
				$id = attachment_url_to_postid($cleanedUrl);
				$textSize = trim($sizeMatches[1], '-');
				$size = explode('x', $textSize);

				if (!empty($id)) {
					$url = $this->dynamicImagesTool->buildSizedImage($id, $size);
					if (array($url)) {
						return $url[0];
					}
				}
			}
		}

		return $destUrl;
	}
}