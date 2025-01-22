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

namespace MediaCloud\Plugin\Tools\Storage\Tasks;

use MediaCloud\Plugin\Tasks\AttachmentTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

class RegenerateThumbnailTask extends AttachmentTask {
	const MODE_ALL = 0;
	const MODE_MISSING = 1;
	const MODE_SIZE = 2;

	const REGENERATE_ALL = 0;
	const REGENERATE_MISSING = 1;
	const REGENERATE_SIZE = 2;

	private static $imageSizes = null;
	private static $imageSizeNames = null;

	private $allowedSizes = [];

	protected $reportHeaders = [
		'Post ID',
		'Filename',
		'Title',
		'Status'
	];

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'regenerate-thumbs-task';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Regenerate Thumbnails';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.regenerate-thumbs-task';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Rebuild Thumbnails';
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
		return "Regenerate Thumbnails";
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
		return '/batch/thumbnails';
	}


	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		$sizes = ilab_get_image_sizes();
		$sizeOptions = [];
		$replaceOptions = [];

		foreach($sizes as $size => $sizeInfo) {
			$sizeOptions[$size] = "Any item missing the specific size '$size'";
			$replaceOptions[$size] = "Only regenerate the size '$size'";
		}


		return [
			'selected-items' => [
				"title" => "Selected Media",
				"description" => "If you want to process just a small subset of items, click on 'Select Media'",
				"type" => "media-select",
				"media-types" => ['image']
			],
			'sort-order' => [
				"title" => "Sort Order",
				"description" => "Controls the order that items are processed.",
				"type" => "select",
				"options" => [
					'default' => 'Default',
					'date-asc' => "Oldest first",
					'date-desc' => "Newest first",
					'title-asc' => "Title, A-Z",
					'title-desc' => "Title, Z-A",
					'filename-asc' => "File name, A-Z",
					'filename-desc' => "File name, Z-A",
				],
				"default" => 'default',
			],
			'mode' => [
				"title" => "Processing Mode",
				"description" => "Controls which items are processed.",
				"type" => "select",
				"options" => array_merge([
					'missing' => 'Any item that is missing any image size',
					'all' => 'Regenerate all selected images.',
				], $sizeOptions),
				"default" => 'missing',
			],
			'regenerate' => [
				"title" => "Regenerate Mode",
				"description" => "Controls which sizes are regenerated.",
				"type" => "select",
				"options" => array_merge([
					'missing' => 'Only regenerate missing image sizes.',
					'all' => 'Regenerate all image sizes, even existing ones.',
				], $replaceOptions),
				"default" => 'missing',
			],
		];
	}

	public function willStart() {
		parent::willStart();

		add_filter('media-cloud/dynamic-images/skip-url-generation', '__return_true');
		add_filter('media-cloud/optimizer/no-background', '__return_true', PHP_INT_MAX);
	}

	public function didFinish() {
		remove_filter('media-cloud/dynamic-images/skip-url-generation', '__return_true');
		parent::didFinish();
	}

	protected function filterPostArgs($args) {
		$args['post_mime_type'] = 'image';
		return $args;
	}

	protected function filterItem($item, $options) {
		if (static::$imageSizes === null) {
			static::$imageSizes = ilab_get_image_sizes();
			static::$imageSizeNames = array_keys(static::$imageSizes);
		}

		$mode = arrayPath($options, 'mode', 'missing');
		if ($mode === 'all') {
			$item['m'] = (int)self::MODE_ALL;
		} else if ($mode === 'missing') {
			$item['m'] = self::MODE_MISSING;
		} else {
			$item['m'] = self::MODE_SIZE;
			$item['sz'] = array_search($mode, static::$imageSizeNames);
		}

		$replace = arrayPath($options, 'regenerate', 'missing');
		if ($replace === 'all') {
			$item['r'] = (int)self::REGENERATE_ALL;
		} else if ($replace === 'missing') {
			$item['r'] = self::REGENERATE_MISSING;
		} else {
			$item['r'] = self::REGENERATE_SIZE;
			$item['rsz'] = array_search($replace, static::$imageSizeNames);
		}

		return $item;
	}

	public function getImageSizes($new_sizes, $image_meta, $attachment_id) {
		return $this->allowedSizes;
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
		if (static::$imageSizes === null) {
			static::$imageSizes = ilab_get_image_sizes();
			static::$imageSizeNames = array_keys(static::$imageSizes);
		}

		$post_id = $item['id'];
		if (!postIdExists($post_id)) {
			return true;
		}

		$this->updateCurrentPost($post_id);

		// reset all the filtering shiz
		$this->allowedSizes = [];
		remove_filter('intermediate_image_sizes_advanced', [$this, 'getImageSizes'], PHP_INT_MAX);
		remove_filter('media-cloud/storage/report-missing-files', '__return_false');

		// Fetch the processing mode
		$mode = arrayPath($item, 'm', (int)static::MODE_MISSING);
		$sizeIdx = (isset($item['sz'])) ? $item['sz'] : -1;
		$size = null;
		if ($mode == static::MODE_SIZE) {
			// make sure the requested size exists
			if (($sizeIdx == -1) || ($sizeIdx >= count(static::$imageSizes))) {
				Logger::warning("The $sizeIdx was wrong or too big.  Dropping to mode 1");
				$mode = (int)static::MODE_MISSING;
			} else {
				$size = static::$imageSizeNames[$sizeIdx];
			}
		}

		// Fetch the regenerate mode
		$regenerate = arrayPath($item, 'r', (int)static::REGENERATE_MISSING);
		$regenerateSizeIdx = (isset($item['rsz'])) ? $item['rsz'] : -1;
		$regenerateSize = null;
		if ($regenerate == static::REGENERATE_SIZE) {
			// make sure the regenerate mode exists
			if (($regenerateSizeIdx == -1) || ($regenerateSizeIdx >= count(static::$imageSizes))) {
				Logger::warning("The $regenerateSizeIdx was wrong or too big.  Dropping to regenerate mode 1");
				$regenerate = (int)1;
			} else {
				$regenerateSize = static::$imageSizeNames[$regenerateSizeIdx];
			}
		}

		$sizes = false;
		$diff = [];
		// If either mode or regenerate are not all, we need to determine what sizes should exist
		// to both check for mode, and to specify for regenerate
		if (($regenerate > static::REGENERATE_ALL) || ($mode > static::MODE_ALL))  {
			$meta = get_post_meta($post_id, '_wp_attachment_metadata', true);
			$sizes = arrayPath($meta, 'sizes', []);

			$uploadBase = basename(arrayPath($meta, 'file'));
			if (!empty($uploadBase)) {
				foreach($sizes as $psizeName => $psizeData) {
					if(arrayPath($psizeData, 'file') == $uploadBase) {
						unset($sizes[$psizeName]);
					}
				}
			}

			foreach($sizes as $psizeName => $psizeData) {
				if (!isset($psizeData['file']) || empty($psizeData['file'])) {
					$key = arrayPath($psizeData, 's3/key', null);
					if (!empty($key)) {
						$sizes[$psizeName]['file'] = pathinfo($key, PATHINFO_BASENAME);
					} else {
						unset($sizes[$psizeName]);
					}
				}
			}

			// If mode is a specific size, if it's set then we can exit here
			if (($mode == static::MODE_SIZE) && isset($sizes[$size])) {
				Logger::info("Target size $size exists, skipping image", [], __METHOD__, __LINE__);
				return true;
			}

			$imageWidth = intval(arrayPath($meta, 'width', (int)0));
			$imageHeight = intval(arrayPath($meta, 'height', (int)0));
			if (($imageWidth === 0) || ($imageHeight === 0)) {
				Logger::warning("Image dims is wrong or missing, {$imageWidth}x{$imageHeight}.  Skipping item.", [], __METHOD__, __LINE__);
				return true;
			}

			$validSizes = [];
			foreach(static::$imageSizes as $sizeName => $sizeData) {
				$w = empty($sizeData['width']) ? (int)0 : (int)$sizeData['width'];
				$h = empty($sizeData['height']) ? (int)0 : (int)$sizeData['height'];

				$newSize = image_resize_dimensions($imageWidth, $imageHeight, $w, $h, !empty($sizeData['crop']));
				if (!empty($newSize) && (($newSize[4] <= $imageWidth) && ($newSize[5] <= $imageHeight))) {
					$validSizes[] = $sizeName;
				}
			}

			$diff = array_diff($validSizes, array_keys($sizes));
		}

		// If mode is "missing" but there aren't any missing sizes, exit
		if ((($mode == static::MODE_MISSING) || ($regenerate == static::REGENERATE_MISSING)) && (count($diff) == 0)) {
			Logger::info("Image contains the correct number of image sizes, skipping", [], __METHOD__, __LINE__);
			return true;
		}

		// Setup our allowed sizes and filters for regeneration
		if (($regenerate == static::REGENERATE_ALL) || ($regenerate == static::REGENERATE_MISSING)) {
			if ($regenerate == static::REGENERATE_MISSING) {
				foreach($diff as $validName) {
					$this->allowedSizes[$validName] = static::$imageSizes[$validName];
				}

				$missed = implode(', ', $diff);
				Logger::info("Missing valid sizes '$missed'.", [], __METHOD__, __LINE__);
			} else {
				$this->allowedSizes[$regenerateSize] = static::$imageSizes[$regenerateSize];
				Logger::info("Only regenerating the image size '$regenerateSize'.", [], __METHOD__, __LINE__);
			}

			add_filter('media-cloud/storage/report-missing-files', '__return_false');
			add_filter('intermediate_image_sizes_advanced', [$this, 'getImageSizes'], PHP_INT_MAX, 3);
		} else {
			$sizes = false;
		}

		Logger::info("Regenerating $post_id", [], __METHOD__, __LINE__);

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		$result = $storageTool->regenerateFile($post_id, $sizes);

		remove_filter('intermediate_image_sizes_advanced', [$this, 'getImageSizes'], PHP_INT_MAX);


		$this->reporter()->add([
			$post_id,
			$this->currentFile,
			$this->currentTitle,
			($result === true) ? 'Success' : $result
		]);

		if ($result !== true) {
			Logger::error("Error regenerating $post_id: $result", [], __METHOD__, __LINE__);
			return $result;
		} else {
			Logger::info("Finished regenerating $post_id", [], __METHOD__, __LINE__);
		}

		return true;
	}
}
