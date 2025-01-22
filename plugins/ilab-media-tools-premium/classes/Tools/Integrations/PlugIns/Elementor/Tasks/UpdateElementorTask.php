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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks;


use Elementor\Plugin;
use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\ElementorUpdater;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\isKeyedArray;

class UpdateElementorTask extends Task {
	protected $reportHeaders = ['Post ID', 'Meta ID', 'Widget', 'Notes', 'Old URL', 'New URL'];

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'update-elementor';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Update Elementor';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.update-elementor';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Update Elementor';
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
		return '/batch/update-elementor';
	}



	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
			'generate-report' => [
				"title" => "Generate Report",
				"description" => "Generates a report detailing what changes to your Elementor pages Media Cloud made.  This report will be located in the <code>".TaskReporter::reporterDirectory()."</code> directory.",
				"type" => "checkbox",
				"default" => true
			],
			'skip-widget-cache' => [
				"title" => "Skip Widget Cache",
				"description" => "Media Cloud generates a widget cache to speed up subsequent runs of this task.  If you've recently updated Elementor or any of it's addons, you should turn this on.",
				"type" => "checkbox",
				"default" => false
			],
		];
	}

	public static function warnOption() {
		return 'update-elementor-task-warning-seen';
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

		global $wpdb;
		$query = <<<SQL
select 
     post_id, meta_id 
from 
     {$wpdb->postmeta} 
where 
     meta_key = '_elementor_data' 
and 
     meta_value LIKE '[%' 
and 
     post_id in (select ID from {$wpdb->posts} where post_type in ('page', 'post'))
SQL;

		$results = $wpdb->get_results($query, ARRAY_A);
		foreach($results as $result) {
			$this->addItem($result);
		}

		$this->addItem(['meta_id' => -1]);

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
		$metaId = $item['meta_id'];

		if ($metaId == -1) {
			Plugin::instance()->files_manager->clear_cache();
			return true;
		}

		$jsonData = get_post_meta_by_id($metaId);
		if (empty($jsonData)) {
			Logger::error("No data for $metaId", [], __METHOD__, __LINE__);
			return true;
		}

		$data = json_decode($jsonData->meta_value, true);
		if (empty($data)) {
			Logger::error("Could not decode JSON value for $metaId: ".$jsonData->meta_value, [], __METHOD__, __LINE__);
			return true;
		}

		Logger::info("Processing $metaId", [], __METHOD__, __LINE__);

		$data = ElementorUpdater::update(!empty($this->options['skip-widget-cache']), $item['post_id'], $metaId, $data, $this->reporter());

		$json = wp_slash(wp_json_encode($data));
		update_meta($metaId, '_elementor_data', $json);

		Logger::info("Finished processing $metaId", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}
