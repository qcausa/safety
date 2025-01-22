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

namespace MediaCloud\Plugin\Tools\Browser\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Vendor\Mimey\MimeTypes;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;

class ImportFromStorageTask extends Task {
	protected $reportHeaders = [
		'Post ID',
		'Key',
		'Thumb Count',
		'Thumbs',
		'Import Only',
		'Scaled',
		'Result'
	];

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'import-task';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Import From Cloud Storage';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.import-task';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Import From Cloud';
	}

	/**
	 * Controls if this task stops on an error.
	 *
	 * @return bool
	 */
	public static function stopOnError() {
		return false;
	}

	/**
	 * Determines if a task is a user facing task.
	 * @return bool|false
	 */
	public static function userTask() {
		return true;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/import-storage';
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
			'import-path' => [
				"title" => "Import Path",
				"description" => "The folder on cloud storage to import.",
				"type" => "browser",
				"default" => "/"
			],
			'import-only' => [
				"title" => "Import Only",
				"description" => "Don't download, import to database only.",
				"type" => "checkbox",
				"default" => apply_filters('media-cloud/dynamic-images/enabled', false)
			],
			'preserve-paths' => [
				"title" => "Preserve Paths",
				"description" => "When downloading images, maintain the directory structure that is on cloud storage.",
				"type" => "checkbox",
				"default" => true
			],
			'skip-thumbnails' => [
				"title" => "Skip Thumbnails",
				"description" => "Skips any images that look like they might be thumbnails. If this option is on, you may import images that are thumbnails but they will be treated as individual images.",
				"type" => "checkbox",
				"default" => true
			],
		];
	}

	//endregion

	/**
	 * Performs the actual task
	 *
	 * @param $item
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function performTask($item) {
		Logger::info("Starting Import Task", [], __METHOD__, __LINE__);

		$importOnly = arrayPath($this->options, 'import-only', false);
		$preservePaths = arrayPath($this->options, 'preserve-paths', false);


		$this->currentItemID = $item['key'];
		$this->currentFile = basename($item['key']);
		$this->currentTitle = basename($item['key']);
		$this->currentThumb = arrayPath($item, 'thumb', null);
		$this->isIcon = arrayPath($item, 'icon', false);
		$this->save();

		Logger::info("Importing from storage: {$item['key']}", [], __METHOD__, __LINE__);
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		$storageTool->importFileFromStorage($item['key'], arrayPath($item, 'thumbs', []), $importOnly, $preservePaths, arrayPath($item, 'scaled', null), $this->reporter());

		return true;
	}

	/**
	 * @param array $options
	 * @param array $selectedItems
	 *
	 * @return bool
	 * @throws \MediaCloud\Plugin\Tools\Storage\StorageException
	 */
	public function prepare($options = [], $selectedItems = []) {
		ilab_set_time_limit(0);

		$this->options = $options;

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$key = (isset($options['import-path'])) ? $options['import-path'] : '';
		if ($key == '/') {
			$key = '';
		}

		$files = $storageTool->getFileList([$key], !empty($options['skip-thumbnails']), -1, null, true)['files'];

		$mimey = new MimeTypes();

		for($i = 0; $i < count($files); $i++) {
			if (!isset($files[$i]['thumbs']) || (count($files[$i]['thumbs']) === 0)) {
				$base = basename($files[$i]['key']);
				$ext = pathinfo($base, PATHINFO_EXTENSION);
				$mimeType = $mimey->getMimeType($ext);

				if (in_array($mimeType, ['image/gif', 'image/jpg', 'image/jpeg', 'image/png'])) {
					$files[$i]['thumb'] = $storageTool->client()->presignedUrl($files[$i]['key'], 60*72);
					$files[$i]['icon'] = false;
				} else {
					$files[$i]['thumb'] = wp_mime_type_icon($mimeType);
					$files[$i]['icon'] = true;
				}
			} else {
				foreach($files[$i]['thumbs'] as $thumb) {
					if (strpos($thumb, '150x150.') !== false) {
						$files[$i]['thumb'] = $storageTool->client()->presignedUrl($thumb, 60*72);
						$files[$i]['icon'] = false;
						break;
					}
				}

				if (empty($files[$i]['thumb'])) {
					$base = basename($files[$i]['key']);
					$ext = pathinfo($base, PATHINFO_EXTENSION);
					$mimeType = $mimey->getMimeType($ext);

					if (in_array($mimeType, ['image/gif', 'image/jpg', 'image/jpeg', 'image/png'])) {
						$files[$i]['thumb'] = $storageTool->client()->presignedUrl($files[$i]['key'], 60*72);
						$files[$i]['icon'] = false;
					} else {
						$files[$i]['thumb'] = wp_mime_type_icon($mimeType);
						$files[$i]['icon'] = true;
					}
				}
			}
		}

		foreach($files as $file) {
			$this->addItem($file);
		}

		return ($this->totalItems > 0);
	}

	public function complete() {
		do_action('media-cloud/storage/import/complete');

		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}
	}
}
