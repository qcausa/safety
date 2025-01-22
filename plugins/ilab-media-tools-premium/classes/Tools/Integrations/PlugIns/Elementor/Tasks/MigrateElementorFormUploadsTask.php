<?php

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks;

use Elementor\Plugin;
use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\ElementorIntegration;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\ElementorUpdater;
use MediaCloud\Plugin\Utilities\Logging\Logger;

class MigrateElementorFormUploadsTask extends Task {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'migrate-elementor-forms';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate Elementor Forms';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.migrate-elementor-forms';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate Elementor Forms';
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
		return '/batch/migrate-elementor-forms';
	}



	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
		];
	}

	public static function warnOption() {
		return 'update-migrate-elementor-forms-warning-seen';
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
		return null;
	}

	public function prepare($options = [], $selectedItems = []) {
		global $wpdb;

		$results = $wpdb->get_results("select id from {$wpdb->prefix}e_submissions_values", ARRAY_A);
		if (empty($results)) {
			return false;
		}

		foreach($results as $result) {
			$this->addItem($result);
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
		global $wpdb;

		$id = $item['id'];
		$dbvalue = $wpdb->get_var($wpdb->prepare("select value from {$wpdb->prefix}e_submissions_values where id = %d", $id));
		if (empty($dbvalue)) {
			return true;
		}

		$values = explode(' , ', $dbvalue);
		$newvalues = [];
		foreach($values as $value) {
			$path = parse_url($value, PHP_URL_PATH);
			$fileName = basename($path);
			$newUrl = ElementorIntegration::handleFormUpload($value, $fileName, false);
			if (empty($newUrl)) {
				return true;
			}

			$newvalues[] = $newUrl;
		}

		$wpdb->update($wpdb->prefix.'e_submissions_values', ['value' => implode(' , ', $newvalues)], ['id' => $id]);
		return true;
	}

	//endregion
}