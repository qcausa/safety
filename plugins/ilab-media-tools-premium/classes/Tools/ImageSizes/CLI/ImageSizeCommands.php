<?php
// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// Uses code from:
// Persist Admin Notices Dismissal
// by Agbonghama Collins and Andy Fragen
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\ImageSizes\CLI;

use MediaCloud\Plugin\CLI\Command;
use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\ImageSizes\Tasks\UpdateImagePrivacyTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\Tasks\UpdateWebStoriesTask;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\Browser\Tasks\ImportFromStorageTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks\UpdateElementorTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery\Tasks\MigrateNextGenTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\Tasks\MigrateFromOtherTask;
use MediaCloud\Plugin\Tools\Storage\Tasks\MigrateTask;
use MediaCloud\Plugin\Tools\Storage\Tasks\RegenerateThumbnailTask;
use MediaCloud\Plugin\Tools\Storage\Tasks\UnlinkTask;
use MediaCloud\Plugin\Tools\Storage\Tasks\VerifyLibraryTask;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Search\Searcher;
use MediaCloud\Vendor\GuzzleHttp\Client;
use Mpdf\Shaper\Sea;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined('ABSPATH')) { header('Location: /'); die; }

/**
 * Import to Cloud Storage, rebuild thumbnails, etc.
 * @package MediaCloud\Plugin\CLI\Storage
 */
class ImageSizeCommands extends Command {
    private $debugMode = false;

	/**
	 * Migrates the media library to the cloud.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : The maximum number of items to process, default is infinity.
	 *
	 * [--offset=<number>]
	 * : The starting offset to process.  Cannot be used with page.
	 *
	 * [--page=<number>]
	 * : The starting offset to process.  Page numbers start at 1.  Cannot be used with offset.
	 *
	 * [--order-by=<string>]
	 * : The field to sort the items to be imported by. Valid values are 'date', 'title' and 'filename'.
	 *
	 * [--order=<string>]
	 * : The sort order. Valid values are 'asc' and 'desc'.
	 * ---
	 * default: asc
	 * options:
	 *   - asc
	 *   - desc
	 * ---
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function update($args, $assoc_args) {
		$this->debugMode = (\WP_CLI::get_config('debug') == 'mediacloud');

		// Force the logger to initialize
		Logger::instance();

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		if(!$storageTool || !$storageTool->enabled()) {
			Command::Error('Storage tool is not enabled in Media Cloud or the settings are incorrect.');
			exit(1);
		}

		$options = $assoc_args;

		if(isset($options['limit'])) {
			if(isset($options['page'])) {
				$options['offset'] = max(0, ($assoc_args['page'] - 1) * $assoc_args['limit']);
				unset($options['page']);
			}
		}

		if(isset($assoc_args['order-by'])) {
			$orderBy = $assoc_args['order-by'];
			$dir = arrayPath($assoc_args, 'order', 'asc');

			unset($assoc_args['order-by']);
			unset($assoc_args['order']);

			$assoc_args['sort-order'] = $orderBy . '-' . $dir;
		}

		$task = new UpdateImagePrivacyTask();
		$this->runTask($task, $assoc_args);
	}

	public static function Register() {
		\WP_CLI::add_command('mediacloud:image-sizes', __CLASS__);
	}

}
