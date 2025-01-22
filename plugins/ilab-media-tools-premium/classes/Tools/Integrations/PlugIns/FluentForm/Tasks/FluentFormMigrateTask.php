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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm\FluentFormSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class FluentFormMigrateTask extends Task {
	//region Private Properties
	private $formCache = [];

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'fluent-form-migrate';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate Fluent Forms Uploads';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.fluent-form-migrate';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate Fluent Forms';
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
		return '/batch/fluent-forms-migrate';
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
		return 'migrate-fluent-forms-warning-seen';
	}

	public static function warnConfirmationAnswer() {
		return 'I UNDERSTAND';
	}

	public static function warnConfirmationText() {
		return "It is important that you backup your database prior to running this import task.  To continue, please type 'I UNDERSTAND' to confirm that you have backed up your database.";
	}

	//endregion



	//region Execution

	public function prepare($options = [], $selectedItems = []) {
		global $wpdb;

		$submissions = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}fluentform_submissions where status <> 'trashed'", ARRAY_A);
		foreach($submissions as $submission) {
			$this->addItem([
				'id' => $submission['id']
			]);
		}


		return count($submissions) > 0;
	}

	protected function updateCurrentPost($submission_id, $title) {
		$this->currentItemID = $submission_id;
		$this->currentFile = $title;
		$this->currentTitle = $title;
		$this->currentThumb = wp_mime_type_icon('image/jpeg');

		$this->save();
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

		$settings = FluentFormSettings::instance();

		$submissionId = $item['id'];
		$submission = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}fluentform_submissions WHERE id = {$submissionId}", OBJECT);
		if (!$submission) {
			return true;
		}

		$this->updateCurrentPost($submission->id, "Submission #{$submission->serial_number}");

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		if (!isset($this->formCache[$submission->form_id])) {
			global $wpdb;
			$query = $wpdb->prepare('SELECT form_fields FROM '.$wpdb->prefix.'fluentform_forms WHERE id = %d and status<>%s', $submission->form_id, 'trashed');
			$fieldsJSON = $wpdb->get_var($query);
			if (!$fieldsJSON) {
				return true;
			}

			$fields = json_decode($fieldsJSON, true);
			if (!is_array($fields)) {
				return true;
			}

			$uploadFields = [];
			foreach($fields['fields'] as $field) {
				if (!in_array($field['element'], ['input_image', 'input_file'])) {
					continue;
				}

				$uploadFields[] = $field['attributes']['name'];
			}

			$this->formCache[$submission->form_id] = $uploadFields;
		} else {
			$uploadFields = $this->formCache[$submission->form_id];
		}

		$responseData = json_decode($submission->response, true);
		if (arrayPath($responseData, 'migrated', false) !== false) {
			return true;
		}

		foreach($uploadFields as $uploadField) {
			if (!isset($responseData[$uploadField])) {
				continue;
			}

			$uploadData = &$responseData[$uploadField];
			if (!is_array($uploadData) || empty($uploadData)) {
				continue;
			}

			foreach($uploadData as &$url) {
				$key = trim(parse_url($url, PHP_URL_PATH), '/');
				$src = ABSPATH.'/'.$key;
				if (!file_exists($src)) {
					continue;
				}

				try {
					ilab_set_time_limit(0);

					if ($settings->randomFilename) {
						$uniquePath = Prefixer::genUUIDPath();
						$ext = pathinfo($src, PATHINFO_EXTENSION);
						$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
					}

					$s3Url = $storageTool->client()->upload($key, $src, $settings->acl);
					if ($s3Url) {
						$url = $s3Url;
					}
				} catch (\Exception $ex) {
					Logger::error($ex->getMessage(), [], __METHOD__, __LINE__);
				}
			}
		}

		$responseData['migrated'] = true;
		$wpdb->update($wpdb->prefix.'fluentform_submissions', [
			'response' => json_encode($responseData)
		], [
			'id' => $submission->id
		]);

		return true;
	}

	//endregion
}