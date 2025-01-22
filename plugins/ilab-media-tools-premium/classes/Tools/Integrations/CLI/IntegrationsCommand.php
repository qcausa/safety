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

namespace MediaCloud\Plugin\Tools\Integrations\CLI;

use MediaCloud\Plugin\CLI\Command;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery\Tasks\MigrateNextGenTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\Tasks\UpdateWebStoriesTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;

/**
 * Various commands related to third party plugin integrations
 */
class IntegrationsCommand extends Command {

	/**
	 * Migrate NextGen Gallery images to cloud storage.
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function migrateNGG($args, $assoc_args) {
		global $media_cloud_licensing;
		if ($media_cloud_licensing->is__premium_only()) {
			if(!class_exists("\\MediaCloud\\Plugin\\Tools\\Integrations\\PlugIns\\NextGenGallery\\Tasks\\MigrateNextGenTask")) {
				self::Error("Migrate NextGen Gallery integration does not exist.  This feature is only available in the Pro version of the plugin.");
				exit(1);
			}

			$task = new MigrateNextGenTask();
			$this->runTask($task, []);
		} else {
			self::Error("Only available in the Premium version.  To upgrade: https://mediacloud.press/pricing/");
		}
	}

	/**
	 * Updates Google Web Stories with the correct URLs.
	 *
	 * ## OPTIONS
	 *
	 * [--report]
	 * : Generate report about what Media Cloud updated.  You can find that report in your 'wp-content/mcloud-reports' directory.
	 *
	 * [--yes]
	 * : Skip prompts.
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function updateWebStories($args, $assoc_args) {
		global $media_cloud_licensing;
		if ($media_cloud_licensing->is__premium_only()) {
			if(!class_exists( "MediaCloud\\Plugin\\Tools\\Integrations\\PlugIns\\WebStories\\Tasks\\UpdateWebStoriesTask")) {
				self::Error("Google Web Stories integration does not exist.  This feature is only available in the Pro version of the plugin.");
				exit(1);
			}

			Command::Out("", true);
			Command::Warn("%WThis command will modify the data of your Google Web Stories.  Make sure to backup your database first.%n");
			Command::Out("", true);

			\WP_CLI::confirm("Are you sure you want to continue?", $assoc_args);

			$task = new UpdateWebStoriesTask();
			$this->runTask($task, [
				'generate-report' => isset($assoc_args['report'])
			]);
		} else {
			self::Error("Only available in the Premium version.  To upgrade: https://mediacloud.press/pricing/");
		}
	}

	public function fixLeopardWooLinks($args, $assoc_args) {
		global $media_cloud_licensing;
		if ($media_cloud_licensing->is__premium_only()) {
			if (!ToolsManager::instance()->toolEnabled('storage')) {
				self::Error("%WThis command requires Cloud Storage to be enabled.%n");
				return;
			}


			$prefixFix = Environment::Option('mcloud-leopard-prefix-fix', null, false);


			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			global $wpdb;
			$rows = $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '_downloadable_files'", ARRAY_A);
			self::Out("Found %Y".count($rows)."%n rows to process.", true);

			$index = 1;
			foreach($rows as $row) {
				self::Out("Processing row %Y{$index}%n of %Y".count($rows)."%n ... ");
				$changed = false;
				$val = maybe_unserialize($row['meta_value']);
				if (is_array($val)) {
					foreach($val as $key => &$item) {
						if (strpos($item['file'], 'http://[leopard') === 0) {
							$wtfre = '/key\=(.*)\%20/m';
							preg_match_all($wtfre, $item['file'], $matches, PREG_SET_ORDER, 0);

							if (count($matches) > 0) {
								$match = $matches[0];
								$key = $match[1];
								$item['file'] = $storageTool->client()->url($key);
								$changed = true;
							}

							continue;
						} else if (strpos($item['file'], '[leopard_wordpress_offload_media_storage') !== 0) {
							continue;
						}

						$atts = shortcode_parse_atts($item['file']);
						if (empty($atts['key'])) {
							continue;
						}

						$path = ltrim(parse_url($atts['key'], PHP_URL_PATH), '/');
						if (!empty($prefixFix) && (strpos($path, $prefixFix) !== 0)) {
							$path = $prefixFix . '/' . $path;
						}

						$item['file'] = $storageTool->client()->url($path);

						$changed = true;
					}
				}

				if ($changed) {
					$wpdb->update($wpdb->postmeta, [
						'meta_value' => serialize($val)
					], [
						'meta_id' => $row['meta_id']
					]);

					self::Out("Saved.", true);
				} else {
					self::Out("Skipped.", true);
				}

				$index++;
			}
		} else {
			self::Error("Only available in the Premium version.  To upgrade: https://mediacloud.press/pricing/");
		}
	}

	public static function Register() {
		\WP_CLI::add_command('mediacloud:integrations', __CLASS__);
	}
}