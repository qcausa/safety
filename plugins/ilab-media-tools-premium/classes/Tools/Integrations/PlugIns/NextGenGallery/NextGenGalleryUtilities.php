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
use MediaCloud\Plugin\Tasks\TaskSchedule;
use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Tools\Storage\Tasks\DeleteUploadsTask;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Prefixer;

final class NextGenGalleryUtilities {
	const DB_VERSION = '1.0.0';

	/**
	 * Insures the additional database tables are installed
	 */
	public static function init() {
		static::installDataTable();
	}


	//region Install Database Tables

	protected static function installDataTable() {
		$currentVersion = get_site_option('mcloud_ngg_data_db_version');
		if (!empty($currentVersion) && version_compare(self::DB_VERSION, $currentVersion, '<=')) {
			return;
		}

		global $wpdb;

		$tableName = $wpdb->base_prefix.'mcloud_ngg_data';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tableName} (
	id BIGINT AUTO_INCREMENT,
	pid BIGINT NOT NULL,
	size varchar(255) NOT NULL,
	provider varchar(32) NOT NULL,
	bucket varchar(255) NOT NULL,
	s3key varchar(512) NOT NULL,
	url varchar(512) NOT NULL,
	PRIMARY KEY  (id),
	KEY pid(pid),
	KEY size(size(255))
) {$charset};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		$exists = ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName);
		if ($exists) {
			update_site_option('mcloud_ngg_data_db_version', self::DB_VERSION);
		}
	}

	//endregion

	//region Upload and Import

	public static function uploadImage($pid, $size, $galleryRoot, $galleryPath, $filename) {
		$prefix = Environment::Option('mcloud-ngg-storage-prefix', null, null);

		$storageTool = ToolsManager::instance()->tools['storage'];
		$dynamicImagesTool = DynamicImagesTool::currentDynamicImagesTool();

		$fullPath = realpath(trailingslashit($galleryRoot).trailingslashit($galleryPath).$filename);
		if (!file_exists($fullPath)) {
			return null;
		}

		$uploadPath = $galleryPath;
		if (!empty($prefix)) {
			$uploadPath = Prefixer::Parse($prefix);
		}

		$fullKey = trailingslashit($uploadPath).$filename;
		if ($storageTool->client()->exists($fullKey)) {
			$fullUrl = $storageTool->client()->url($fullKey, 'image');
		} else {
			$fullUrl = $storageTool->client()->upload($fullKey, $fullPath, StorageToolSettings::privacy());
		}

		if (!empty($fullUrl)) {
			$data = ['pid' => $pid, 'size' => $size, 'provider' => StorageToolSettings::driver(), 'bucket' => $storageTool->client()->bucket(), 's3Key' => $fullKey, 'url' => $fullUrl];

			global $wpdb;
			$tableName = $wpdb->base_prefix . 'mcloud_ngg_data';
			$inserted = $wpdb->insert($tableName, $data, ['%d', '%s', '%s', '%s', '%s', '%s']);
			if($inserted !== false) {

				if(!empty($dynamicImagesTool)) {
					$finalUrl = $dynamicImagesTool->urlForStorageMedia($fullKey);
				} else {
					$finalUrl = $storageTool->getAttachmentURLFromMeta(['file' => $filename, 's3' => ['key' => $fullKey, 'bucket' => $storageTool->client()->bucket(), 'url' => $fullUrl]]);
				}

				if (Environment::Option('mcloud-ngg-delete-uploads', null, false)) {
					if (Environment::Option('mcloud-ngg-queue-delete-uploads', null, true)) {
						$task = TaskSchedule::nextScheduledTaskOfType(DeleteUploadsTask::identifier());
						if (!empty($task)) {
							$task->selection = array_merge($task->selection, [$fullPath]);
							$task->save();
						} else {
							DeleteUploadsTask::scheduleIn(StorageToolSettings::queuedDeletesDelay(), [], [$fullPath]);
						}
					} else {
						unlink($fullPath);
					}
				}

				return empty($finalUrl) ? $fullUrl : $finalUrl;
			}
		}

		return null;
	}

	public static function importImage($image, $size = null) {
		global $wpdb;
		$galleryPath = $wpdb->get_var("select path from {$wpdb->prefix}ngg_gallery where gid={$image->galleryid}");
		if (empty($galleryPath)) {
			return null;
		}

		$galleryRoot = (defined('NGG_GALLERY_ROOT_TYPE') && (NGG_GALLERY_ROOT_TYPE == 'content')) ? WP_CONTENT_DIR : ABSPATH;

		$result = [];

		if (empty($size) || ($size === 'full')) {
			$fullUrl = static::uploadImage($image->pid, 'full', $galleryRoot, $galleryPath, $image->filename);
			if (!empty($fullUrl)) {
				$result['full'] = $fullUrl;
			}
		}

		if (empty($size) || ($size === 'thumb') || ($size === 'thumbnail')) {
			$size = (empty($size)) ? 'thumb' : $size;
			if(isset($image->meta_data['thumbnail'])) {
				$thumbUrl = static::uploadImage($image->pid, $size, $galleryRoot, trailingslashit($galleryPath).'thumbs', $image->meta_data['thumbnail']['filename']);
				if(!empty($thumbUrl)) {
					$result[$size] = $thumbUrl;
				}
			}
		}

		return $result;
	}


	//endregion
}
