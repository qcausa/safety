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

namespace MediaCloud\Plugin\Tools\Optimizer\Models\Data;

final class OptimizerData {
	const DB_VERSION = '1.0.2';
	const DB_KEY = 'mcloud_optimizer_db_version';
	const DB_TABLE = 'mcloud_pending_optimizations';

	private static $installed = false;

	/**
	 * Insures the additional database tables are installed
	 */
	public static function init() {
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

			$tableName = $wpdb->base_prefix.self::DB_TABLE;
			$exists = ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName);
			if ($exists) {
				static::$installed = true;
				return true;
			}
		}

		return static::installTable();
	}

	protected static function installTable() {
		global $wpdb;

		$tableName = $wpdb->base_prefix.self::DB_TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tableName} (
	id BIGINT AUTO_INCREMENT,
	created_at bigint NULL,
	saved_to_cloud int default 0,
	post_id bigint NULL,
	optimizer_id VARCHAR(255) NOT NULL,
	local_file VARCHAR(512) NULL,
	remote_url VARCHAR(512) NULL,
	wordpress_size VARCHAR(255) NOT NULL,
	s3_data TEXT NULL,
	PRIMARY KEY  (id),
	KEY optimizer_id(optimizer_id),
	KEY post_id(post_id)
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
}