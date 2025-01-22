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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery;

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery\Tasks\MigrateNextGenTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class NextGenGalleryIntegration {
	/** @var StorageTool|null $storageTool */
	private $storageTool = null;

	/** @var DynamicImagesTool|null $dynamicImagesTool */
	private $dynamicImagesTool = null;

	private static $urlCache = null;

	private $useCache = true;

	public function __construct() {
		if (is_admin()) {
			TaskManager::registerTask(MigrateNextGenTask::class);
		}

		$this->storageTool = ToolsManager::instance()->tools['storage'];
		$this->dynamicImagesTool = DynamicImagesTool::currentDynamicImagesTool();

		if ($this->storageTool->enabled()) {
			add_filter('ngg_get_image_url', [$this, 'getImageUrl'], 1000, 3);
			add_action('ngg_added_new_image', [$this, 'importImage'], 1000, 1);

			if (static::$urlCache == null) {
				static::$urlCache = get_option('ilab-ngg-static-url-cache', []);
			}

			$this->useCache = Environment::Option('mcloud-ngg-use-url-cache', null, true);
		}


		if (!wp_doing_ajax()) {
			NextGenGalleryUtilities::init();
		}
	}

	protected function fetchImage($image, $size) {
		global $wpdb;
		$tableName = $wpdb->base_prefix.'mcloud_ngg_data';
		/** @var array|null $imageData */
		$imageData = $wpdb->get_row($wpdb->prepare("select * from {$tableName} where pid=%d and size=%s", $image->pid, $size), ARRAY_A);
		if (empty($imageData)) {
			return null;
		}

		$provider = $imageData['provider'];
		$bucket = $imageData['bucket'];
		$key = $imageData['s3key'];
		$url = $imageData['url'];

		if (($provider === StorageToolSettings::driver()) && ($bucket === $this->storageTool->client()->bucket())) {
			if (!empty($this->dynamicImagesTool)) {
				return $this->dynamicImagesTool->urlForStorageMedia($key);
			}

			$newUrl =  $this->storageTool->getAttachmentURLFromMeta([
				'file' => $image->filename,
				's3' => [
					'key' => $key,
					'bucket' => $bucket,
					'url' => $url
				]
			]);

			if (!empty($newUrl)) {
				return $newUrl;
			}
		}

		return $url;
	}

	public function getImageUrl($url, $image, $size) {
		$galleryPath = @\C_NextGen_Settings::get_instance()->gallerypath;
		if (empty($galleryPath)) {
			return $url;
		}

		// TODO: Migrate this away
		if (isset(static::$urlCache[$image->pid.$size])) {
			return static::$urlCache[$image->pid.$size];
		}

		$newUrl = $this->fetchImage($image, $size);
		if (!empty($newUrl)) {
			return $newUrl;
		}

		$imported = NextGenGalleryUtilities::importImage($image, $size);
		if (isset($imported[$size])) {
			return $imported[$size];
		}

		return $url;
	}

}
