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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\CLI;

use MediaCloud\Plugin\CLI\Command;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks\UpdateElementorTask;

/**
 * Various commands related to Elementor integration
 */
class ElementorCommand extends Command {

	/**
	 * Updates Elementor's data with the correct URLs.
	 *
	 * ## OPTIONS
	 *
	 * [--report]
	 * : Generate report about what Media Cloud updated.  You can find that report in your 'wp-content/mcloud-reports' directory.
	 *
	 * [--skip-widget-cache]
	 * : Media Cloud generates a widget cache to speed up subsequent runs of this task.  If you've recently updated Elementor or any of it's addons, you should specify this flag to skip the cache.
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function update($args, $assoc_args) {
		global $media_cloud_licensing;
		if ($media_cloud_licensing->is__premium_only()) {
			if(!class_exists("\\MediaCloud\\Plugin\\Tools\\Integrations\\PlugIns\\Elementor\\Tasks\\UpdateElementorTask")) {
				self::Error("Elementor integration does not exist.  This feature is only available in the Pro version of the plugin.");
				exit(1);
			}

			Command::Out("", true);
			Command::Warn("%WThis command will modify the data of your Elementor pages and posts.  Make sure to backup your database first.%n");
			Command::Out("", true);

			\WP_CLI::confirm("Are you sure you want to continue?", $assoc_args);

			$task = new UpdateElementorTask();
			$this->runTask($task, [
				'generate-report' => isset($assoc_args['report']),
				'skip-widget-cache' => isset($assoc_args['skip-widget-cache'])
			]);
		} else {
			self::Error("Only available in the Premium version.  To upgrade: https://mediacloud.press/pricing/");
		}
	}

	public static function Register() {
		\WP_CLI::add_command('mediacloud:elementor', __CLASS__);
	}
}