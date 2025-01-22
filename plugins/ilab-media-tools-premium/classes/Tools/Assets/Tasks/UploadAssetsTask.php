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

namespace MediaCloud\Plugin\Tools\Assets\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Assets\AssetsTool;
use MediaCloud\Plugin\Tools\Assets\AssetsToolSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;

class UploadAssetsTask extends Task {
	//region Properties

	/** @var AssetsToolSettings  */
	protected $settings = null;

	/** @var StorageTool  */
	protected $storageTool = null;

	/** @var string|null  */
	protected $rootPath = null;

	protected $processedScripts = [];
	protected $processedStyles = [];

	//endregion

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'upload-assets';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Upload Assets';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return null;
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
	 * Bulk action title.
	 *
	 * @return string|null
	 */
	public static function bulkActionTitle() {
		return null;
	}

	/**
	 * Determines if a task is a user facing task.
	 * @return bool|false
	 */
	public static function userTask() {
		return false;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/upload-assets';
	}

	public static function runFromTaskManager() {
		return false;
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [];
	}

	//endregion

	//region Init

	public function __construct($data = null) {
		$this->storageTool = ToolsManager::instance()->tools['storage'];
		$this->settings = new AssetsToolSettings();

		parent::__construct($data);
	}

	//endregion


	//region Execution

	public function prepare($options = [], $selectedItems = []) {
		$this->options = $options;

		foreach($selectedItems as $hash => $item) {
			$this->addItem([
				'hash' => $hash,
				'item' => $item
			]);
		}

		return true;
	}

	protected function uploadCSS($item) {
		$hash = $item['hash'];
		$cssEntry = $item['item'];

		/** @var AssetsTool $tool */
		$tool = ToolsManager::instance()->tools['assets'];
		$cssEntry = $tool->uploadCSSItem($cssEntry);

		$this->processedStyles[$hash] = $cssEntry;
	}

	protected function uploadJS($item) {
		$hash = $item['hash'];
		$jsEntry = $item['item'];

		/** @var AssetsTool $tool */
		$tool = ToolsManager::instance()->tools['assets'];
		$jsEntry = $tool->uploadJSItem($jsEntry);

		$this->processedScripts[$hash] = $jsEntry;
	}

	/**
	 * Performs the actual task
	 *
	 * @param $item
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function performTask($item) {
		$type = arrayPath($this->options, 'type', null);
		if (empty($type)) {
			Logger::error("No type specified for asset upload task.", [], __METHOD__, __LINE__);
			return true;
		}

		try {
			if ($type === 'css') {
				$this->uploadCSS($item);
			} else {
				$this->uploadJS($item);
			}
		} catch (\Exception $ex) {
			Logger::error("Error uploading asset for type {$type}: {$ex->getMessage()}", [], __METHOD__, __LINE__);
		}

		return true;
	}

	public function willStart() {
		if (isset($this->options['site'])) {
			switch_to_blog($this->options['site']);
	}
	}

	public function didFinish() {
		if (!empty($this->processedStyles)) {
			Logger::info("Saving styles.", [], __METHOD__, __LINE__);
			$processed = get_option('mcloud-assets-styles', []);
			$processed = array_merge($processed, $this->processedStyles);
			update_option('mcloud-assets-styles', $processed);
		}

		if (!empty($this->processedScripts)) {
			Logger::info("Saving scripts.", [], __METHOD__, __LINE__);
			$processed = get_option('mcloud-assets-scripts', []);
			$processed = array_merge($processed, $this->processedScripts);
			update_option('mcloud-assets-scripts', $processed);
		}
	}

	public function complete() {
		Logger::info("Assets complete", [], __METHOD__, __LINE__);

		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}

		$type = arrayPath($this->options, 'type', null);

		if ($type === 'css') {
			delete_transient('mcloud-assets-css-task-'.$this->settings->version);
		} else {
			delete_transient('mcloud-assets-js-task-'.$type.'-'.$this->settings->version);
		}
	}

	//endregion
}
