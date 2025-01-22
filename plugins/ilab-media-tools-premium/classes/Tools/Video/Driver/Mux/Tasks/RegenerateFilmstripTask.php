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


namespace MediaCloud\Plugin\Tools\Video\Driver\Mux\Tasks;

use MediaCloud\Plugin\Tasks\AttachmentTask;
use MediaCloud\Plugin\Tools\Video\Driver\Mux\Models\MuxAsset;
use MediaCloud\Plugin\Tools\Video\Driver\Mux\MuxTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

class RegenerateFilmstripTask extends AttachmentTask {

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'regenerate-filmstrip-task';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Regenerate Filmstrips';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.regenerate-filmstrip-task';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Rebuild Filmstrips';
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
		return 'Regenerate Filmstrips';
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
		return '/batch/regenerate-filmstrips';
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
				"type" => "media-select",
				"media-types" => ['video']
			],
			'sort-order' => [
				"title" => "Sort Order",
				"description" => "Controls the order that items from your media library are migrated to cloud storage.",
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
		];

		return $options;
	}

	//endregion

	//region Data

	protected function filterPostArgs($args) {
		$args['post_mime_type'] = 'video';

		return $args;
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

		$this->updateCurrentPost($post_id);

		Logger::info("Processing $post_id", [], __METHOD__, __LINE__);

		$asset = MuxAsset::assetForAttachment($post_id);
		if (!$asset) {
			Logger::info("No asset found for $post_id", [], __METHOD__, __LINE__);
			return true;
		}

		$asset->generateFilmstrip();

		Logger::info("Finished processing $post_id", [], __METHOD__, __LINE__);

		return true;
	}


	public function complete() {
		do_action('media-cloud/storage/migration/complete');

		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}
	}

	//endregion
}