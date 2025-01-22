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

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Prefixer;
use WP_Thumb;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class FooGalleryIntegration {
	/** @var StorageTool|null $storageTool */
	private $storageTool = null;

	/** @var DynamicImagesTool|null $dynamicImagesTool */
	private $dynamicImagesTool = null;

	private $liveMigrations = false;


	public function __construct() {
		if (ToolsManager::instance()->toolEnabled('storage')) {
			$this->storageTool = ToolsManager::instance()->tools['storage'];
			$this->dynamicImagesTool = DynamicImagesTool::currentDynamicImagesTool();

			$this->liveMigrations = Environment::Option('mcloud-foogallery-live-migration', null, true);

			add_filter('foogallery_attachment_resize_thumbnail', [$this, 'resize'], PHP_INT_MAX, 3);
			add_action('wpthumb_saved_cache_image', [$this, 'uploadCachedFile'], PHP_INT_MAX, 1);
		}
	}

	/**
	 * @param $original_image_src
	 * @param $args
	 * @param \FooGalleryAttachment $thumbnail_object
	 *
	 * @return mixed
	 *
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	function resize($original_image_src, $args, $thumbnail_object, $level = 0) {
		$sizes = get_post_meta($thumbnail_object->ID, 'mcloud_foo_thumbs', true);
		if (empty($sizes) || !is_array($sizes)) {
			if ($this->liveMigrations && ($level === 0)) {
				$thumbArgs = $args;
				$thumbArgs['foogallery_attachment_id'] = $thumbnail_object->ID;

				$thumb = new WP_Thumb($original_image_src, $thumbArgs);
				if (file_exists($thumb->getCacheFilePath())) {
					$this->uploadCachedFile($thumb);
					return $this->resize($original_image_src, $args, $thumbnail_object, 1);
				}
			}

			return $original_image_src;
		}

		$sizeW = isset($args['width']) ? (int)$args['width'] : (int)0;
		$sizeH= isset($args['height']) ? (int)$args['height'] : (int)0;
		$crop = empty($args['crop']) ? (int)0 : 1;
		$sizeKey = "{$sizeW}_{$sizeH}_{$crop}";

		if (!isset($sizes[$sizeKey])) {
			return $original_image_src;
		}

		$size = $sizes[$sizeKey];
		if (($size['s3']['provider'] === StorageToolSettings::driver()) && ($size['s3']['bucket'] === $this->storageTool->client()->bucket())) {
			if (!empty($this->dynamicImagesTool)) {
				return $this->dynamicImagesTool->urlForStorageMedia($size['s3']['key']);
			}

			$newUrl =  $this->storageTool->getAttachmentURLFromMeta($size);

			if (!empty($newUrl)) {
				return $newUrl;
			}
		}

		return $size['s3']['url'];
	}

	/**
	 * @param WP_Thumb $thumb
	 *
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	function uploadCachedFile($thumb) {
		$wpUploadDir = wp_upload_dir();
		$base = $wpUploadDir['basedir'];
		$cachedFile = $thumb->getCacheFilePath();
		$thumbKey = ltrim(str_replace($base, '', $cachedFile), '/');
		$thumbFile = pathinfo($cachedFile, PATHINFO_FILENAME);

		$prefix = Environment::Option('mcloud-foogallery-storage-prefix', null, null);
		if (!empty($prefix)) {
			$newPrefix = Prefixer::Parse($prefix);
			$thumbKey = trailingslashit($newPrefix).$thumbFile;
		}

		$url = $this->storageTool->client()->upload($thumbKey, $cachedFile, StorageToolSettings::privacy(), StorageToolSettings::cacheControl(), StorageToolSettings::expires());

		$sizeW = (int)$thumb->getArg('width');
		$sizeH = (int)$thumb->getArg('height');
		$crop = empty($thumb->getArg('crop')) ? (int)0 : 1;
		$sizeKey = "{$sizeW}_{$sizeH}_{$crop}";

		$attachId = $thumb->getArg('foogallery_attachment_id');
		$sizes = get_post_meta($attachId, 'mcloud_foo_thumbs', true);
		if (!is_array($sizes)) {
			$sizes = [];
		}

		$sizes[$sizeKey] = [
			'file' => $thumbFile,
			's3' => [
				'provider' => StorageToolSettings::driver(),
				'bucket' => $this->storageTool->client()->bucket(),
				'key' => $thumbKey,
				'url' => $url
			]
		];

		update_post_meta($attachId, 'mcloud_foo_thumbs', $sizes);
	}
}


