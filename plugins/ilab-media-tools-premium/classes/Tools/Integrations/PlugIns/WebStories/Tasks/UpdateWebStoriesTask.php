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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\Tasks;


use Elementor\Plugin;
use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\ElementorUpdater;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\WebStoriesIntegration;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\isKeyedArray;

class UpdateWebStoriesTask extends Task {
	protected $reportHeaders = ['Post ID', 'Notes', 'Old URL', 'New URL'];

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'update-web-stories';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Update Web Stories';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.update-web-stories';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Update Web Stories';
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
		return true;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/update-web-stories';
	}



	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
			'generate-report' => [
				"title" => "Generate Report",
				"description" => "Generates a report detailing what changes to your web stories Media Cloud made.  This report will be located in the <code>".TaskReporter::reporterDirectory()."</code> directory.",
				"type" => "checkbox",
				"default" => true
			],
		];
	}

	public static function warnOption() {
		return 'update-web-stories-task-warning-seen';
	}

	public static function warnConfirmationAnswer() {
		return 'I UNDERSTAND';
	}

	public static function warnConfirmationText() {
		return "It is important that you backup your database prior to running this task.  To continue, please type 'I UNDERSTAND' to confirm that you have backed up your database.";
	}

	//endregion

	//region Execution

	public function reporter() {
		if (empty($this->options['generate-report'])) {
			return null;
		}

		return parent::reporter();
	}

	public function prepare($options = [], $selectedItems = []) {
		$this->options = $options;

		$query = new \WP_Query([
			'post_type' => 'web-story',
			'status' => ['any'],
			'fields' => 'ids',
			'nopaging' => true,
			'post_per_page' => -1,
		]);

		foreach($query->posts as $postId) {
			$this->addItem(['post_id' => $postId]);
		}

		return true;
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
		$postId = $item['post_id'];

		Logger::info("Processing $postId", [], __METHOD__, __LINE__);

		$post = \WP_Post::get_instance($postId);
		WebStoriesIntegration::updatePost($postId, $post);

		Logger::info("Finished processing $postId", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}
