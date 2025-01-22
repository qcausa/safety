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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress;

use MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\Tasks\BuddyPressDeleteTask;
use MediaCloud\Plugin\Tools\Storage\StorageGlobals;
use MediaCloud\Plugin\Tasks\TaskSchedule;
use MediaCloud\Plugin\Tools\Imgix\ImgixTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;

/**
 * Maps buddy press member avatar and cover image URLs
 * @package MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress
 */
final class BuddyPressMap {
	const DB_VERSION = '1.0.0';
	const DB_KEY = 'mcloud_buddypress_db_version';
	const TABLE_NAME = 'mcloud_buddypress_map';

	private static $installed = false;
	private static $urlCache = [];
	private static $objectCache = [];

	private static $scheme = null;

	/**
	 * Insures the additional database tables are installed
	 */
	public static function init() {
		if (static::$scheme === null) {
			static::$scheme = parse_url(home_url(), PHP_URL_SCHEME).'://';
		}

		static::verifyInstalled();
	}

	//region Install Database Tables

	protected static function verifyInstalled() {
		if (static::$installed === true) {
			return true;
		}

		$currentVersion = get_site_option(self::DB_KEY);
		if (!empty($currentVersion) && version_compare(self::DB_VERSION, $currentVersion, '==')) {
			global $wpdb;

			$tableName = $wpdb->base_prefix.self::TABLE_NAME;
			$exists = ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName);
			if ($exists) {
				static::$installed = true;
				return true;
			}
		}

		return static::installMapTable();
	}

	protected static function installMapTable() {
		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tableName} (
	id BIGINT AUTO_INCREMENT,
	file_deleted SMALLINT NOT NULL DEFAULT 0,
	object_key VARCHAR(255) NULL,
	post_url VARCHAR(255) NULL,
	file_path varchar(512) NOT NULL,
	s3_info TEXT NOT NULL,
	PRIMARY KEY  (id),
	KEY post_url(post_url(255)),
	KEY object_key(object_key(255)),
	KEY file_deleted(file_deleted)
) {$charset};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		$exists = ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName);
		if ($exists) {
			update_site_option(self::DB_KEY, self::DB_VERSION);
			static::$installed = true;
			return true;
		}

		static::$installed = false;
		return false;
	}

	//endregion

	//region URL Generation

	/**
	 * @param array $s3Info
	 *
	 * @return string|null
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	private static function generateUrl($s3Info) {
		$imgixEnabled = apply_filters('media-cloud/imgix/enabled', false);

		if ($imgixEnabled) {
			/** @var ImgixTool $imgixTool */
			$imgixTool = ToolsManager::instance()->tools['imgix'];

			$params = apply_filters('media-cloud/integrations/buddypress/profile/imgix-params', []);

			return $imgixTool->urlForStorageMedia($s3Info['key'], $params);
		} else {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			if($storageTool->client()->usesSignedURLs('image')) {
				$url = $storageTool->client()->url($s3Info['key'], 'image');
				if(!empty(StorageGlobals::cdn())) {
					$cdnScheme = parse_url(StorageGlobals::cdn(), PHP_URL_SCHEME);
					$cdnHost = parse_url(StorageGlobals::cdn(), PHP_URL_HOST);

					$urlScheme = parse_url($url, PHP_URL_SCHEME);
					$urlHost = parse_url($url, PHP_URL_HOST);

					return str_replace("{$urlScheme}://{$urlHost}", "{$cdnScheme}://{$cdnHost}", $url);
				} else {
					return $url;
				}
			} else if(!empty(StorageGlobals::cdn())) {
				return StorageGlobals::cdn() . '/' . $s3Info['key'];
			}

			return $s3Info['url'];
		}
	}

	//endregion

	//region URL Mapping

	/**
	 * Maps a buddypress URL to a cloud storage url.
	 * @param string $url
	 * @param string $objectKey
	 *
	 * @return string|null
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	public static function mapURL($url, $objectKey = null) {
		$url = str_replace((static::$scheme === 'https://') ? 'http://' : 'https://', static::$scheme, $url);

		if (!empty($objectKey) && isset(static::$objectCache[$objectKey])) {
			return static::$objectCache[$objectKey];
		}

		if (isset(static::$urlCache[$url])) {
			return static::$urlCache[$url];
		}

		if (!static::verifyInstalled()) {
			return null;
		}

		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;

		if (!empty($objectKey)) {
			if (!empty($url)) {
				$query = $wpdb->prepare("select s3_info from {$tableName} where object_key = %s and post_url = %s", $objectKey, $url);
			} else {
				$query = $wpdb->prepare("select s3_info from {$tableName} where object_key = %s", $objectKey);
			}
		} else {
			$query = $wpdb->prepare("select s3_info from {$tableName} where post_url = %s", $url);
		}

		$s3_raw_info = $wpdb->get_var($query);
		if (empty($s3_raw_info)) {
			return null;
		}

		$s3Info = unserialize($s3_raw_info);
		if (empty($s3Info)) {
			return null;
		}

		$newUrl = static::generateUrl($s3Info);
		if (!empty($newUrl)) {
			if (!empty($url)) {
				static::$urlCache[$url] = $newUrl;
			}

			if (!empty($objectKey)) {
				static::$objectCache[$objectKey] = $newUrl;
			}
		}

		return $newUrl;
	}

	/**
	 * Copies a mapping from one key to another
	 *
	 * @param $oldObjectKey
	 * @param $newObjectKey
	 */
	public static function copyMap($oldObjectKey, $newObjectKey) {
		if (!static::verifyInstalled()) {
			return;
		}

		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;

		$record = $wpdb->get_results($wpdb->prepare("select * from {$tableName} where object_key = %s", $oldObjectKey), ARRAY_A);
		if (empty($record)) {
			return;
		}

		$oldId = $record[0]['id'];
		unset($record[0]['id']);
		$record[0]['object_key'] = $newObjectKey;

		$wpdb->insert($tableName, $record[0]);
		$wpdb->delete($tableName, ['id' => $oldId]);
	}

	/**
	 * Updates the map with cloud storage info
	 *
	 * @param string $url
	 * @param string $objectKey
	 * @param string $filePath
	 * @param array $s3Info
	 *
	 * @return string|null
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	public static function updateMap($url, $objectKey, $filePath, $s3Info) {
		if (!static::verifyInstalled()) {
			return null;
		}

		$url = str_replace((static::$scheme === 'https://') ? 'http://' : 'https://', static::$scheme, $url);

		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;

		if (!empty($objectKey)) {
			$wpdb->delete($tableName, ['object_key' => $objectKey]);
			$wpdb->insert($tableName, ['object_key' => $objectKey, 'post_url' => $url, 'file_deleted' => (int)0, 'file_path' => $filePath, 's3_info' => serialize($s3Info)], ['%s', '%s', '%d', '%s', '%s']);
		} else {
			$wpdb->delete($tableName, ['post_url' => $url]);
			$wpdb->insert($tableName, ['post_url' => $url, 'file_deleted' => (int)0, 'file_path' => $filePath, 's3_info' => serialize($s3Info)], ['%s', '%d', '%s', '%s']);
		}

		if (BuddyPressSettings::instance()->deleteUploads) {
			$task = TaskSchedule::nextScheduledTaskOfType(BuddyPressDeleteTask::identifier());
			if (!empty($task)) {
				$task->selection = array_merge($task->selection, ['id' => $wpdb->insert_id, 'file_path' => $filePath]);
				$task->save();
			} else {
				BuddyPressDeleteTask::scheduleIn(5, [], ['id' => $wpdb->insert_id, 'file_path' => $filePath]);
			}
		}

		$newUrl = static::generateUrl($s3Info);
		if (!empty($newUrl)) {
			if (!empty($url)) {
				static::$urlCache[$url] = $newUrl;
			}

			if (!empty($objectKey)) {
				static::$objectCache[$objectKey] = $newUrl;
			}
		}

		return $newUrl;
	}

	public static function deleteMap($objectKey) {
		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;
		$wpdb->delete($tableName, ['object_key' => $objectKey], ['%s']);
		if (isset(static::$objectCache[$objectKey])) {
			unset(static::$objectCache[$objectKey]);
		}
	}

	//endregion

	//region File Management

	public static function undeletedFiles() {
		if (!static::verifyInstalled()) {
			return [];
		}

		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;
		$results = $wpdb->get_results("select id, file_path from {$tableName} where file_deleted = 0", ARRAY_A);

		return $results;
	}

	public static function markDeleted($id) {
		if (!static::verifyInstalled()) {
			return;
		}

		global $wpdb;

		$tableName = $wpdb->base_prefix.self::TABLE_NAME;
		$wpdb->update($tableName, ['file_deleted' => 1], ['id' => $id], ['%d'], ['%d']);
	}

	//endregion
}