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

namespace MediaCloud\Plugin\Tools\ImageSizes\Tasks;

use MediaCloud\Plugin\Tasks\AttachmentTask;
use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\ImageSizes\ImageSizePrivacy;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

class UpdateImagePrivacyTask extends AttachmentTask {

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'update-image-privacy';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Update Image Privacy';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Update Image Privacy';
	}

	public static function showInMenu() {
		return true;
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
		return 'Update Image Privacy';
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
		return '/batch/update-image-privacy';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.update-image-privacy';
	}


	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		$options = [
			'selected-items' => [
				"title" => "Selected Media",
				"description" => "If you want to process just a small subset of items, click on 'Select Media'",
				"type" => "media-select"
			]
		];

		return $options;
	}

	/** @var null|\Closure  */
	public static $callback = null;

	//endregion

	//region Data

	protected function filterPostArgs($args) {
		$args['meta_query'] = [
			'relation' => 'OR',
			[
				'key'     => '_wp_attachment_metadata',
				'value'   => '"s3"',
				'compare' => 'LIKE',
				'type'    => 'CHAR',
			],
			[
				'key'     => 'ilab_s3_info',
				'compare' => 'EXISTS',
			],
		];

		return $args;
	}

	public function reporter() {
		if (empty($this->reportHeaders)) {
			$fields = [
				'Post ID',
				'Status',
			];

			$this->reportHeaders = $fields;
		}

		return parent::reporter();
	}

	//endregion

	//region Execution

	/**
	 * Performs the actual task
	 *
	 * @param $item
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function performTask($item) {
		$post_id = $item['id'];

		if (!postIdExists($post_id)) {
			return true;
		}

		Logger::info("Updating $post_id", [], __METHOD__, __LINE__);

		$defaultPrivacy = StorageToolSettings::privacy('image');


		$this->updateCurrentPost($post_id);

		$meta = get_post_meta($post_id, '_wp_attachment_metadata', true);
		if (empty($meta)) {
			Logger::warning("Skipped $post_id because meta was missing.", [], __METHOD__, __LINE__);
			$this->reporter()->add([
				$post_id,
				'Missing metadata'
			]);
			return true;
		}

		$imageS3 = arrayPath($meta, 's3');
		if (empty($imageS3)) {
			Logger::warning("Skipped $post_id because s3 meta was missing.", [], __METHOD__, __LINE__);
			$this->reporter()->add([
				$post_id,
				'Missing S3'
			]);
			return true;
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$changes = 0;
		$sizes = array_merge(['full' => ['s3' => $imageS3]], arrayPath($meta, 'sizes', []));
		foreach($sizes as $size => $sizeData) {
			$privacy = ImageSizePrivacy::privacyForSize($size, $defaultPrivacy);

			$key = arrayPath($sizeData, 's3/key');
			if (empty($key)) {
				Logger::warning("Skipped size '$size' for $post_id because s3 meta was missing.", [], __METHOD__, __LINE__);
				continue;
			}

			try {
				$storageTool->client()->updateACL($key, $privacy);
				$changes++;

				if ($size == 'full') {
					$meta['s3']['privacy'] = $privacy;
				} else {
					$meta['sizes'][$size]['s3']['privacy'] = $privacy;
				}

				update_post_meta($post_id, '_wp_attachment_metadata', $meta);
			} catch (\Exception $ex) {
				Logger::error("Error changing size '$size' for $post_id: ".$ex->getMessage(), [], __METHOD__, __LINE__);
				$this->reporter()->add([
					$post_id,
					$ex->getMessage()
				]);
				continue;
			}
		}

		$this->reporter()->add([
			$post_id,
			"$changes images updated."
		]);

		Logger::info("Finished updating $post_id", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}
